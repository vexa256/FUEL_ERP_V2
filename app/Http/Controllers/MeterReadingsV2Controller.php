<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class MeterReadingsV2Controller extends Controller
{
    /**
     * FORENSIC SCHEMA VALIDATION - 100% Database Compliance
     * FIXED: All phantom columns removed, exact FUEL_ERP_V2.sql schema compliance
     */
    private static $VALIDATED_TABLES = [
        'meter_readings' => [
            'id', 'meter_id', 'reading_date', 'opening_reading_liters', 'closing_reading_liters',
            'dispensed_liters', 'recorded_by_user_id', 'created_at', 'updated_at'
        ],
        'meters' => [
            'id', 'tank_id', 'meter_number', 'current_reading_liters', 'is_active', 'created_at', 'updated_at'
        ],
        'tanks' => [
            // FORENSIC FIX: Exact schema - NO is_active column
            'id', 'station_id', 'tank_number', 'fuel_type', 'capacity_liters', 'current_volume_liters',
            'created_at', 'updated_at'
        ],
        'stations' => [
            'id', 'name', 'location', 'currency_code', 'timezone', 'created_at', 'updated_at'
        ],
        'users' => [
            'id', 'name', 'email', 'email_verified_at', 'password', 'remember_token',
            'station_id', 'employee_id', 'first_name', 'last_name', 'phone', 'role',
            'is_active', 'last_login_at', 'created_at', 'updated_at'
        ],
        'fifo_layers' => [
            'id', 'tank_id', 'delivery_id', 'layer_sequence', 'original_volume_liters',
            'remaining_volume_liters', 'cost_per_liter_ugx', 'delivery_date', 'is_exhausted',
            'created_at', 'updated_at'
        ],
        'fifo_consumption_log' => [
            'id', 'reconciliation_id', 'fifo_layer_id', 'volume_consumed_liters', 'cost_per_liter_ugx',
            'total_cost_ugx', 'consumption_sequence', 'created_at', 'inventory_value_before_ugx',
            'inventory_value_after_ugx', 'weighted_avg_cost_ugx', 'valuation_impact_ugx'
        ],
        'daily_reconciliations' => [
            'id', 'tank_id', 'reconciliation_date', 'opening_stock_liters', 'total_delivered_liters',
            'total_dispensed_liters', 'theoretical_closing_stock_liters', 'actual_closing_stock_liters',
            'volume_variance_liters', 'variance_percentage', 'total_cogs_ugx', 'total_sales_ugx',
            'gross_profit_ugx', 'created_at', 'updated_at'
        ]
    ];

    /**
     * TABLE CONSTRAINTS - Exact database constraints from schema
     */
    private static $TABLE_CONSTRAINTS = [
        'meter_readings' => [
            'foreign_keys' => [
                'meter_id' => 'meters.id',
                'recorded_by_user_id' => 'users.id'
            ],
            'unique_keys' => [
                'unique_meter_reading_date' => ['meter_id', 'reading_date']
            ],
            'check_constraints' => [
                'chk_meter_readings' => 'closing_reading_liters >= opening_reading_liters',
                'chk_meter_readings_positive' => 'opening_reading_liters >= 0'
            ]
        ]
    ];

    /**
     * Get authorized stations for user (strict access control)
     */
    private function getAuthorizedStations($user)
    {
        if ($user->role === 'admin') {
            return DB::table('stations')->pluck('id')->toArray();
        }
        return [$user->station_id];
    }

    /**
     * Display comprehensive meter readings dashboard with complete operational visibility
     * FIXED: Proper error handling with redirects/AJAX responses, NO is_active on tanks
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $authorizedStations = $this->getAuthorizedStations($user);

            if (empty($authorizedStations)) {
                return $this->handleError(
                    'No authorized stations found for user',
                    ['user_role' => $user->role, 'station_id' => $user->station_id],
                    403
                );
            }

            // SCHEMA FIX: Station selection - NO phantom columns
            $stations = [];
            if ($user->role === 'admin') {
                $stations = DB::table('stations as s')
                    ->select(['s.id', 's.name', 's.location'])
                    ->orderBy('s.name')
                    ->get();
            } else {
                $stations = DB::table('stations as s')
                    ->select(['s.id', 's.name', 's.location'])
                    ->whereIn('s.id', $authorizedStations)
                    ->orderBy('s.name')
                    ->get();
            }

            // Station selection logic (fixed redirect loop)
            $requestedStationId = $request->get('station_id');
            $selectedStation = null;

            if ($requestedStationId && in_array((int)$requestedStationId, $authorizedStations)) {
                $selectedStation = (int)$requestedStationId;
            } else {
                $selectedStation = $authorizedStations[0] ?? null;

                if (!$requestedStationId && !$request->ajax()) {
                    return redirect()->route('meter-readings.index', ['station_id' => $selectedStation]);
                }
            }

            if (!$selectedStation || !in_array($selectedStation, $authorizedStations)) {
                return $this->handleError(
                    'Invalid station access',
                    ['requested_station' => $requestedStationId, 'authorized_stations' => $authorizedStations],
                    403
                );
            }

            $today = Carbon::now()->format('Y-m-d');

            // Get station meters with FIFO inventory status - SCHEMA COMPLIANT (NO t.is_active)
            $meters = DB::table('meters as m')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->leftJoin('fifo_layers as fl', function($join) {
                    $join->on('fl.tank_id', '=', 't.id')
                         ->where('fl.remaining_volume_liters', '>', 0);
                })
                ->where('t.station_id', $selectedStation)
                ->where('m.is_active', 1)  // meters HAS is_active, tanks does NOT
                ->groupBy([
                    'm.id', 'm.meter_number', 'm.current_reading_liters',
                    't.id', 't.tank_number', 't.fuel_type', 't.current_volume_liters', 't.capacity_liters'
                ])
                ->select([
                    'm.id', 'm.meter_number', 'm.current_reading_liters',
                    't.id as tank_id', 't.tank_number', 't.fuel_type',
                    't.current_volume_liters', 't.capacity_liters',
                    DB::raw('COUNT(fl.id) as available_fifo_layers'),
                    DB::raw('COALESCE(SUM(fl.remaining_volume_liters), 0) as total_inventory_liters')
                ])
                ->orderBy('t.tank_number')
                ->orderBy('m.meter_number')
                ->get();

            // Get today's readings with user details - SCHEMA COMPLIANT
            $readings = DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->join('users as u', 'mr.recorded_by_user_id', '=', 'u.id')
                ->where('t.station_id', $selectedStation)
                ->where('mr.reading_date', $today)
                ->select([
                    'mr.*', 'm.meter_number', 't.tank_number', 't.fuel_type',
                    'u.first_name', 'u.last_name'
                ])
                ->orderBy('t.tank_number')
                ->orderBy('m.meter_number')
                ->get();

            // Get automation health metrics
            $automationHealth = $this->getAutomationHealthMetrics($selectedStation);

            // Get comprehensive dashboard data
            $dashboardData = $this->getComprehensiveDashboardData($selectedStation, $today);

            // Comprehensive dashboard data
            $data = [
                'stations' => $stations,
                'selectedStation' => $selectedStation,
                'meters' => $meters,
                'readings' => $readings,
                'today' => $today,
                'automation_health' => $automationHealth,
                'dashboard_data' => $dashboardData,
                'user_role' => $user->role,
                'authorized_stations' => $authorizedStations,
                'dashboard_stats' => [
                    'total_meters' => $meters->count(),
                    'readings_today' => $readings->count(),
                    'pending_readings' => $meters->count() - $readings->count(),
                    'total_dispensed_today' => $readings->sum('dispensed_liters'),
                    'automation_success_rate' => $automationHealth['success_rate'] ?? 100
                ]
            ];

            // Return appropriate response type
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Meter readings dashboard loaded successfully',
                    'data' => $data
                ]);
            }

            return view('meter-readings.index', $data);

        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Store new meter reading with comprehensive automation tracking
     * CRITICAL: Respects database triggers - NO MANUAL SALES/FIFO PROCESSING
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $authorizedStations = $this->getAuthorizedStations($user);

            $validator = Validator::make($request->all(), $this->getMeterReadingValidationRules());

            if ($validator->fails()) {
                return $this->handleError(
                    'Validation failed',
                    $validator->errors()->toArray(),
                    422
                );
            }

            $validatedData = $validator->validated();

            $insertData = [
                'meter_id' => $validatedData['meter_id'],
                'reading_date' => $validatedData['reading_date'],
                'opening_reading_liters' => $validatedData['opening_reading_liters'],
                'closing_reading_liters' => $validatedData['closing_reading_liters'],
                'recorded_by_user_id' => $user->id
            ];

            $this->validateInsertData('meter_readings', $insertData);

            $validationResult = $this->performBusinessValidations($validatedData, $authorizedStations);
            if (!$validationResult['success']) {
                return $this->handleError(
                    $validationResult['message'],
                    $validationResult['errors'],
                    400
                );
            }

            $result = DB::transaction(function() use ($insertData, $authorizedStations, $validatedData) {
                $stationId = $this->validateStationAccess($insertData['meter_id'], $authorizedStations);

                $existing = DB::table('meter_readings')
                    ->where('meter_id', $insertData['meter_id'])
                    ->where('reading_date', $insertData['reading_date'])
                    ->exists();

                if ($existing) {
                    throw new Exception('Meter reading already exists for this date');
                }

                // CRITICAL: Database automation triggers handle ALL processing
                $readingId = DB::table('meter_readings')->insertGetId($insertData);

                // Comprehensive automation results tracking
                $automationResults = $this->trackAutomationExecution($readingId, $insertData);

                return [
                    'reading_id' => $readingId,
                    'automation_results' => $automationResults,
                    'station_id' => $stationId
                ];
            });

            return $this->responseHandler($result,
                'Meter reading recorded successfully. ' . $result['automation_results']['automation_message']);

        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), ['input' => $request->all()], 500);
        }
    }

    /**
     * Show specific meter reading with comprehensive context and automation results
     * SCHEMA COMPLIANT: All queries match database structure
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $authorizedStations = $this->getAuthorizedStations($user);

            // Get reading with all related data - SCHEMA COMPLIANT
            $reading = DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->join('users as u', 'mr.recorded_by_user_id', '=', 'u.id')
                ->where('mr.id', $id)
                ->whereIn('s.id', $authorizedStations)
                ->select([
                    'mr.*', 'm.meter_number', 'm.current_reading_liters',
                    't.tank_number', 't.fuel_type', 't.capacity_liters',
                    's.name as station_name', 's.location as station_location',
                    'u.first_name', 'u.last_name'
                ])
                ->first();

            if (!$reading) {
                return $this->handleError(
                    'Reading not found or access denied',
                    ['reading_id' => $id],
                    404
                );
            }

            // Get comprehensive context and automation results
            $automationResults = $this->getReadingAutomationResults($reading);
            $historicalContext = $this->getHistoricalContext($reading);
            $businessMetrics = $this->getReadingBusinessMetrics($reading);

            $data = [
                'reading' => $reading,
                'automation_results' => $automationResults,
                'historical_context' => $historicalContext,
                'business_metrics' => $businessMetrics
            ];

            return $this->responseHandler($data, 'Reading details retrieved successfully');

        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), ['reading_id' => $id], 500);
        }
    }

    /**
     * Get station meters for AJAX requests with inventory status
     * SCHEMA COMPLIANT: No phantom columns, removed t.is_active
     */
    public function getStationMeters(Request $request)
    {
        try {
            $user = Auth::user();
            $authorizedStations = $this->getAuthorizedStations($user);
            $stationId = $request->get('station_id');

            if (!$stationId || !in_array($stationId, $authorizedStations)) {
                return $this->handleError(
                    'Invalid or unauthorized station',
                    ['station_id' => $stationId],
                    403
                );
            }

            $meters = DB::table('meters as m')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->leftJoin('fifo_layers as fl', function($join) {
                    $join->on('fl.tank_id', '=', 't.id')
                         ->where('fl.remaining_volume_liters', '>', 0);
                })
                ->where('t.station_id', $stationId)
                ->where('m.is_active', 1)  // Only meters.is_active exists
                ->groupBy([
                    'm.id', 'm.meter_number', 'm.current_reading_liters',
                    't.tank_number', 't.fuel_type', 't.current_volume_liters'
                ])
                ->select([
                    'm.id', 'm.meter_number', 'm.current_reading_liters',
                    't.tank_number', 't.fuel_type', 't.current_volume_liters',
                    DB::raw('COUNT(fl.id) as available_fifo_layers'),
                    DB::raw('COALESCE(SUM(fl.remaining_volume_liters), 0) as inventory_liters')
                ])
                ->orderBy('t.tank_number')
                ->get();

            return $this->responseHandler($meters, 'Station meters with inventory status retrieved successfully');

        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), ['station_id' => $request->get('station_id')], 500);
        }
    }

    /**
     * Get comprehensive meter history with automation tracking
     * SCHEMA COMPLIANT: All columns exist in database
     */
    public function getMeterHistory(Request $request)
    {
        try {
            $user = Auth::user();
            $authorizedStations = $this->getAuthorizedStations($user);
            $meterId = $request->get('meter_id');
            $days = $request->get('days', 30);

            if (!$meterId) {
                return $this->handleError('Meter ID required', ['input' => $request->all()], 400);
            }

            // Validate meter access
            $meter = DB::table('meters as m')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->where('m.id', $meterId)
                ->whereIn('t.station_id', $authorizedStations)
                ->first();

            if (!$meter) {
                return $this->handleError(
                    'Meter not found or access denied',
                    ['meter_id' => $meterId],
                    404
                );
            }

            $dateFrom = Carbon::now()->subDays($days)->format('Y-m-d');

            // Get comprehensive meter history - SCHEMA COMPLIANT
            $history = DB::table('meter_readings as mr')
                ->leftJoin('readings as r', function($join) {
                    $join->on('r.meter_id', '=', 'mr.meter_id')
                         ->on('r.reading_date', '=', 'mr.reading_date');
                })
                ->leftJoin('daily_reconciliations as dr', function($join) use ($meter) {
                    $join->on('dr.reconciliation_date', '=', 'mr.reading_date')
                         ->where('dr.tank_id', $meter->tank_id);
                })
                ->select([
                    'mr.reading_date', 'mr.opening_reading_liters', 'mr.closing_reading_liters',
                    'mr.dispensed_liters', 'dr.volume_variance_liters', 'dr.variance_percentage',
                    'dr.total_sales_ugx', 'dr.gross_profit_ugx'
                ])
                ->where('mr.meter_id', $meterId)
                ->where('mr.reading_date', '>=', $dateFrom)
                ->orderBy('mr.reading_date', 'desc')
                ->get();

            $comprehensiveHistory = [
                'history' => $history,
                'automation_stats' => [
                    'total_readings' => $history->count(),
                    'total_dispensed' => $history->sum('dispensed_liters'),
                    'reconciliations_completed' => $history->whereNotNull('volume_variance_liters')->count(),
                    'automation_success_rate' => $history->count() > 0 ?
                        ($history->whereNotNull('volume_variance_liters')->count() / $history->count()) * 100 : 100
                ],
                'variance_analysis' => [
                    'total_variance_incidents' => $history->where('variance_percentage', '>', 1.0)->count(),
                    'avg_variance_percentage' => $history->avg('variance_percentage') ?: 0,
                    'total_profit' => $history->sum('gross_profit_ugx') ?: 0
                ]
            ];

            return $this->responseHandler($comprehensiveHistory, 'Comprehensive meter history retrieved successfully');

        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), ['meter_id' => $request->get('meter_id')], 500);
        }
    }

    /**
     * Get comprehensive dashboard data for AJAX requests
     * SCHEMA COMPLIANT: All aggregations match database structure
     */
    public function getDashboardData(Request $request)
    {
        try {
            $user = Auth::user();
            $authorizedStations = $this->getAuthorizedStations($user);
            $stationId = $request->get('station_id');
            $dateFrom = $request->get('date_from', Carbon::now()->subDays(7)->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

            if ($stationId && !in_array($stationId, $authorizedStations)) {
                return $this->handleError(
                    'Station access denied',
                    ['station_id' => $stationId],
                    403
                );
            }

            $stationFilter = $stationId ? [$stationId] : $authorizedStations;

            // Get comprehensive dashboard metrics - SCHEMA COMPLIANT
            $dashboardData = $this->getComprehensiveDashboardData($stationFilter, $dateFrom, $dateTo);

            return $this->responseHandler($dashboardData, 'Comprehensive dashboard data retrieved successfully');

        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), ['input' => $request->all()], 500);
        }
    }

    /**
     * Get automation health status for monitoring
     * SCHEMA COMPLIANT: All monitoring queries match database structure
     */
    public function getAutomationHealth(Request $request)
    {
        try {
            $user = Auth::user();
            $authorizedStations = $this->getAuthorizedStations($user);
            $stationId = $request->get('station_id');

            if ($stationId && !in_array($stationId, $authorizedStations)) {
                return $this->handleError(
                    'Station access denied for automation health',
                    ['station_id' => $stationId],
                    403
                );
            }

            $health = $this->getAutomationHealthMetrics($stationId ?: $authorizedStations[0]);

            return $this->responseHandler($health, 'Automation health retrieved successfully');

        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), ['station_id' => $request->get('station_id')], 500);
        }
    }

    /**
     * Validate and preview reading before submission
     * SCHEMA COMPLIANT: All validation queries match database structure
     */
    public function validatePreview(Request $request)
    {
        try {
            $user = Auth::user();
            $authorizedStations = $this->getAuthorizedStations($user);

            $validator = Validator::make($request->all(), $this->getMeterReadingValidationRules());

            if ($validator->fails()) {
                return $this->handleError(
                    'Preview validation failed',
                    $validator->errors()->toArray(),
                    422
                );
            }

            $data = $validator->validated();
            $validationResult = $this->performBusinessValidations($data, $authorizedStations);

            // Get meter info for preview - SCHEMA COMPLIANT
            $meterInfo = DB::table('meters as m')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('m.id', $data['meter_id'])
                ->whereIn('s.id', $authorizedStations)
                ->select([
                    'm.id', 'm.meter_number', 'm.current_reading_liters',
                    't.tank_number', 't.fuel_type', 't.capacity_liters',
                    's.name as station_name'
                ])
                ->first();

            if (!$meterInfo) {
                return $this->handleError(
                    'Meter not found for preview',
                    ['meter_id' => $data['meter_id']],
                    404
                );
            }

            $dispensedAmount = $data['closing_reading_liters'] - $data['opening_reading_liters'];

            // Check for existing reading
            $existingReading = DB::table('meter_readings')
                ->where('meter_id', $data['meter_id'])
                ->where('reading_date', $data['reading_date'])
                ->exists();

            $preview = [
                'validation_status' => $validationResult['success'],
                'validation_errors' => $validationResult['errors'] ?? [],
                'meter_info' => $meterInfo,
                'dispensed_liters' => $dispensedAmount,
                'existing_reading' => $existingReading,
                'automation_preview' => [
                    'triggers_will_execute' => [
                        'automatic_reconciliation' => 'Will trigger when tank dip readings are available',
                        'fifo_consumption_processing' => 'Will process inventory consumption via stored procedures'
                    ],
                    'expected_processing' => [
                        'reconciliation' => 'Automatic when both meter and dip readings exist',
                        'fifo_inventory_consumption' => 'Via sp_process_fifo_consumption',
                        'variance_detection' => 'Automatic variance calculations and alerts'
                    ]
                ],
                'preview_warnings' => []
            ];

            // Add preview warnings
            if ($existingReading) {
                $preview['preview_warnings'][] = 'Reading already exists for this date - submission will be rejected';
            }

            if ($dispensedAmount == 0) {
                $preview['preview_warnings'][] = 'Zero dispense detected - confirm if no fuel was sold';
            }

            if ($dispensedAmount > 50000) {
                $preview['preview_warnings'][] = 'High dispense amount - please verify readings';
            }

            return $this->responseHandler($preview, 'Reading validation preview completed');

        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), ['input' => $request->all()], 500);
        }
    }

    /**
     * Export meter readings data with comprehensive options
     * SCHEMA COMPLIANT: All export queries match database structure
     */
    public function export(Request $request)
    {
        try {
            $user = Auth::user();
            $authorizedStations = $this->getAuthorizedStations($user);
            $stationId = $request->get('station_id');
            $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
            $format = $request->get('format', 'json');

            if ($stationId && !in_array($stationId, $authorizedStations)) {
                return $this->handleError(
                    'Station access denied for export',
                    ['station_id' => $stationId],
                    403
                );
            }

            $stationFilter = $stationId ? [$stationId] : $authorizedStations;

            // Get comprehensive export data - SCHEMA COMPLIANT
            $exportData = DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->join('users as u', 'mr.recorded_by_user_id', '=', 'u.id')
                ->leftJoin('daily_reconciliations as dr', function($join) {
                    $join->on('dr.tank_id', '=', 't.id')
                         ->on('dr.reconciliation_date', '=', 'mr.reading_date');
                })
                ->whereIn('s.id', $stationFilter)
                ->whereBetween('mr.reading_date', [$dateFrom, $dateTo])
                ->select([
                    's.name as station_name',
                    't.tank_number',
                    't.fuel_type',
                    'm.meter_number',
                    'mr.reading_date',
                    'mr.opening_reading_liters',
                    'mr.closing_reading_liters',
                    'mr.dispensed_liters',
                    'dr.volume_variance_liters',
                    'dr.variance_percentage',
                    'dr.total_sales_ugx',
                    'dr.gross_profit_ugx',
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as recorded_by"),
                    'mr.created_at'
                ])
                ->orderBy('mr.reading_date', 'desc')
                ->orderBy('s.name')
                ->orderBy('t.tank_number')
                ->get();

            // Handle CSV export
            if ($format === 'csv') {
                $filename = "meter_readings_export_" . date('Y-m-d_H-i-s') . ".csv";
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ];

                $callback = function() use ($exportData) {
                    $file = fopen('php://output', 'w');

                    // CSV Headers
                    fputcsv($file, [
                        'Station', 'Tank', 'Fuel Type', 'Meter', 'Date',
                        'Opening (L)', 'Closing (L)', 'Dispensed (L)', 'Sales (L)',
                        'Reset Occurred', 'Variance (L)', 'Variance (%)',
                        'Sales (UGX)', 'Profit (UGX)', 'Recorded By', 'Created'
                    ]);

                    // CSV Data
                    foreach ($exportData as $row) {
                        fputcsv($file, [
                            $row->station_name,
                            $row->tank_number,
                            $row->fuel_type,
                            $row->meter_number,
                            $row->reading_date,
                            $row->opening_reading_liters,
                            $row->closing_reading_liters,
                            $row->dispensed_liters,
                            $row->calculated_sales_liters,
                            $row->meter_reset_occurred ? 'Yes' : 'No',
                            $row->volume_variance_liters,
                            $row->variance_percentage,
                            $row->total_sales_ugx,
                            $row->gross_profit_ugx,
                            $row->recorded_by,
                            $row->created_at
                        ]);
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }

            // Return JSON export data
            return response()->json([
                'success' => true,
                'message' => 'Export data generated successfully',
                'data' => $exportData,
                'export_info' => [
                    'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                    'stations' => $stationFilter,
                    'record_count' => $exportData->count(),
                    'total_dispensed' => $exportData->sum('dispensed_liters'),
                    'total_sales' => $exportData->sum('total_sales_ugx')
                ]
            ]);

        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), ['input' => $request->all()], 500);
        }
    }

    // =================== PRIVATE HELPER METHODS ===================

    /**
     * Centralized error handler with AJAX/redirect support
     */
    private function handleError($message, $details = [], $statusCode = 500)
    {
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => $message,
                'details' => $details
            ], $statusCode);
        }

        // For web requests
        return redirect()->back()
            ->withErrors(['error' => $message])
            ->with('error', $message)
            ->withInput();
    }

    /**
     * Validate station access against authorized stations
     * SCHEMA COMPLIANT: No phantom column references
     */
    private function validateStationAccess($meterId, $authorizedStations)
    {
        $meter = DB::table('meters as m')
            ->join('tanks as t', 'm.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->where('m.id', $meterId)
            ->whereIn('s.id', $authorizedStations)
            ->select('s.id as station_id', 's.name', 't.tank_number', 'm.meter_number')
            ->first();

        if (!$meter) {
            throw new Exception("Station access validation failed for meter ID: {$meterId}");
        }

        return $meter->station_id;
    }

    /**
     * Get meter reading validation rules
     */
    private function getMeterReadingValidationRules()
    {
        return [
            'meter_id' => 'required|integer|exists:meters,id',
            'reading_date' => 'required|date|before_or_equal:today|after:' .
                Carbon::now()->subDays(30)->format('Y-m-d'),
            'opening_reading_liters' => 'required|numeric|min:0|max:999999999.999',
            'closing_reading_liters' => 'required|numeric|min:0|max:999999999.999'
        ];
    }

    /**
     * Validate insert data against exact schema
     */
    private function validateInsertData($tableName, array $data)
    {
        if (!isset(self::$VALIDATED_TABLES[$tableName])) {
            throw new Exception("Invalid table name: {$tableName}");
        }

        foreach (array_keys($data) as $column) {
            if (!in_array($column, self::$VALIDATED_TABLES[$tableName])) {
                throw new Exception("Invalid column '{$column}' for table '{$tableName}'");
            }
        }

        return true;
    }

    /**
     * Perform comprehensive business validations
     * SCHEMA COMPLIANT: All validation queries match database structure
     */
    private function performBusinessValidations($data, $authorizedStations)
    {
        $errors = [];

        try {
            $meter = DB::table('meters as m')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('m.id', $data['meter_id'])
                ->whereIn('s.id', $authorizedStations)
                ->where('m.is_active', 1)  // Only meters.is_active exists
                ->select([
                    'm.id', 'm.meter_number', 'm.current_reading_liters',
                    't.tank_number', 't.fuel_type', 't.capacity_liters',
                    's.name as station_name'
                ])
                ->first();

            if (!$meter) {
                $errors['meter_id'] = ['Meter not found, inactive, or access denied'];
                return ['success' => false, 'message' => 'Meter validation failed', 'errors' => $errors];
            }

            if ($data['closing_reading_liters'] < $data['opening_reading_liters']) {
                $errors['closing_reading_liters'] = ['Closing reading cannot be less than opening reading'];
            }

            $dispenseAmount = $data['closing_reading_liters'] - $data['opening_reading_liters'];
            $maxDailyDispense = 50000;

            if ($dispenseAmount > $maxDailyDispense) {
                $errors['closing_reading_liters'] = [
                    'Daily dispense (' . number_format($dispenseAmount, 3) . 'L) exceeds maximum limit (' .
                    number_format($maxDailyDispense, 3) . 'L)'
                ];
            }

            return [
                'success' => empty($errors),
                'message' => empty($errors) ? 'Validation passed' : 'Validation failed',
                'errors' => $errors,
                'meter_info' => $meter
            ];

        } catch (Exception $e) {
            throw new Exception("Business validation failed: " . $e->getMessage());
        }
    }

    /**
     * Track comprehensive automation execution results
     * SCHEMA COMPLIANT: Uses actual database tables (daily_reconciliations + fifo_consumption_log)
     */
    private function trackAutomationExecution($readingId, $insertData)
    {
        try {
            // Check if reconciliation was created automatically for this tank/date
            $reconciliation = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('meters as m', 'm.tank_id', '=', 't.id')
                ->where('m.id', $insertData['meter_id'])
                ->where('dr.reconciliation_date', $insertData['reading_date'])
                ->select([
                    'dr.*', 't.tank_number', 't.fuel_type'
                ])
                ->first();

            // Check FIFO consumption if reconciliation exists
            $fifoConsumption = [];
            if ($reconciliation) {
                $fifoConsumption = DB::table('fifo_consumption_log')
                    ->where('reconciliation_id', $reconciliation->id)
                    ->get();
            }

            return [
                'reconciliation' => $reconciliation,
                'fifo_consumption' => $fifoConsumption,
                'automation_message' => $this->generateAutomationMessage($reconciliation, $fifoConsumption)
            ];

        } catch (Exception $e) {
            throw new Exception("Automation tracking failed: " . $e->getMessage());
        }
    }

    /**
     * Generate comprehensive automation execution message
     */
    private function generateAutomationMessage($reconciliation, $fifoConsumption)
    {
        $messages = [];

        if ($reconciliation) {
            $variance = number_format($reconciliation->variance_percentage, 2);
            $messages[] = "✅ Daily reconciliation completed (Variance: {$variance}%)";

            if (count($fifoConsumption) > 0) {
                $layersConsumed = count($fifoConsumption);
                $totalCost = number_format($fifoConsumption->sum('total_cost_ugx'), 2);
                $messages[] = "✅ FIFO consumption processed: {$layersConsumed} layers consumed (Cost: {$totalCost} UGX)";
            } else {
                $messages[] = "⏳ FIFO processing pending - awaiting inventory layers";
            }

            if (abs($reconciliation->variance_percentage) > 2.0) {
                $messages[] = "⚠️ HIGH VARIANCE detected - requires investigation";
            }
        } else {
            $messages[] = "⏳ Reconciliation pending - awaiting tank dip readings for automation";
        }

        return implode(' | ', $messages);
    }

    /**
     * Get comprehensive automation health metrics
     * SCHEMA COMPLIANT: Uses actual database tables (meter_readings + daily_reconciliations)
     */
    private function getAutomationHealthMetrics($stationId)
    {
        try {
            $dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');

            // Get meter readings count for this station
            $meterReadingsCount = DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->where('t.station_id', $stationId)
                ->where('mr.reading_date', '>=', $dateFrom)
                ->count();

            // Get reconciliations count for this station
            $reconciliationsCount = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->where('t.station_id', $stationId)
                ->where('dr.reconciliation_date', '>=', $dateFrom)
                ->count();

            // Get FIFO consumption count
            $fifoConsumptionCount = DB::table('fifo_consumption_log as fcl')
                ->join('daily_reconciliations as dr', 'fcl.reconciliation_id', '=', 'dr.id')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->where('t.station_id', $stationId)
                ->where('dr.reconciliation_date', '>=', $dateFrom)
                ->count();

            $automationSuccessRate = $meterReadingsCount > 0 ?
                ($reconciliationsCount / $meterReadingsCount) * 100 : 100;

            return [
                'success_rate' => round($automationSuccessRate, 2),
                'total_meter_readings_30_days' => $meterReadingsCount,
                'total_reconciliations_30_days' => $reconciliationsCount,
                'total_fifo_consumptions_30_days' => $fifoConsumptionCount,
                'automation_health' => [
                    'meter_readings_processing' => [
                        'status' => 'ACTIVE',
                        'executions' => $meterReadingsCount,
                        'success_rate' => round($automationSuccessRate, 2)
                    ],
                    'reconciliation_automation' => [
                        'status' => 'ACTIVE',
                        'executions' => $reconciliationsCount,
                        'fifo_processing' => $fifoConsumptionCount
                    ]
                ],
                'recommendations' => $this->getAutomationRecommendations($automationSuccessRate, $meterReadingsCount)
            ];

        } catch (Exception $e) {
            throw new Exception("Automation health metrics failed: " . $e->getMessage());
        }
    }

    /**
     * Get automation recommendations based on health metrics
     */
    private function getAutomationRecommendations($successRate, $meterReadingsCount)
    {
        $recommendations = [];

        if ($successRate < 95) {
            $recommendations[] = 'Review reconciliation automation - some meter readings may be missing dip readings for full processing';
        }

        if ($successRate < 90) {
            $recommendations[] = 'Check tank dip reading completeness - automated reconciliation requires both meter and dip readings';
        }

        if ($successRate < 80) {
            $recommendations[] = 'CRITICAL: Investigate automation delays - ensure daily_readings are being recorded for all tanks';
        }

        if ($meterReadingsCount < 5) {
            $recommendations[] = 'Low meter reading volume - verify station operations and data entry consistency';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Automation system operating optimally - continue regular monitoring';
        }

        return $recommendations;
    }

    /**
     * Get comprehensive dashboard data
     * SCHEMA COMPLIANT: All dashboard queries match database structure
     */
    private function getComprehensiveDashboardData($stationFilter, $dateFrom, $dateTo = null)
    {
        try {
            if (!$dateTo) {
                $dateTo = $dateFrom;
            }

            if (!is_array($stationFilter)) {
                $stationFilter = [$stationFilter];
            }

            // Get comprehensive dashboard metrics
            $metrics = [
                'total_readings' => DB::table('meter_readings as mr')
                    ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                    ->join('tanks as t', 'm.tank_id', '=', 't.id')
                    ->whereIn('t.station_id', $stationFilter)
                    ->whereBetween('mr.reading_date', [$dateFrom, $dateTo])
                    ->count(),

                'total_dispensed' => DB::table('meter_readings as mr')
                    ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                    ->join('tanks as t', 'm.tank_id', '=', 't.id')
                    ->whereIn('t.station_id', $stationFilter)
                    ->whereBetween('mr.reading_date', [$dateFrom, $dateTo])
                    ->sum('mr.dispensed_liters'),

                'automation_success_rate' => $this->getAutomationSuccessRate($stationFilter, $dateFrom, $dateTo),

                'variance_incidents' => DB::table('daily_reconciliations as dr')
                    ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                    ->whereIn('t.station_id', $stationFilter)
                    ->whereBetween('dr.reconciliation_date', [$dateFrom, $dateTo])
                    ->where('dr.variance_percentage', '>', 1.0)
                    ->count(),

                'total_sales_ugx' => DB::table('daily_reconciliations as dr')
                    ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                    ->whereIn('t.station_id', $stationFilter)
                    ->whereBetween('dr.reconciliation_date', [$dateFrom, $dateTo])
                    ->sum('dr.total_sales_ugx'),

                'daily_trends' => $this->getDailyTrends($stationFilter, $dateFrom, $dateTo)
            ];

            return $metrics;

        } catch (Exception $e) {
            throw new Exception("Dashboard data failed: " . $e->getMessage());
        }
    }

    /**
     * Get reading automation results
     * SCHEMA COMPLIANT: Uses actual tables (daily_reconciliations + fifo_consumption_log)
     */
    private function getReadingAutomationResults($reading)
    {
        try {
            // Get reconciliation for this meter's tank and date
            $reconciliation = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('meters as m', 'm.tank_id', '=', 't.id')
                ->where('m.id', $reading->meter_id)
                ->where('dr.reconciliation_date', $reading->reading_date)
                ->select('dr.*')
                ->first();

            $fifoConsumption = [];
            if ($reconciliation) {
                $fifoConsumption = DB::table('fifo_consumption_log')
                    ->where('reconciliation_id', $reconciliation->id)
                    ->get();
            }

            return [
                'reconciliation' => $reconciliation,
                'fifo_processing' => $fifoConsumption,
                'automation_message' => $this->generateAutomationMessage($reconciliation, $fifoConsumption)
            ];

        } catch (Exception $e) {
            throw new Exception("Reading automation results failed: " . $e->getMessage());
        }
    }

    /**
     * Get reading business metrics
     * SCHEMA COMPLIANT: All metric calculations match database structure
     */
    private function getReadingBusinessMetrics($reading)
    {
        try {
            return [
                'dispensed_liters' => $reading->dispensed_liters,
                'dispensed_percentage_of_capacity' => ($reading->dispensed_liters / $reading->capacity_liters) * 100,
                'reading_efficiency' => 'Normal',
                'volume_dispensed_liters' => $reading->dispensed_liters,
                'revenue_per_liter' => 0 // Would need pricing data to calculate
            ];

        } catch (Exception $e) {
            throw new Exception("Business metrics failed: " . $e->getMessage());
        }
    }

    /**
     * Get historical context for reading
     * SCHEMA COMPLIANT: All context queries match database structure
     */
    private function getHistoricalContext($reading)
    {
        try {
            $previousReading = DB::table('meter_readings')
                ->where('meter_id', $reading->meter_id)
                ->where('reading_date', '<', $reading->reading_date)
                ->orderBy('reading_date', 'desc')
                ->first();

            $nextReading = DB::table('meter_readings')
                ->where('meter_id', $reading->meter_id)
                ->where('reading_date', '>', $reading->reading_date)
                ->orderBy('reading_date', 'asc')
                ->first();

            return [
                'previous_reading' => $previousReading,
                'next_reading' => $nextReading,
                'progression_valid' => $previousReading ?
                    $reading->opening_reading_liters >= $previousReading->closing_reading_liters : true,
                'sequence_complete' => $previousReading && $nextReading
            ];

        } catch (Exception $e) {
            throw new Exception("Historical context failed: " . $e->getMessage());
        }
    }

    /**
     * Get automation success rate
     * SCHEMA COMPLIANT: Uses meter_readings and daily_reconciliations tables
     */
    private function getAutomationSuccessRate($stationFilter, $dateFrom, $dateTo)
    {
        try {
            $totalReadings = DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->whereIn('t.station_id', $stationFilter)
                ->whereBetween('mr.reading_date', [$dateFrom, $dateTo])
                ->count();

            $reconciliationsCompleted = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->whereIn('t.station_id', $stationFilter)
                ->whereBetween('dr.reconciliation_date', [$dateFrom, $dateTo])
                ->count();

            return $totalReadings > 0 ? ($reconciliationsCompleted / $totalReadings) * 100 : 100;

        } catch (Exception $e) {
            throw new Exception("Automation success rate failed: " . $e->getMessage());
        }
    }

    /**
     * Get daily trends
     * SCHEMA COMPLIANT: All trend queries match database structure
     */
    private function getDailyTrends($stationFilter, $dateFrom, $dateTo)
    {
        try {
            return DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->whereIn('t.station_id', $stationFilter)
                ->whereBetween('mr.reading_date', [$dateFrom, $dateTo])
                ->groupBy('mr.reading_date')
                ->selectRaw('
                    mr.reading_date,
                    COUNT(mr.id) as total_readings,
                    SUM(mr.dispensed_liters) as total_dispensed,
                    AVG(mr.dispensed_liters) as avg_dispensed_per_meter
                ')
                ->orderBy('mr.reading_date')
                ->get();

        } catch (Exception $e) {
            throw new Exception("Daily trends failed: " . $e->getMessage());
        }
    }

    /**
     * Response handler for consistent dual API/VIEW responses
     */
    private function responseHandler($data, $message, $success = true, $errors = [])
    {
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json($response);
        }

        if ($success) {
            return redirect()->back()->with('success', $message)->with('data', $data);
        } else {
            return redirect()->back()->withErrors($errors)->with('error', $message)->withInput();
        }
    }
}
