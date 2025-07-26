<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DailyEveningDipReadingsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedStation = $request->get('station_id');

        // Enforce station-level access control
        $stations = $this->getAuthorizedStations($user);

        if ($stations->isEmpty()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'No authorized stations found'], 403);
            }
            return redirect()->back()->with('error', 'No authorized stations found');
        }

        // Default to first authorized station if none selected
        $stationIds = $stations->pluck('id')->toArray();
        if (!$selectedStation || !in_array($selectedStation, $stationIds)) {
            $selectedStation = $stations->first()->id;
        }

        $today = Carbon::today()->toDateString();

        // Get tanks with morning readings but missing evening readings
        $pendingTanks = DB::table('daily_readings as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->where('t.station_id', $selectedStation)
            ->where('dr.reading_date', $today)
            ->where('dr.morning_dip_liters', '>', 0)
            ->where('dr.evening_dip_liters', '=', 0)
            ->select(
                't.id',
                't.tank_number',
                't.fuel_type',
                't.capacity_liters',
                'dr.morning_dip_liters',
                'dr.water_level_mm',
                'dr.temperature_celsius'
            )
            ->orderBy('t.tank_number')
            ->get();

        // Get completed evening readings for today
        $completedReadings = DB::table('daily_readings as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('users as u', 'dr.recorded_by_user_id', '=', 'u.id')
            ->where('t.station_id', $selectedStation)
            ->where('dr.reading_date', $today)
            ->where('dr.evening_dip_liters', '>', 0)
            ->select(
                't.tank_number',
                't.fuel_type',
                'dr.morning_dip_liters',
                'dr.evening_dip_liters',
                'dr.water_level_mm',
                'dr.temperature_celsius',
                'dr.updated_at',
                'u.first_name',
                'u.last_name'
            )
            ->orderBy('t.tank_number')
            ->get();

        // Handle AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'stations' => $stations,
                    'selectedStation' => $selectedStation,
                    'pendingTanks' => $pendingTanks,
                    'completedReadings' => $completedReadings,
                    'today' => $today
                ]
            ]);
        }

        return view('daily-evening-dip-readings.index', compact(
            'stations',
            'selectedStation',
            'pendingTanks',
            'completedReadings',
            'today'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate request
        $validator = validator($request->all(), [
            'station_id' => 'required|integer',
            'tank_id' => 'required|integer',
            'reading_date' => 'required|date|before_or_equal:today|after:' . Carbon::now()->subDays(30)->toDateString(),
            'evening_dip_liters' => 'required|numeric|min:0|max:999999999.999',
            'water_level_mm' => 'nullable|numeric|min:0|max:99999.99',
            'temperature_celsius' => 'nullable|numeric|min:-10|max:60'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Verify station access
            if (!$this->hasStationAccess($user, $request->station_id)) {
                throw new \Exception('Unauthorized station access');
            }

            // Verify tank belongs to station and get tank details
            $tank = DB::table('tanks')
                ->where('id', $request->tank_id)
                ->where('station_id', $request->station_id)
                ->first(['id', 'capacity_liters', 'tank_number']);

            if (!$tank) {
                throw new \Exception('Invalid tank for selected station');
            }

            // CRITICAL: Evening reading requires existing morning reading
            $morningReading = DB::table('daily_readings')
                ->where('tank_id', $request->tank_id)
                ->where('reading_date', $request->reading_date)
                ->where('morning_dip_liters', '>', 0)
                ->first();

            if (!$morningReading) {
                throw new \Exception('Morning reading must be recorded before evening reading');
            }

            // Check if evening reading already recorded
            if ($morningReading->evening_dip_liters > 0) {
                throw new \Exception('Evening reading already recorded for this tank and date');
            }

            // Validate evening dip doesn't exceed tank capacity
            if ($request->evening_dip_liters > $tank->capacity_liters) {
                throw new \Exception('Evening dip reading cannot exceed tank capacity');
            }

            // CRITICAL: Check meter readings exist for reconciliation
            $hasMeters = DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->where('m.tank_id', $request->tank_id)
                ->where('mr.reading_date', $request->reading_date)
                ->exists();

            if (!$hasMeters) {
                throw new \Exception('Meter readings required before evening dip reading');
            }

            // CRITICAL: Validate evening reading is physically possible
            if ($request->evening_dip_liters < 0) {
                throw new \Exception('Evening reading cannot be negative');
            }

            // CRITICAL: Validate water level progression (water should not increase significantly)
            if ($request->water_level_mm && $morningReading->water_level_mm) {
                $waterIncrease = $request->water_level_mm - $morningReading->water_level_mm;
                if ($waterIncrease > 50) {
                    throw new \Exception('Water level increase of ' . $waterIncrease . 'mm exceeds normal threshold');
                }
            }

            // CRITICAL: Validate temperature is reasonable
            if ($request->temperature_celsius && $morningReading->temperature_celsius) {
                $tempChange = abs($request->temperature_celsius - $morningReading->temperature_celsius);
                if ($tempChange > 20) {
                    throw new \Exception('Temperature change of ' . $tempChange . '°C exceeds normal threshold');
                }
            }

            // CRITICAL: Validate against yesterday's evening for continuity
            $yesterday = Carbon::parse($request->reading_date)->subDay()->toDateString();
            $yesterdayEvening = DB::table('daily_readings')
                ->where('tank_id', $request->tank_id)
                ->where('reading_date', $yesterday)
                ->value('evening_dip_liters');

            if ($yesterdayEvening && $morningReading->morning_dip_liters) {
                $overnightVariance = abs($morningReading->morning_dip_liters - $yesterdayEvening);
                if ($overnightVariance > ($yesterdayEvening * 0.05)) {
                    throw new \Exception('Morning reading shows ' . round($overnightVariance, 3) . 'L overnight variance from yesterday evening');
                }
            }

            // Update with evening reading (triggers DATABASE AUTOMATION: tr_dip_update_reconciliation)
            $affected = DB::table('daily_readings')
                ->where('tank_id', $request->tank_id)
                ->where('reading_date', $request->reading_date)
                ->update([
                    'evening_dip_liters' => $request->evening_dip_liters,
                    'water_level_mm' => $request->water_level_mm ?? $morningReading->water_level_mm,
                    'temperature_celsius' => $request->temperature_celsius ?? $morningReading->temperature_celsius,
                    'updated_at' => now()
                ]);

            if ($affected === 0) {
                throw new \Exception('Failed to update evening reading');
            }

            DB::commit();

            $responseData = [
                'success' => true,
                'message' => 'Evening reading recorded. Reconciliation processing automatically.',
                'data' => [
                    'tank_number' => $tank->tank_number,
                    'evening_reading' => $request->evening_dip_liters
                ]
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($responseData);
            }

            return redirect()->back()->with('success', $responseData['message']);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function getPendingTanks(Request $request)
    {
        $user = Auth::user();
        $stationId = $request->get('station_id');
        $readingDate = $request->get('reading_date', Carbon::today()->toDateString());

        if (!$this->hasStationAccess($user, $stationId)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized station access');
        }

        // Get tanks needing evening readings
        $pendingTanks = DB::table('daily_readings as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->where('t.station_id', $stationId)
            ->where('dr.reading_date', $readingDate)
            ->where('dr.morning_dip_liters', '>', 0)
            ->where('dr.evening_dip_liters', '=', 0)
            ->select(
                't.id',
                't.tank_number',
                't.fuel_type',
                't.capacity_liters',
                'dr.morning_dip_liters'
            )
            ->orderBy('t.tank_number')
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'pending_tanks' => $pendingTanks,
                    'reading_date' => $readingDate,
                    'station_id' => $stationId
                ]
            ]);
        }

        return view('daily-evening-dip-readings.pending', compact('pendingTanks', 'readingDate', 'stationId'));
    }

    private function getAuthorizedStations($user)
    {
        if ($user->role === 'admin') {
            return DB::table('stations')
                ->orderBy('name')
                ->get(['id', 'name', 'location']);
        }

        return DB::table('stations')
            ->where('id', $user->station_id)
            ->get(['id', 'name', 'location']);
    }

    private function hasStationAccess($user, $stationId)
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->station_id == $stationId;
    }
}
