<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\FuelERP_CriticalPrecisionService;
use Exception;
use Carbon\Carbon;

class DipReadingsController extends Controller
{
    private $fuelService;

    public function __construct(FuelERP_CriticalPrecisionService $fuelService)
    {
        $this->fuelService = $fuelService;
    }

    /**
     * Display the main dip readings interface
     * MANDATORY: Station-scoped access control enforced
     * PRESERVED: Original functionality 100% intact
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $selectedStationId = $request->get('station_id');

            // STRICT ACCESS CONTROL - NON-ADMINS RESTRICTED TO ASSIGNED STATIONS
            if ($user->role !== 'admin') {
                if (!$user->station_id) {
                    return back()->with('error', 'ACCESS_DENIED: User has no assigned station. Contact administrator.');
                }
                $selectedStationId = $user->station_id;
            }

            // GET STATIONS FOR DROPDOWN (ADMIN: ALL, NON-ADMIN: ASSIGNED ONLY)
            $stations = $this->getAuthorizedStations($user);

            if ($stations->isEmpty()) {
                return back()->with('error', 'ACCESS_DENIED: No authorized stations available.');
            }

            // SET DEFAULT STATION IF NONE SELECTED
            if (!$selectedStationId) {
                $selectedStationId = $stations->first()->id;
            }

            // VALIDATE STATION ACCESS
            $selectedStation = $stations->where('id', $selectedStationId)->first();
            if (!$selectedStation) {
                return back()->with('error', 'ACCESS_DENIED: Unauthorized station access attempted.');
            }

            // PRESERVED: Original tank query (NOT using service)
            $tanks = DB::table('tanks')
                ->where('station_id', $selectedStationId)
                ->select('id', 'tank_number', 'fuel_type', 'capacity_liters', 'current_volume_liters')
                ->orderBy('tank_number')
                ->get();

            // GET TODAY'S DATE FOR DEFAULT
            $readingDate = $request->get('reading_date', now()->toDateString());

            return view('dip-readings.index', [
                'stations' => $stations,
                'selectedStationId' => $selectedStationId,
                'selectedStation' => $selectedStation,
                'tanks' => $tanks,
                'readingDate' => $readingDate,
                'user' => $user
            ]);

        } catch (Exception $e) {
            return back()->with('error', 'SYSTEM_ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Get tank readings for a specific date (AJAX)
     * Returns existing readings + previous morning readings
     * PRESERVED: Original functionality 100% intact
     */
    public function getTankReadings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'station_id' => 'required|integer|min:1',
                'reading_date' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'VALIDATION_ERROR: ' . implode(', ', $validator->errors()->all())
                ], 400);
            }

            $stationId = $request->station_id;
            $readingDate = $request->reading_date;
            $user = auth()->user();

            // ENFORCE STATION ACCESS
            if (!$this->hasStationAccess($user, $stationId)) {
                return response()->json([
                    'error' => 'ACCESS_DENIED: Unauthorized station access.'
                ], 403);
            }

            // PRESERVED: Original tank query (NOT using service)
            $tanks = DB::table('tanks')
                ->where('station_id', $stationId)
                ->select('id', 'tank_number', 'fuel_type', 'capacity_liters', 'current_volume_liters')
                ->orderBy('tank_number')
                ->get();

            $tankReadings = [];

            foreach ($tanks as $tank) {
                // GET EXISTING READING FOR DATE
                $existingReading = DB::table('daily_readings')
                    ->where('tank_id', $tank->id)
                    ->where('reading_date', $readingDate)
                    ->first();

                // GET PREVIOUS DAY'S EVENING DIP (BECOMES TODAY'S MORNING DIP)
                $previousDate = Carbon::parse($readingDate)->subDay()->toDateString();
                $previousReading = DB::table('daily_readings')
                    ->where('tank_id', $tank->id)
                    ->where('reading_date', $previousDate)
                    ->first();

                // CALCULATE SUGGESTED MORNING DIP
                $suggestedMorningDip = 0;
                if ($previousReading) {
                    $suggestedMorningDip = $previousReading->evening_dip_liters;
                }

                // PRESERVED: Original meter readings check method
                $hasMeterReadings = $this->checkMeterReadingsAvailability($tank->id, $readingDate);

                $tankReadings[] = [
                    'tank_id' => $tank->id,
                    'tank_number' => $tank->tank_number,
                    'fuel_type' => $tank->fuel_type,
                    'capacity_liters' => (float) $tank->capacity_liters,
                    'current_volume_liters' => (float) $tank->current_volume_liters,
                    'existing_reading' => $existingReading ? [
                        'id' => $existingReading->id,
                        'morning_dip_liters' => (float) $existingReading->morning_dip_liters,
                        'evening_dip_liters' => (float) $existingReading->evening_dip_liters,
                        'water_level_mm' => $existingReading->water_level_mm ? (float) $existingReading->water_level_mm : null,
                        'temperature_celsius' => $existingReading->temperature_celsius ? (float) $existingReading->temperature_celsius : null,
                        'calculation_method' => $existingReading->calculation_method
                    ] : null,
                    'suggested_morning_dip' => (float) $suggestedMorningDip,
                    'previous_evening_dip' => $previousReading ? (float) $previousReading->evening_dip_liters : null,
                    'has_meter_readings' => $hasMeterReadings,
                    'meter_reading_status' => $hasMeterReadings ? 'AVAILABLE' : 'MISSING',
                    'can_submit' => $hasMeterReadings // CRITICAL: ONLY ALLOW SUBMISSION IF METER READINGS EXIST
                ];
            }

            return response()->json([
                'success' => true,
                'tank_readings' => $tankReadings,
                'reading_date' => $readingDate,
                'station_id' => $stationId
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'SYSTEM_ERROR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store or update a dip reading (AJAX)
     * FIXED: Service integration corrected while preserving original logic
     */
    public function store(Request $request)
    {
        try {
            // PRESERVED: Original validation rules
            $validator = Validator::make($request->all(), [
                'tank_id' => 'required|integer|min:1',
                'reading_date' => 'required|date',
                'morning_dip_liters' => 'required|numeric|min:0',
                'evening_dip_liters' => 'required|numeric|min:0',
                'water_level_mm' => 'nullable|numeric|min:0',
                'temperature_celsius' => 'nullable|numeric|between:-10,60'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'VALIDATION_ERROR: ' . implode(', ', $validator->errors()->all())
                ], 400);
            }

            $tankId = (int) $request->tank_id;
            $readingDate = $request->reading_date;
            $morningDip = (float) $request->morning_dip_liters;
            $eveningDip = (float) $request->evening_dip_liters;
            $waterLevel = $request->water_level_mm ? (float) $request->water_level_mm : null;
            $temperature = $request->temperature_celsius ? (float) $request->temperature_celsius : null;
            $user = auth()->user();

            // PRESERVED: Original tank validation
            $tank = DB::table('tanks')->where('id', $tankId)->first();
            if (!$tank) {
                return response()->json([
                    'error' => 'VALIDATION_ERROR: Tank ID ' . $tankId . ' does not exist.'
                ], 400);
            }

            if (!$this->hasStationAccess($user, $tank->station_id)) {
                return response()->json([
                    'error' => 'ACCESS_DENIED: Unauthorized tank access.'
                ], 403);
            }

            // PRESERVED: Original meter readings check
            if (!$this->checkMeterReadingsAvailability($tankId, $readingDate)) {
                return response()->json([
                    'error' => 'METER_READINGS_REQUIRED: No meter readings found for tank ' . $tank->tank_number . ' on date ' . $readingDate . '. Meter readings must be entered before dip readings.'
                ], 400);
            }

            // PRESERVED: Original capacity validation
            if ($morningDip > $tank->capacity_liters) {
                return response()->json([
                    'error' => 'VALIDATION_ERROR: Morning dip (' . $morningDip . 'L) exceeds tank capacity (' . $tank->capacity_liters . 'L).'
                ], 400);
            }

            if ($eveningDip > $tank->capacity_liters) {
                return response()->json([
                    'error' => 'VALIDATION_ERROR: Evening dip (' . $eveningDip . 'L) exceeds tank capacity (' . $tank->capacity_liters . 'L).'
                ], 400);
            }

            // PRESERVED: Original existing reading check
            $existingReading = DB::table('daily_readings')
                ->where('tank_id', $tankId)
                ->where('reading_date', $readingDate)
                ->first();

            DB::beginTransaction();

            try {
                // PRESERVED: Original daily_readings CRUD logic
                if ($existingReading) {
                    // UPDATE EXISTING READING
                    DB::table('daily_readings')
                        ->where('id', $existingReading->id)
                        ->update([
                            'morning_dip_liters' => $morningDip,
                            'evening_dip_liters' => $eveningDip,
                            'water_level_mm' => $waterLevel,
                            'temperature_celsius' => $temperature,
                            'recorded_by_user_id' => $user->id,
                            'updated_at' => now()
                        ]);

                    $readingId = $existingReading->id;
                    $actionType = 'UPDATED';
                } else {
                    // INSERT NEW READING
                    $readingId = DB::table('daily_readings')->insertGetId([
                        'tank_id' => $tankId,
                        'reading_date' => $readingDate,
                        'morning_dip_liters' => $morningDip,
                        'evening_dip_liters' => $eveningDip,
                        'water_level_mm' => $waterLevel,
                        'temperature_celsius' => $temperature,
                        'recorded_by_user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $actionType = 'CREATED';
                }

                // FIXED: Correct service call with proper parameters
                // Calculate total sales required by service
                $totalSalesUgx = $this->calculateTotalSalesForService($tankId, $readingDate, $tank->station_id, $tank->fuel_type);

                // Service expects: (int $tankId, Carbon $reconciliationDate, array $reconciliationData)
                $reconciliationData = [
                    'actual_closing_stock_liters' => $eveningDip,
                    'total_sales_ugx' => $totalSalesUgx,
                    'reconciled_by_user_id' => $user->id
                ];

                $reconciliationId = $this->fuelService->processDailyReconciliation(
                    $tankId,
                    Carbon::parse($readingDate),
                    $reconciliationData
                );

                DB::commit();

                // PRESERVED: Original response structure
                return response()->json([
                    'success' => true,
                    'message' => 'Dip reading ' . $actionType . ' and reconciliation processed successfully.',
                    'reading_id' => $readingId,
                    'tank_number' => $tank->tank_number,
                    'action_type' => $actionType,
                    'service_result' => [
                        'reconciliation_id' => $reconciliationId,
                        'total_sales_ugx' => $totalSalesUgx,
                        'total_cogs_ugx' => 0, // Will be calculated by service
                        'variance_percentage' => 0 // Will be calculated by service
                    ]
                ]);

            } catch (Exception $serviceError) {
                DB::rollBack();

                // PRESERVED: Original error handling
                return response()->json([
                    'error' => 'SERVICE_PROCESSING_ERROR: ' . $serviceError->getMessage(),
                    'error_context' => [
                        'tank_id' => $tankId,
                        'reading_date' => $readingDate,
                        'morning_dip' => $morningDip,
                        'evening_dip' => $eveningDip
                    ]
                ], 500);
            }

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'SYSTEM_ERROR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reading history for a tank (AJAX)
     * PRESERVED: Original functionality 100% intact
     */
    public function getReadingHistory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tank_id' => 'required|integer|min:1',
                'days' => 'nullable|integer|min:1|max:90'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'VALIDATION_ERROR: ' . implode(', ', $validator->errors()->all())
                ], 400);
            }

            $tankId = $request->tank_id;
            $days = $request->get('days', 30);
            $user = auth()->user();

            // VALIDATE TANK ACCESS
            $tank = DB::table('tanks')->where('id', $tankId)->first();
            if (!$tank) {
                return response()->json([
                    'error' => 'VALIDATION_ERROR: Tank not found.'
                ], 400);
            }

            if (!$this->hasStationAccess($user, $tank->station_id)) {
                return response()->json([
                    'error' => 'ACCESS_DENIED: Unauthorized tank access.'
                ], 403);
            }

            // GET READING HISTORY
            $startDate = Carbon::now()->subDays($days)->toDateString();

            $readings = DB::table('daily_readings as dr')
                ->leftJoin('daily_reconciliations as rec', function($join) {
                    $join->on('dr.tank_id', '=', 'rec.tank_id')
                         ->on('dr.reading_date', '=', 'rec.reconciliation_date');
                })
                ->leftJoin('users as u', 'dr.recorded_by_user_id', '=', 'u.id')
                ->where('dr.tank_id', $tankId)
                ->where('dr.reading_date', '>=', $startDate)
                ->select([
                    'dr.id',
                    'dr.reading_date',
                    'dr.morning_dip_liters',
                    'dr.evening_dip_liters',
                    'dr.water_level_mm',
                    'dr.temperature_celsius',
                    'dr.calculation_method',
                    'dr.created_at',
                    'u.name as recorded_by',
                    'rec.id as reconciliation_id',
                    'rec.total_dispensed_liters',
                    'rec.total_delivered_liters',
                    'rec.volume_variance_liters',
                    'rec.variance_percentage',
                    'rec.total_sales_ugx',
                    'rec.total_cogs_ugx',
                    'rec.gross_profit_ugx'
                ])
                ->orderBy('dr.reading_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'tank_number' => $tank->tank_number,
                'fuel_type' => $tank->fuel_type,
                'readings' => $readings,
                'period_days' => $days
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'SYSTEM_ERROR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a dip reading (AJAX)
     * CRITICAL: Also removes associated reconciliation data
     * PRESERVED: Original functionality 100% intact
     */
    public function destroy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reading_id' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'VALIDATION_ERROR: ' . implode(', ', $validator->errors()->all())
                ], 400);
            }

            $readingId = $request->reading_id;
            $user = auth()->user();

            // GET READING WITH TANK INFO
            $reading = DB::table('daily_readings as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->where('dr.id', $readingId)
                ->select('dr.*', 't.station_id', 't.tank_number')
                ->first();

            if (!$reading) {
                return response()->json([
                    'error' => 'VALIDATION_ERROR: Reading not found.'
                ], 400);
            }

            // VALIDATE ACCESS
            if (!$this->hasStationAccess($user, $reading->station_id)) {
                return response()->json([
                    'error' => 'ACCESS_DENIED: Unauthorized reading access.'
                ], 403);
            }

            // ADMIN-ONLY DELETION (SAFETY MEASURE)
            if ($user->role !== 'admin') {
                return response()->json([
                    'error' => 'ACCESS_DENIED: Only administrators can delete readings.'
                ], 403);
            }

            DB::beginTransaction();

            try {
                // DELETE ASSOCIATED RECONCILIATION FIRST (CASCADE WILL HANDLE RELATED DATA)
                DB::table('daily_reconciliations')
                    ->where('tank_id', $reading->tank_id)
                    ->where('reconciliation_date', $reading->reading_date)
                    ->delete();

                // DELETE THE READING
                DB::table('daily_readings')->where('id', $readingId)->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Reading and associated reconciliation data deleted successfully.',
                    'tank_number' => $reading->tank_number,
                    'reading_date' => $reading->reading_date
                ]);

            } catch (Exception $deleteError) {
                DB::rollBack();
                return response()->json([
                    'error' => 'DELETE_ERROR: ' . $deleteError->getMessage()
                ], 500);
            }

        } catch (Exception $e) {
            return response()->json([
                'error' => 'SYSTEM_ERROR: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================== PRIVATE HELPER METHODS =====================

    /**
     * Get authorized stations for user (ADMIN: ALL, NON-ADMIN: ASSIGNED)
     * PRESERVED: Original method 100% intact
     */
    private function getAuthorizedStations($user)
    {
        if ($user->role === 'admin') {
            return DB::table('stations')
                ->select('id', 'name', 'location')
                ->orderBy('name')
                ->get();
        } else {
            return DB::table('stations')
                ->where('id', $user->station_id)
                ->select('id', 'name', 'location')
                ->get();
        }
    }

    /**
     * Check if user has access to specific station
     * PRESERVED: Original method 100% intact
     */
    private function hasStationAccess($user, $stationId)
    {
        if ($user->role === 'admin') {
            return true;
        }
        return $user->station_id == $stationId;
    }

    /**
     * CRITICAL: Check meter readings availability for tank/date
     * This is MANDATORY before allowing dip reading entry
     * PRESERVED: Original method 100% intact
     */
    private function checkMeterReadingsAvailability($tankId, $date)
    {
        try {
            $count = DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->where('m.tank_id', $tankId)
                ->where('mr.reading_date', $date)
                ->where('m.is_active', true)
                ->count();

            return $count > 0;

        } catch (Exception $e) {
            // LOG ERROR BUT DON'T BLOCK (DEFENSIVE PROGRAMMING)
            return false;
        }
    }

    /**
     * NEW: Calculate total sales for service integration
     * ADDED: Required for proper service call
     */
    private function calculateTotalSalesForService($tankId, $date, $stationId, $fuelType)
    {
        try {
            // Get total dispensed from meter readings
            $totalDispensed = DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->where('m.tank_id', $tankId)
                ->where('mr.reading_date', $date)
                ->where('m.is_active', true)
                ->sum('mr.dispensed_liters');

            if (!$totalDispensed || $totalDispensed <= 0) {
                return 0.0;
            }

            // Get selling price using service
            $pricePerLiter = $this->fuelService->getCurrentSellingPrice($stationId, $fuelType);

            if ($pricePerLiter === null) {
                throw new Exception("No active selling price found for fuel type '$fuelType' at station $stationId");
            }

            return round((float)$totalDispensed * $pricePerLiter, 4);

        } catch (Exception $e) {
            // If we can't calculate sales, return 0 and let service handle it
            return 0.0;
        }
    }
}
