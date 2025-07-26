<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MeterReadingsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedStation = $request->get('station_id');

        // Enforce station-level access control
        $stations = $this->getAuthorizedStations($user);

        if (empty($stations)) {
            return redirect()->back()->with('error', 'No authorized stations found');
        }

        // Default to first authorized station if none selected
        $stationIds = array_map(function($station) { return $station->id; }, $stations);
        if (!$selectedStation || !in_array($selectedStation, $stationIds)) {
            $selectedStation = $stations[0]->id;
        }

        // Get meters for selected station with tank info
        $meters = DB::table('meters as m')
            ->join('tanks as t', 'm.tank_id', '=', 't.id')
            ->where('t.station_id', $selectedStation)
            ->where('m.is_active', true)
            ->orderBy('m.meter_number')
            ->get(['m.id', 'm.meter_number', 'm.current_reading_liters', 't.tank_number', 't.fuel_type']);

        // Get today's readings for selected station
        $today = Carbon::today()->toDateString();
        $readings = DB::table('meter_readings as mr')
            ->join('meters as m', 'mr.meter_id', '=', 'm.id')
            ->join('tanks as t', 'm.tank_id', '=', 't.id')
            ->join('users as u', 'mr.recorded_by_user_id', '=', 'u.id')
            ->where('t.station_id', $selectedStation)
            ->where('mr.reading_date', $today)
            ->select('mr.*', 'm.meter_number', 't.tank_number', 't.fuel_type', 'u.first_name', 'u.last_name')
            ->orderBy('m.meter_number')
            ->get();

        return view('meter-readings.index', compact('stations', 'selectedStation', 'meters', 'readings', 'today'));
    }

    public function storeMorningReading(Request $request)
    {
        $user = Auth::user();

        // Validate request
        $request->validate([
            'station_id' => 'required|integer',
            'meter_id' => 'required|integer',
            'reading_date' => 'required|date|before_or_equal:today|after:' . Carbon::now()->subDays(30)->toDateString(),
            'opening_reading_liters' => 'required|numeric|min:0|max:999999999.999'
        ]);

        try {
            DB::beginTransaction();

            // Verify station access
            if (!$this->hasStationAccess($user, $request->station_id)) {
                throw new \Exception('Unauthorized station access');
            }

            // Verify meter belongs to station and is active
            $meter = DB::table('meters as m')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->where('m.id', $request->meter_id)
                ->where('t.station_id', $request->station_id)
                ->where('m.is_active', true)
                ->first(['m.id', 'm.meter_number', 'm.current_reading_liters', 't.tank_number']);

            if (!$meter) {
                throw new \Exception('Invalid or inactive meter for selected station');
            }

            // Check if morning reading already exists
            $existing = DB::table('meter_readings')
                ->where('meter_id', $request->meter_id)
                ->where('reading_date', $request->reading_date)
                ->first();

            if ($existing) {
                throw new \Exception('Morning reading already recorded for this meter and date');
            }

            // Validate meter progression (business rule - readings must increase)
            $lastReading = DB::table('meter_readings')
                ->where('meter_id', $request->meter_id)
                ->orderBy('reading_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastReading && $request->opening_reading_liters < $lastReading->closing_reading_liters) {
                // Check if this is a legitimate meter reset (near capacity threshold)
                $resetThreshold = 999999999.0; // Near max capacity
                if ($lastReading->closing_reading_liters > ($resetThreshold * 0.95) && $request->opening_reading_liters < 1000) {
                    // Legitimate reset - allow but log
                    // This will be handled by database trigger tr_validate_meter_progression
                } else {
                    throw new \Exception('Opening reading cannot be less than previous closing reading (meter fraud prevention)');
                }
            }

            // Insert morning reading with temporary closing reading (same as opening)
            DB::table('meter_readings')->insert([
                'meter_id' => $request->meter_id,
                'reading_date' => $request->reading_date,
                'opening_reading_liters' => $request->opening_reading_liters,
                'closing_reading_liters' => $request->opening_reading_liters, // Temporary - will be updated with evening reading
                'recorded_by_user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Morning reading recorded successfully']);
            }

            return redirect()->back()->with('success', 'Morning reading recorded successfully');

        } catch (\Exception $e) {
            DB::rollBack();

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
            'meter_id' => 'required|integer',
            'reading_date' => 'required|date|before_or_equal:today|after:' . Carbon::now()->subDays(30)->toDateString(),
            'closing_reading_liters' => 'required|numeric|min:0|max:999999999.999'
        ]);

        try {
            DB::beginTransaction();

            // Verify station access
            if (!$this->hasStationAccess($user, $request->station_id)) {
                throw new \Exception('Unauthorized station access');
            }

            // Verify meter belongs to station and is active
            $meter = DB::table('meters as m')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->where('m.id', $request->meter_id)
                ->where('t.station_id', $request->station_id)
                ->where('m.is_active', true)
                ->first(['m.id', 'm.meter_number', 'm.current_reading_liters', 't.tank_number']);

            if (!$meter) {
                throw new \Exception('Invalid or inactive meter for selected station');
            }

            // Get morning reading (business rule - evening requires morning)
            $morningReading = DB::table('meter_readings')
                ->where('meter_id', $request->meter_id)
                ->where('reading_date', $request->reading_date)
                ->first();

            if (!$morningReading) {
                throw new \Exception('Morning reading must be recorded before evening reading');
            }

            // Check if evening reading already recorded (closing != opening)
            if ($morningReading->closing_reading_liters != $morningReading->opening_reading_liters) {
                throw new \Exception('Evening reading already recorded for this meter and date');
            }

            // Validate evening reading progression
            if ($request->closing_reading_liters < $morningReading->opening_reading_liters) {
                throw new \Exception('Closing reading cannot be less than opening reading');
            }

            // Update with evening reading (triggers FIFO automation)
            DB::table('meter_readings')
                ->where('meter_id', $request->meter_id)
                ->where('reading_date', $request->reading_date)
                ->update([
                    'closing_reading_liters' => $request->closing_reading_liters,
                    'updated_at' => now()
                ]);

            // Update meter current reading
            DB::table('meters')
                ->where('id', $request->meter_id)
                ->update([
                    'current_reading_liters' => $request->closing_reading_liters,
                    'updated_at' => now()
                ]);

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Evening reading recorded successfully. FIFO processing triggered automatically.']);
            }

            return redirect()->back()->with('success', 'Evening reading recorded successfully. FIFO processing triggered automatically.');

        } catch (\Exception $e) {
            DB::rollBack();

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
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized station access');
        }

        // Get meters needing morning readings
        $morningPending = DB::table('meters as m')
            ->join('tanks as t', 'm.tank_id', '=', 't.id')
            ->leftJoin('meter_readings as mr', function($join) use ($readingDate) {
                $join->on('m.id', '=', 'mr.meter_id')
                     ->where('mr.reading_date', '=', $readingDate);
            })
            ->where('t.station_id', $stationId)
            ->where('m.is_active', true)
            ->whereNull('mr.id')
            ->select('m.id', 'm.meter_number', 'm.current_reading_liters', 't.tank_number', 't.fuel_type')
            ->orderBy('m.meter_number')
            ->get();

        // Get meters needing evening readings
        $eveningPending = DB::table('meters as m')
            ->join('tanks as t', 'm.tank_id', '=', 't.id')
            ->join('meter_readings as mr', 'm.id', '=', 'mr.meter_id')
            ->where('t.station_id', $stationId)
            ->where('m.is_active', true)
            ->where('mr.reading_date', $readingDate)
            ->whereColumn('mr.opening_reading_liters', '=', 'mr.closing_reading_liters')
            ->select('m.id', 'm.meter_number', 't.tank_number', 't.fuel_type', 'mr.opening_reading_liters')
            ->orderBy('m.meter_number')
            ->get();

        $data = [
            'morning_pending' => $morningPending,
            'evening_pending' => $eveningPending
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($data);
        }

        return view('meter-readings.pending', $data);
    }

    private function getAuthorizedStations($user)
    {
        if ($user->role === 'admin') {
            return DB::table('stations')
                ->orderBy('name')
                ->get(['id', 'name', 'location'])
                ->toArray();
        }

        return DB::table('stations')
            ->where('id', $user->station_id)
            ->get(['id', 'name', 'location'])
            ->toArray();
    }

    private function hasStationAccess($user, $stationId)
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->station_id == $stationId;
    }
}
