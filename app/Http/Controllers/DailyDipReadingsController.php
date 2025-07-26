<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DailyDipReadingsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedStation = $request->get('station_id');

        // Enforce station-level access control
        $stations = $this->getAuthorizedStations($user);

        if ($stations->isEmpty()) {
            return redirect()->back()->with('error', 'No authorized stations found');
        }

        // Default to first authorized station if none selected
        if (!$selectedStation || !$stations->pluck('id')->contains($selectedStation)) {
            $selectedStation = $stations->first()->id;
        }

        // Get tanks for selected station
        $tanks = DB::table('tanks')
            ->where('station_id', $selectedStation)
            ->orderBy('tank_number')
            ->get(['id', 'tank_number', 'fuel_type', 'capacity_liters']);

        // Get today's readings for selected station
        $today = Carbon::today()->toDateString();
        $readings = DB::table('daily_readings as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('users as u', 'dr.recorded_by_user_id', '=', 'u.id')
            ->where('t.station_id', $selectedStation)
            ->where('dr.reading_date', $today)
            ->select('dr.*', 't.tank_number', 't.fuel_type', 'u.first_name', 'u.last_name')
            ->orderBy('t.tank_number')
            ->get();

        return view('daily-dip-readings.index', compact('stations', 'selectedStation', 'tanks', 'readings', 'today'));
    }

    public function storeMorningReading(Request $request)
    {
        $user = Auth::user();

        // Validate request
        $request->validate([
            'station_id' => 'required|integer',
            'tank_id' => 'required|integer',
            'reading_date' => 'required|date|before_or_equal:today|after:' . Carbon::now()->subDays(30)->toDateString(),
            'morning_dip_liters' => 'required|numeric|min:0|max:999999999.999',
            'water_level_mm' => 'nullable|numeric|min:0|max:99999.99'
        ]);

        try {
            DB::beginTransaction();

            // Verify station access
            if (!$this->hasStationAccess($user, $request->station_id)) {
                throw new \Exception('Unauthorized station access');
            }

            // Verify tank belongs to station
            $tank = DB::table('tanks')
                ->where('id', $request->tank_id)
                ->where('station_id', $request->station_id)
                ->first();

            if (!$tank) {
                throw new \Exception('Invalid tank for selected station');
            }

            // Check if morning reading already exists
            $existing = DB::table('daily_readings')
                ->where('tank_id', $request->tank_id)
                ->where('reading_date', $request->reading_date)
                ->first();

            if ($existing) {
                throw new \Exception('Morning reading already recorded for this tank and date');
            }

            // Validate morning dip doesn't exceed tank capacity
            if ($request->morning_dip_liters > $tank->capacity_liters) {
                throw new \Exception('Morning dip reading cannot exceed tank capacity');
            }

            // Insert morning reading
            DB::table('daily_readings')->insert([
                'tank_id' => $request->tank_id,
                'reading_date' => $request->reading_date,
                'morning_dip_liters' => $request->morning_dip_liters,
                'evening_dip_liters' => 0.000,
                'water_level_mm' => $request->water_level_mm,
                'recorded_by_user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            // Handle both AJAX and normal Laravel responses
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Morning reading recorded successfully']);
            }

            return redirect()->back()->with('success', 'Morning reading recorded successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            // Handle both AJAX and normal Laravel responses
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function storeEveningReading(Request $request)
    {
        $user = Auth::user();

        // Validate request
        $request->validate([
            'station_id' => 'required|integer',
            'tank_id' => 'required|integer',
            'reading_date' => 'required|date|before_or_equal:today|after:' . Carbon::now()->subDays(30)->toDateString(),
            'evening_dip_liters' => 'required|numeric|min:0|max:999999999.999',
            'water_level_mm' => 'nullable|numeric|min:0|max:99999.99'
        ]);

        try {
            DB::beginTransaction();

            // Verify station access
            if (!$this->hasStationAccess($user, $request->station_id)) {
                throw new \Exception('Unauthorized station access');
            }

            // Verify tank belongs to station
            $tank = DB::table('tanks')
                ->where('id', $request->tank_id)
                ->where('station_id', $request->station_id)
                ->first();

            if (!$tank) {
                throw new \Exception('Invalid tank for selected station');
            }

            // BUSINESS LOGIC: Evening reading requires existing morning reading
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

            // Update with evening reading (triggers reconciliation automation)
            DB::table('daily_readings')
                ->where('tank_id', $request->tank_id)
                ->where('reading_date', $request->reading_date)
                ->update([
                    'evening_dip_liters' => $request->evening_dip_liters,
                    'water_level_mm' => $request->water_level_mm ?? $morningReading->water_level_mm,
                    'updated_at' => now()
                ]);

            DB::commit();

            // Handle both AJAX and normal Laravel responses
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Evening reading recorded successfully. Reconciliation processing...']);
            }

            return redirect()->back()->with('success', 'Evening reading recorded successfully. Reconciliation processing...');

        } catch (\Exception $e) {
            DB::rollBack();

            // Handle both AJAX and normal Laravel responses
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function getPendingReadings(Request $request)
    {
        $user = Auth::user();
        $stationId = $request->get('station_id');
        $readingDate = $request->get('reading_date', Carbon::today()->toDateString());

        // Verify station access
        if (!$this->hasStationAccess($user, $stationId)) {
            // Handle both AJAX and normal Laravel responses
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized station access');
        }

        // Get tanks needing morning readings
        $morningPending = DB::table('tanks as t')
            ->leftJoin('daily_readings as dr', function($join) use ($readingDate) {
                $join->on('t.id', '=', 'dr.tank_id')
                     ->where('dr.reading_date', '=', $readingDate);
            })
            ->where('t.station_id', $stationId)
            ->whereNull('dr.id')
            ->select('t.id', 't.tank_number', 't.fuel_type')
            ->orderBy('t.tank_number')
            ->get();

        // Get tanks needing evening readings
        $eveningPending = DB::table('tanks as t')
            ->join('daily_readings as dr', 't.id', '=', 'dr.tank_id')
            ->where('t.station_id', $stationId)
            ->where('dr.reading_date', $readingDate)
            ->where('dr.morning_dip_liters', '>', 0)
            ->where('dr.evening_dip_liters', '=', 0)
            ->select('t.id', 't.tank_number', 't.fuel_type', 'dr.morning_dip_liters')
            ->orderBy('t.tank_number')
            ->get();

        $data = [
            'morning_pending' => $morningPending,
            'evening_pending' => $eveningPending
        ];

        // Handle both AJAX and normal Laravel responses
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($data);
        }

        // For normal Laravel requests, you might want to return a view or redirect
        return view('daily-dip-readings.pending', $data);
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
