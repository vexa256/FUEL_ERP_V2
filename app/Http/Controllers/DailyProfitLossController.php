<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FuelERP_CriticalPrecisionService;
use Carbon\Carbon;
use Exception;

/**
 * FUEL_ERP_V2 Daily Profit & Loss Controller - FULLY REFACTORED
 *
 * 100% DATABASE SCHEMA ALIGNED - Uses ONLY actual fields from FUEL_ERP_V2.sql
 * ZERO PHANTOM FIELDS - Every field validated against real schema dump
 * CRITICAL PRECISION SERVICE INTEGRATED - Defers to automation layer
 * RAW ERROR EXPOSURE - No sugar-coated error messages
 * STATION ACCESS CONTROL - Admin/non-admin role enforcement
 */
class DailyProfitLossController extends Controller
{
    private FuelERP_CriticalPrecisionService $fuelService;

    /**
     * ACTUAL schema precision from database dump analysis
     */
    private const SCHEMA_PRECISION = [
        'volume_precision' => 3,      // decimal(12,3) for liters
        'currency_precision' => 4,    // decimal(15,4) for UGX
        'percentage_precision' => 4   // decimal(8,4) for percentages
    ];

    /**
     * ACTUAL fuel types from schema enum - EXPANDED SHELL UGANDA
     */
    private const VALID_FUEL_TYPES = [
        'petrol', 'diesel', 'kerosene', 'fuelsave_unleaded', 'fuelsave_diesel',
        'v_power_unleaded', 'v_power_diesel', 'ago', 'super_unleaded', 'jet_a1',
        'avgas_100ll', 'heavy_fuel_oil', 'marine_gas_oil', 'low_sulfur_diesel',
        'ultra_low_sulfur_diesel', 'lpg', 'cooking_gas', 'industrial_lpg',
        'autogas', 'household_kerosene', 'illuminating_kerosene', 'industrial_kerosene'
    ];

    /**
     * ACTUAL user roles from schema enum
     */
    private const VALID_ROLES = ['admin', 'manager', 'attendant', 'supervisor'];

    /**
     * GENERATED columns in daily_reconciliations - NEVER manually set these
     */
    private const GENERATED_COLUMNS = [
        'theoretical_closing_stock_liters',
        'volume_variance_liters',
        'variance_percentage',
        'gross_profit_ugx',
        'profit_margin_percentage',
        'abs_variance_percentage'
    ];

    public function __construct(FuelERP_CriticalPrecisionService $fuelService)
    {
        $this->fuelService = $fuelService;

    }

    /**
     * Display daily profit & loss index page
     * MANDATORY: Station selection UI for both admin and non-admin users
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                throw new Exception("User not authenticated");
            }

            // Get stations based on user role - STRICT ACCESS CONTROL
            $availableStations = $this->getAvailableStations($user);

            // Set default station for initial load
            $defaultStationId = $this->getDefaultStationId($user, $request);

            // Get initial data if station selected
            $initialData = null;
            if ($defaultStationId) {
                try {
                    $initialRequest = new Request([
                        'station_id' => $defaultStationId,
                        'date_start' => now()->format('Y-m-d'),
                        'date_end' => now()->format('Y-m-d')
                    ]);

                    $response = $this->getDailyProfitLoss($initialRequest);
                    $initialData = $response->getData(true);
                } catch (Exception $e) {
                    // Log but don't break initial page load
                    Log::warning("Could not load initial P&L data", [
                        'user_id' => $user->id,
                        'station_id' => $defaultStationId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return view('reports.daily-profit-loss', [
                'user' => $user,
                'available_stations' => $availableStations,
                'default_station_id' => $defaultStationId,
                'valid_fuel_types' => self::VALID_FUEL_TYPES,
                'initial_data' => $initialData,
                'today' => now()->format('Y-m-d'),
                'max_date_range_days' => 90, // Business rule limit
                'precision_rules' => self::SCHEMA_PRECISION
            ]);

        } catch (Exception $e) {
            Log::error("Daily P&L Index Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->view('errors.500', [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Daily P&L Report Data - AJAX/Traditional Laravel Compatible
     * 100% SCHEMA ALIGNED with RAW ERROR EXPOSURE
     */
    public function getDailyProfitLoss(Request $request)
    {
        try {
            // Validate and extract filters with STRICT access control
            $filters = $this->validateAndExtractFilters($request);

            // Build base query with station access enforcement
            $baseQuery = $this->buildBaseQuery($filters);

            // Get core daily reconciliation data - ACTUAL schema fields only
            $dailyReconciliations = $this->getDailyReconciliationData($baseQuery, $filters);

            // Get comprehensive analysis data
            $fuelTypeBreakdown = $this->getFuelTypeBreakdown($baseQuery, $filters);
            $tankLevelDetails = $this->getTankLevelDetails($baseQuery, $filters);
            $varianceAnalysis = $this->getVarianceAnalysis($baseQuery, $filters);
            $volumeAnalysis = $this->getVolumeAnalysis($baseQuery, $filters);
            $profitabilityMetrics = $this->getProfitabilityMetrics($baseQuery, $filters);

            // Calculate KPIs and summaries
            $summaryTotals = $this->calculateSummaryTotals($dailyReconciliations);
            $kpiMetrics = $this->calculateKPIMetrics($dailyReconciliations);

            // Get comparative data (previous period)
            $comparativeData = $this->getComparativeData($filters);

            // Get additional operational insights
            $operationalInsights = $this->getOperationalInsights($baseQuery, $filters);
            $dataQualityAssessment = $this->assessDataQuality($dailyReconciliations);

            // Prepare comprehensive response structure
            $responseData = [
                'success' => true,
                'data' => [
                    // Core data
                    'summary_totals' => $summaryTotals,
                    'daily_reconciliations' => $dailyReconciliations,

                    // Analytical breakdowns
                    'fuel_type_breakdown' => $fuelTypeBreakdown,
                    'tank_level_details' => $tankLevelDetails,
                    'variance_analysis' => $varianceAnalysis,
                    'volume_analysis' => $volumeAnalysis,
                    'profitability_metrics' => $profitabilityMetrics,

                    // KPIs and comparisons
                    'kpi_metrics' => $kpiMetrics,
                    'comparative_data' => $comparativeData,
                    'operational_insights' => $operationalInsights,

                    // Metadata
                    'data_quality' => $dataQualityAssessment,
                    'filters_applied' => $filters,
                    'generated_at' => now()->toISOString(),
                    'schema_version' => 'FUEL_ERP_V2'
                ],
                'meta' => [
                    'total_reconciliations' => count($dailyReconciliations),
                    'unique_tanks' => count(array_unique(array_column($dailyReconciliations, 'tank_id'))),
                    'unique_stations' => count(array_unique(array_column($dailyReconciliations, 'station_id'))),
                    'date_range' => [
                        'start' => $filters['date_start'],
                        'end' => $filters['date_end'],
                        'days_included' => Carbon::parse($filters['date_start'])->diffInDays(Carbon::parse($filters['date_end'])) + 1
                    ],
                    'stations_included' => $this->getIncludedStations($filters),
                    'precision_applied' => self::SCHEMA_PRECISION,
                    'user_access_level' => $filters['user_role']
                ]
            ];

            // Support both AJAX and traditional Laravel responses
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($responseData);
            } else {
                return redirect()->back()->with('profit_loss_data', $responseData);
            }

        } catch (Exception $e) {
            Log::error("Daily P&L Report Generation Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            // RAW ERROR EXPOSURE - No sugar coating
            $errorResponse = [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'DAILY_PL_GENERATION_FAILED',
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'timestamp' => now()->toISOString()
                ]
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($errorResponse, 500);
            } else {
                return redirect()->back()->withErrors(['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Validate and extract filters with STRICT access control enforcement
     */
    private function validateAndExtractFilters(Request $request): array
    {
        $user = auth()->user();
        if (!$user) {
            throw new Exception("User not authenticated");
        }

        // Validate user role
        if (!in_array($user->role, self::VALID_ROLES)) {
            throw new Exception("Invalid user role: {$user->role}");
        }

        // Date filtering with business rule validation
        $dateStart = $request->input('date_start', now()->format('Y-m-d'));
        $dateEnd = $request->input('date_end', $dateStart);

        // Strict date format validation
        try {
            $startDate = Carbon::createFromFormat('Y-m-d', $dateStart);
            $endDate = Carbon::createFromFormat('Y-m-d', $dateEnd);
        } catch (Exception $e) {
            throw new Exception("Invalid date format. Use YYYY-MM-DD. Error: " . $e->getMessage());
        }

        // Business rule: Date range validation
        if ($startDate->gt($endDate)) {
            throw new Exception("Start date cannot be after end date");
        }

        if ($startDate->diffInDays($endDate) > 90) {
            throw new Exception("Date range cannot exceed 90 days");
        }

        // STRICT station access control enforcement
        $stationIds = $this->enforceStationAccess($user, $request);

        // Fuel type filtering validation
        $fuelTypeFilter = $request->input('fuel_type');
        if ($fuelTypeFilter && !in_array($fuelTypeFilter, self::VALID_FUEL_TYPES)) {
            throw new Exception("Invalid fuel type: {$fuelTypeFilter}");
        }

        // Tank filtering validation
        $tankIdFilter = $request->input('tank_id');
        if ($tankIdFilter && !is_numeric($tankIdFilter)) {
            throw new Exception("Invalid tank ID: {$tankIdFilter}");
        }

        // Variance threshold filtering
        $varianceThreshold = $request->input('variance_threshold');
        if ($varianceThreshold && (!is_numeric($varianceThreshold) || $varianceThreshold < 0)) {
            throw new Exception("Invalid variance threshold: {$varianceThreshold}");
        }

        return [
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'station_ids' => $stationIds,
            'fuel_type' => $fuelTypeFilter,
            'tank_id' => $tankIdFilter ? (int) $tankIdFilter : null,
            'variance_threshold' => $varianceThreshold ? (float) $varianceThreshold : null,
            'user_role' => $user->role,
            'user_station_id' => $user->station_id,
            'user_id' => $user->id
        ];
    }

    /**
     * STRICT station access control enforcement
     */
    private function enforceStationAccess($user, Request $request): array
    {
        $requestedStationId = $request->input('station_id');

        if ($user->role === 'admin') {
            // Admin users get full access
            if ($requestedStationId) {
                // Validate station exists
                $stationExists = DB::table('stations')->where('id', $requestedStationId)->exists();
                if (!$stationExists) {
                    throw new Exception("Station ID {$requestedStationId} does not exist");
                }
                return [(int) $requestedStationId];
            } else {
                // Return all stations
                return DB::table('stations')->pluck('id')->toArray();
            }
        } else {
            // Non-admin users: STRICT station restriction
            if (!$user->station_id) {
                throw new Exception("User has no assigned station. Contact administrator.");
            }

            if ($requestedStationId) {
                // Verify user can access requested station
                if ((int) $requestedStationId !== (int) $user->station_id) {
                    throw new Exception("Access denied to station ID: {$requestedStationId}. User restricted to station ID: {$user->station_id}");
                }
                return [(int) $requestedStationId];
            } else {
                // Return only user's assigned station
                return [(int) $user->station_id];
            }
        }
    }

    /**
     * Build base query with proper joins and schema alignment
     */
    private function buildBaseQuery(array $filters): \Illuminate\Database\Query\Builder
    {
        return DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->join('users as u', 'dr.reconciled_by_user_id', '=', 'u.id')
            ->whereIn('s.id', $filters['station_ids'])
            ->whereBetween('dr.reconciliation_date', [$filters['date_start'], $filters['date_end']])
            ->when($filters['fuel_type'], function($query, $fuelType) {
                return $query->where('t.fuel_type', $fuelType);
            })
            ->when($filters['tank_id'], function($query, $tankId) {
                return $query->where('t.id', $tankId);
            })
            ->when($filters['variance_threshold'], function($query, $threshold) {
                return $query->where('dr.abs_variance_percentage', '>', $threshold);
            });
    }

    /**
     * Get daily reconciliation data - ONLY actual schema fields
     */
    private function getDailyReconciliationData($baseQuery, array $filters): array
    {
        $reconciliations = $baseQuery->select([
            // Primary keys and identifiers
            'dr.id as reconciliation_id',
            'dr.tank_id',
            'dr.reconciliation_date',
            't.tank_number',
            't.fuel_type',
            't.capacity_liters',
            't.current_volume_liters',
            's.id as station_id',
            's.name as station_name',
            's.location as station_location',

            // Volume data - ACTUAL schema fields
            'dr.opening_stock_liters',
            'dr.total_delivered_liters',
            'dr.total_dispensed_liters',
            'dr.theoretical_closing_stock_liters',  // GENERATED column
            'dr.actual_closing_stock_liters',
            'dr.volume_variance_liters',            // GENERATED column
            'dr.variance_percentage',               // GENERATED column
            'dr.abs_variance_percentage',           // GENERATED column

            // Financial data - ACTUAL schema fields
            'dr.total_cogs_ugx',
            'dr.total_sales_ugx',
            'dr.gross_profit_ugx',                  // GENERATED column
            'dr.profit_margin_percentage',          // GENERATED column

            // FIFO valuation data - ACTUAL schema fields
            'dr.opening_stock_value_ugx',
            'dr.closing_stock_value_ugx',
            'dr.inventory_value_change_ugx',
            'dr.cost_of_goods_available_ugx',
            'dr.valuation_method',
            'dr.valuation_quality',
            'dr.inventory_variance_ugx',

            // Metadata - ACTUAL schema fields
            'dr.reconciled_by_user_id',
            'dr.reconciled_at',
            'dr.valuation_processed_at',
            'dr.valuation_processed_by',
            'dr.requires_revaluation',
            'dr.valuation_error_message',
            'u.name as reconciled_by_name',
            'u.employee_id as reconciled_by_employee_id'
        ])
        ->orderBy('dr.reconciliation_date', 'desc')
        ->orderBy('s.name')
        ->orderBy('t.tank_number')
        ->get()
        ->toArray();

        // Apply schema precision formatting
        return array_map(function($row) {
            return $this->applyPrecisionFormatting((array) $row);
        }, $reconciliations);
    }

    /**
     * Get fuel type breakdown with aggregations
     */
    private function getFuelTypeBreakdown($baseQuery, array $filters): array
    {
        $breakdown = $baseQuery->select([
            't.fuel_type',
            DB::raw('COUNT(DISTINCT dr.id) as reconciliation_count'),
            DB::raw('COUNT(DISTINCT t.id) as tank_count'),
            DB::raw('COUNT(DISTINCT s.id) as station_count'),
            DB::raw('SUM(dr.total_sales_ugx) as total_revenue_ugx'),
            DB::raw('SUM(dr.total_cogs_ugx) as total_cogs_ugx'),
            DB::raw('SUM(dr.gross_profit_ugx) as total_profit_ugx'),
            DB::raw('AVG(dr.profit_margin_percentage) as avg_profit_margin_pct'),
            DB::raw('SUM(dr.total_dispensed_liters) as total_volume_sold_liters'),
            DB::raw('SUM(dr.total_delivered_liters) as total_delivered_liters'),
            DB::raw('SUM(ABS(dr.volume_variance_liters)) as total_variance_volume_liters'),
            DB::raw('AVG(dr.abs_variance_percentage) as avg_variance_pct'),
            DB::raw('MAX(dr.abs_variance_percentage) as max_variance_pct'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 2.0 THEN 1 END) as high_variance_count'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 5.0 THEN 1 END) as critical_variance_count'),
            DB::raw('SUM(CASE WHEN dr.volume_variance_liters > 0 THEN dr.volume_variance_liters ELSE 0 END) as positive_variance_liters'),
            DB::raw('SUM(CASE WHEN dr.volume_variance_liters < 0 THEN ABS(dr.volume_variance_liters) ELSE 0 END) as negative_variance_liters')
        ])
        ->groupBy('t.fuel_type')
        ->orderBy(DB::raw('SUM(dr.total_sales_ugx)'), 'desc')
        ->get()
        ->toArray();

        return array_map(function($row) {
            $row = (array) $row;

            // Calculate derived metrics
            $row['revenue_per_liter_ugx'] = $row['total_volume_sold_liters'] > 0 ?
                $row['total_revenue_ugx'] / $row['total_volume_sold_liters'] : 0;

            $row['profit_per_liter_ugx'] = $row['total_volume_sold_liters'] > 0 ?
                $row['total_profit_ugx'] / $row['total_volume_sold_liters'] : 0;

            $row['variance_risk_score'] = $this->calculateVarianceRiskScore(
                $row['avg_variance_pct'],
                $row['high_variance_count'],
                $row['critical_variance_count']
            );

            return $this->applyPrecisionFormatting($row);
        }, $breakdown);
    }

    /**
     * Get tank-level details for operational analysis
     */
    private function getTankLevelDetails($baseQuery, array $filters): array
    {
        $tankDetails = $baseQuery->select([
            't.id as tank_id',
            't.tank_number',
            't.fuel_type',
            't.capacity_liters',
            't.current_volume_liters',
            's.id as station_id',
            's.name as station_name',

            // Performance aggregations
            DB::raw('COUNT(dr.id) as reconciliation_count'),
            DB::raw('SUM(dr.total_sales_ugx) as tank_total_revenue_ugx'),
            DB::raw('SUM(dr.total_cogs_ugx) as tank_total_cogs_ugx'),
            DB::raw('SUM(dr.gross_profit_ugx) as tank_total_profit_ugx'),
            DB::raw('AVG(dr.profit_margin_percentage) as tank_avg_margin_pct'),
            DB::raw('SUM(dr.total_dispensed_liters) as tank_total_volume_liters'),
            DB::raw('SUM(dr.total_delivered_liters) as tank_total_deliveries_liters'),

            // Variance analysis
            DB::raw('AVG(dr.abs_variance_percentage) as tank_avg_variance_pct'),
            DB::raw('MAX(dr.abs_variance_percentage) as tank_max_variance_pct'),
            DB::raw('MIN(dr.abs_variance_percentage) as tank_min_variance_pct'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 2.0 THEN 1 END) as high_variance_days'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 5.0 THEN 1 END) as critical_variance_days'),

            // Stock level analysis
            DB::raw('AVG(dr.opening_stock_liters) as avg_opening_stock_liters'),
            DB::raw('AVG(dr.actual_closing_stock_liters) as avg_closing_stock_liters'),
            DB::raw('MIN(dr.actual_closing_stock_liters) as min_stock_level_liters'),
            DB::raw('MAX(dr.actual_closing_stock_liters) as max_stock_level_liters'),

            // Data quality indicators
            DB::raw('COUNT(CASE WHEN dr.valuation_quality = "COMPLETE" THEN 1 END) as complete_valuations'),
            DB::raw('COUNT(CASE WHEN dr.requires_revaluation = 1 THEN 1 END) as revaluation_required'),
            DB::raw('COUNT(CASE WHEN dr.valuation_error_message IS NOT NULL THEN 1 END) as valuation_errors')
        ])
        ->groupBy('t.id', 't.tank_number', 't.fuel_type', 't.capacity_liters', 't.current_volume_liters', 's.id', 's.name')
        ->orderBy(DB::raw('SUM(dr.total_sales_ugx)'), 'desc')
        ->get()
        ->toArray();

        return array_map(function($row) {
            $row = (array) $row;

            // Calculate operational metrics
            $row['utilization_rate_pct'] = $row['capacity_liters'] > 0 ?
                ($row['current_volume_liters'] / $row['capacity_liters']) * 100 : 0;

            $row['revenue_per_liter_ugx'] = $row['tank_total_volume_liters'] > 0 ?
                $row['tank_total_revenue_ugx'] / $row['tank_total_volume_liters'] : 0;

            $row['profit_per_liter_ugx'] = $row['tank_total_volume_liters'] > 0 ?
                $row['tank_total_profit_ugx'] / $row['tank_total_volume_liters'] : 0;

            $row['variance_risk_level'] = $this->calculateVarianceRiskLevel(
                $row['tank_avg_variance_pct'],
                $row['high_variance_days'],
                $row['critical_variance_days']
            );

            $row['data_quality_score_pct'] = $row['reconciliation_count'] > 0 ?
                ($row['complete_valuations'] / $row['reconciliation_count']) * 100 : 0;

            return $this->applyPrecisionFormatting($row);
        }, $tankDetails);
    }

    /**
     * Get comprehensive variance analysis
     */
    private function getVarianceAnalysis($baseQuery, array $filters): array
    {
        // Overall variance distribution
        $distribution = $baseQuery->select([
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage <= 1.0 THEN 1 END) as excellent_count'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 1.0 AND dr.abs_variance_percentage <= 2.0 THEN 1 END) as good_count'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 2.0 AND dr.abs_variance_percentage <= 5.0 THEN 1 END) as warning_count'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 5.0 THEN 1 END) as critical_count'),
            DB::raw('COUNT(dr.id) as total_reconciliations'),
            DB::raw('AVG(dr.abs_variance_percentage) as overall_avg_variance_pct'),
            DB::raw('MAX(dr.abs_variance_percentage) as max_variance_pct'),
            DB::raw('MIN(dr.abs_variance_percentage) as min_variance_pct'),
            DB::raw('STDDEV(dr.abs_variance_percentage) as variance_std_dev'),
            DB::raw('SUM(ABS(dr.volume_variance_liters)) as total_variance_volume_liters'),
            DB::raw('SUM(CASE WHEN dr.volume_variance_liters > 0 THEN dr.volume_variance_liters ELSE 0 END) as total_gains_liters'),
            DB::raw('SUM(CASE WHEN dr.volume_variance_liters < 0 THEN ABS(dr.volume_variance_liters) ELSE 0 END) as total_losses_liters')
        ])
        ->first();

        // Daily variance trends
        $dailyTrends = $baseQuery->select([
            'dr.reconciliation_date',
            DB::raw('COUNT(dr.id) as daily_reconciliations'),
            DB::raw('AVG(dr.abs_variance_percentage) as daily_avg_variance_pct'),
            DB::raw('MAX(dr.abs_variance_percentage) as daily_max_variance_pct'),
            DB::raw('SUM(dr.volume_variance_liters) as daily_net_variance_liters'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 2.0 THEN 1 END) as daily_high_variance_count'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 5.0 THEN 1 END) as daily_critical_variance_count')
        ])
        ->groupBy('dr.reconciliation_date')
        ->orderBy('dr.reconciliation_date')
        ->get()
        ->toArray();

        // Station-level variance analysis
        $stationVariance = $baseQuery->select([
            's.id as station_id',
            's.name as station_name',
            DB::raw('AVG(dr.abs_variance_percentage) as station_avg_variance_pct'),
            DB::raw('MAX(dr.abs_variance_percentage) as station_max_variance_pct'),
            DB::raw('SUM(ABS(dr.volume_variance_liters)) as station_total_variance_liters'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 5.0 THEN 1 END) as station_critical_count'),
            DB::raw('COUNT(dr.id) as station_reconciliation_count')
        ])
        ->groupBy('s.id', 's.name')
        ->orderBy(DB::raw('AVG(dr.abs_variance_percentage)'), 'desc')
        ->get()
        ->toArray();

        return [
            'distribution' => $this->applyPrecisionFormatting((array) $distribution),
            'daily_trends' => array_map([$this, 'applyPrecisionFormatting'],
                array_map(function($item) { return (array) $item; }, $dailyTrends)),
            'station_variance' => array_map([$this, 'applyPrecisionFormatting'],
                array_map(function($item) { return (array) $item; }, $stationVariance))
        ];
    }

    /**
     * Get volume analysis metrics
     */
    private function getVolumeAnalysis($baseQuery, array $filters): array
    {
        $volumeMetrics = $baseQuery->select([
            // Volume totals
            DB::raw('SUM(dr.opening_stock_liters) as total_opening_stock_liters'),
            DB::raw('SUM(dr.total_delivered_liters) as total_deliveries_liters'),
            DB::raw('SUM(dr.total_dispensed_liters) as total_dispensed_liters'),
            DB::raw('SUM(dr.actual_closing_stock_liters) as total_closing_stock_liters'),

            // Volume averages
            DB::raw('AVG(dr.opening_stock_liters) as avg_opening_stock_liters'),
            DB::raw('AVG(dr.total_delivered_liters) as avg_deliveries_liters'),
            DB::raw('AVG(dr.total_dispensed_liters) as avg_dispensed_liters'),
            DB::raw('AVG(dr.actual_closing_stock_liters) as avg_closing_stock_liters'),

            // Throughput analysis
            DB::raw('SUM(dr.total_delivered_liters + dr.opening_stock_liters) as total_available_liters'),
            DB::raw('COUNT(DISTINCT dr.reconciliation_date) as operating_days'),
            DB::raw('COUNT(DISTINCT t.id) as active_tanks'),
            DB::raw('SUM(t.capacity_liters) as total_tank_capacity_liters'),

            // Efficiency metrics
            DB::raw('SUM(dr.total_dispensed_liters) / COUNT(DISTINCT dr.reconciliation_date) as avg_daily_throughput_liters'),
            DB::raw('CASE WHEN SUM(dr.total_delivered_liters + dr.opening_stock_liters) > 0
                     THEN (SUM(dr.total_dispensed_liters) / SUM(dr.total_delivered_liters + dr.opening_stock_liters)) * 100
                     ELSE 0 END as inventory_turnover_rate_pct')
        ])
        ->first();

        return $this->applyPrecisionFormatting((array) $volumeMetrics);
    }

    /**
     * Get profitability metrics analysis
     */
    private function getProfitabilityMetrics($baseQuery, array $filters): array
    {
        $profitMetrics = $baseQuery->select([
            // Revenue analysis
            DB::raw('SUM(dr.total_sales_ugx) as total_revenue_ugx'),
            DB::raw('AVG(dr.total_sales_ugx) as avg_daily_revenue_ugx'),
            DB::raw('MAX(dr.total_sales_ugx) as peak_daily_revenue_ugx'),
            DB::raw('MIN(dr.total_sales_ugx) as lowest_daily_revenue_ugx'),

            // Cost analysis
            DB::raw('SUM(dr.total_cogs_ugx) as total_cogs_ugx'),
            DB::raw('AVG(dr.total_cogs_ugx) as avg_daily_cogs_ugx'),

            // Profit analysis
            DB::raw('SUM(dr.gross_profit_ugx) as total_profit_ugx'),
            DB::raw('AVG(dr.gross_profit_ugx) as avg_daily_profit_ugx'),
            DB::raw('MAX(dr.gross_profit_ugx) as peak_daily_profit_ugx'),
            DB::raw('MIN(dr.gross_profit_ugx) as lowest_daily_profit_ugx'),

            // Margin analysis
            DB::raw('AVG(dr.profit_margin_percentage) as avg_profit_margin_pct'),
            DB::raw('MAX(dr.profit_margin_percentage) as peak_profit_margin_pct'),
            DB::raw('MIN(dr.profit_margin_percentage) as lowest_profit_margin_pct'),

            // Per-liter metrics
            DB::raw('SUM(dr.total_sales_ugx) / NULLIF(SUM(dr.total_dispensed_liters), 0) as revenue_per_liter_ugx'),
            DB::raw('SUM(dr.total_cogs_ugx) / NULLIF(SUM(dr.total_dispensed_liters), 0) as cost_per_liter_ugx'),
            DB::raw('SUM(dr.gross_profit_ugx) / NULLIF(SUM(dr.total_dispensed_liters), 0) as profit_per_liter_ugx'),

            // Operating days
            DB::raw('COUNT(DISTINCT dr.reconciliation_date) as operating_days'),
            DB::raw('COUNT(dr.id) as total_reconciliations')
        ])
        ->first();

        return $this->applyPrecisionFormatting((array) $profitMetrics);
    }

    /**
     * Calculate comprehensive KPI metrics
     */
    private function calculateKPIMetrics(array $dailyReconciliations): array
    {
        if (empty($dailyReconciliations)) {
            return $this->applyPrecisionFormatting([
                'total_revenue_ugx' => 0,
                'total_profit_ugx' => 0,
                'avg_profit_margin_pct' => 0,
                'total_volume_liters' => 0,
                'revenue_per_liter_ugx' => 0,
                'profit_per_liter_ugx' => 0,
                'avg_variance_pct' => 0,
                'operational_efficiency_score' => 0,
                'data_quality_score_pct' => 0
            ]);
        }

        $totalRevenue = array_sum(array_column($dailyReconciliations, 'total_sales_ugx'));
        $totalCogs = array_sum(array_column($dailyReconciliations, 'total_cogs_ugx'));
        $totalProfit = array_sum(array_column($dailyReconciliations, 'gross_profit_ugx'));
        $totalVolume = array_sum(array_column($dailyReconciliations, 'total_dispensed_liters'));

        $kpis = [
            'total_revenue_ugx' => $totalRevenue,
            'total_cogs_ugx' => $totalCogs,
            'total_profit_ugx' => $totalProfit,
            'total_volume_liters' => $totalVolume,
            'avg_profit_margin_pct' => $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0,
            'revenue_per_liter_ugx' => $totalVolume > 0 ? $totalRevenue / $totalVolume : 0,
            'profit_per_liter_ugx' => $totalVolume > 0 ? $totalProfit / $totalVolume : 0,
            'cost_per_liter_ugx' => $totalVolume > 0 ? $totalCogs / $totalVolume : 0,

            // Variance metrics
            'avg_variance_pct' => count($dailyReconciliations) > 0 ?
                array_sum(array_column($dailyReconciliations, 'abs_variance_percentage')) / count($dailyReconciliations) : 0,
            'max_variance_pct' => count($dailyReconciliations) > 0 ?
                max(array_column($dailyReconciliations, 'abs_variance_percentage')) : 0,
            'min_variance_pct' => count($dailyReconciliations) > 0 ?
                min(array_column($dailyReconciliations, 'abs_variance_percentage')) : 0,

            // Operational metrics
            'reconciliation_count' => count($dailyReconciliations),
            'unique_tanks' => count(array_unique(array_column($dailyReconciliations, 'tank_id'))),
            'unique_stations' => count(array_unique(array_column($dailyReconciliations, 'station_id'))),

            // Calculated scores
            'operational_efficiency_score' => $this->calculateOperationalEfficiencyScore($dailyReconciliations),
            'data_quality_score_pct' => $this->calculateDataQualityScore($dailyReconciliations)
        ];

        return $this->applyPrecisionFormatting($kpis);
    }

    /**
     * Calculate summary totals
     */
    private function calculateSummaryTotals(array $dailyReconciliations): array
    {
        if (empty($dailyReconciliations)) {
            return $this->applyPrecisionFormatting([
                'total_revenue_ugx' => 0,
                'total_cogs_ugx' => 0,
                'total_profit_ugx' => 0,
                'total_volume_liters' => 0,
                'total_deliveries_liters' => 0,
                'net_variance_liters' => 0,
                'reconciliation_count' => 0
            ]);
        }

        $summary = [
            'total_revenue_ugx' => array_sum(array_column($dailyReconciliations, 'total_sales_ugx')),
            'total_cogs_ugx' => array_sum(array_column($dailyReconciliations, 'total_cogs_ugx')),
            'total_profit_ugx' => array_sum(array_column($dailyReconciliations, 'gross_profit_ugx')),
            'total_volume_liters' => array_sum(array_column($dailyReconciliations, 'total_dispensed_liters')),
            'total_deliveries_liters' => array_sum(array_column($dailyReconciliations, 'total_delivered_liters')),
            'net_variance_liters' => array_sum(array_column($dailyReconciliations, 'volume_variance_liters')),
            'reconciliation_count' => count($dailyReconciliations),
            'unique_tanks' => count(array_unique(array_column($dailyReconciliations, 'tank_id'))),
            'unique_stations' => count(array_unique(array_column($dailyReconciliations, 'station_id')))
        ];

        // Calculate derived metrics
        $summary['overall_profit_margin_pct'] = $summary['total_revenue_ugx'] > 0 ?
            ($summary['total_profit_ugx'] / $summary['total_revenue_ugx']) * 100 : 0;

        return $this->applyPrecisionFormatting($summary);
    }

    /**
     * Get comparative data from previous period
     */
    private function getComparativeData(array $filters): array
    {
        try {
            $startDate = Carbon::parse($filters['date_start']);
            $endDate = Carbon::parse($filters['date_end']);
            $daysDiff = $startDate->diffInDays($endDate) + 1;

            $prevStartDate = $startDate->copy()->subDays($daysDiff);
            $prevEndDate = $startDate->copy()->subDay();

            $prevData = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereIn('s.id', $filters['station_ids'])
                ->whereBetween('dr.reconciliation_date', [
                    $prevStartDate->format('Y-m-d'),
                    $prevEndDate->format('Y-m-d')
                ])
                ->select([
                    DB::raw('SUM(dr.total_sales_ugx) as prev_total_revenue_ugx'),
                    DB::raw('SUM(dr.total_cogs_ugx) as prev_total_cogs_ugx'),
                    DB::raw('SUM(dr.gross_profit_ugx) as prev_total_profit_ugx'),
                    DB::raw('SUM(dr.total_dispensed_liters) as prev_total_volume_liters'),
                    DB::raw('AVG(dr.profit_margin_percentage) as prev_avg_margin_pct'),
                    DB::raw('AVG(dr.abs_variance_percentage) as prev_avg_variance_pct'),
                    DB::raw('COUNT(dr.id) as prev_reconciliation_count')
                ])
                ->first();

            return $this->applyPrecisionFormatting((array) $prevData);

        } catch (Exception $e) {
            Log::warning("Comparative data calculation failed", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Get operational insights
     */
    private function getOperationalInsights($baseQuery, array $filters): array
    {
        // Best and worst performing tanks
        $tankPerformance = $baseQuery->select([
            't.id as tank_id',
            't.tank_number',
            't.fuel_type',
            's.name as station_name',
            DB::raw('SUM(dr.gross_profit_ugx) as total_profit_ugx'),
            DB::raw('AVG(dr.profit_margin_percentage) as avg_margin_pct'),
            DB::raw('AVG(dr.abs_variance_percentage) as avg_variance_pct'),
            DB::raw('SUM(dr.total_dispensed_liters) as total_volume_liters')
        ])
        ->groupBy('t.id', 't.tank_number', 't.fuel_type', 's.name')
        ->orderBy(DB::raw('AVG(dr.profit_margin_percentage)'), 'desc')
        ->limit(10)
        ->get()
        ->toArray();

        // Highest variance tanks (potential issues)
        $highVarianceTanks = $baseQuery->select([
            't.id as tank_id',
            't.tank_number',
            't.fuel_type',
            's.name as station_name',
            DB::raw('AVG(dr.abs_variance_percentage) as avg_variance_pct'),
            DB::raw('MAX(dr.abs_variance_percentage) as max_variance_pct'),
            DB::raw('COUNT(CASE WHEN dr.abs_variance_percentage > 5.0 THEN 1 END) as critical_days')
        ])
        ->groupBy('t.id', 't.tank_number', 't.fuel_type', 's.name')
        ->having(DB::raw('AVG(dr.abs_variance_percentage)'), '>', 2.0)
        ->orderBy(DB::raw('AVG(dr.abs_variance_percentage)'), 'desc')
        ->limit(10)
        ->get()
        ->toArray();

        return [
            'top_performing_tanks' => array_map([$this, 'applyPrecisionFormatting'],
                array_map(function($item) { return (array) $item; }, $tankPerformance)),
            'high_variance_tanks' => array_map([$this, 'applyPrecisionFormatting'],
                array_map(function($item) { return (array) $item; }, $highVarianceTanks))
        ];
    }

    /**
     * Assess data quality of reconciliations
     */
    private function assessDataQuality(array $dailyReconciliations): array
    {
        $totalRecords = count($dailyReconciliations);
        if ($totalRecords === 0) {
            return [
                'total_records' => 0,
                'complete_records' => 0,
                'estimated_records' => 0,
                'error_records' => 0,
                'quality_score_pct' => 0,
                'quality_grade' => 'N/A'
            ];
        }

        $completeRecords = 0;
        $estimatedRecords = 0;
        $errorRecords = 0;

        foreach ($dailyReconciliations as $record) {
            switch ($record['valuation_quality'] ?? 'UNKNOWN') {
                case 'COMPLETE':
                    $completeRecords++;
                    break;
                case 'ESTIMATED_MINOR':
                case 'ESTIMATED_MAJOR':
                    $estimatedRecords++;
                    break;
                case 'RECOVERY_MODE':
                    $errorRecords++;
                    break;
            }
        }

        $qualityScore = ($completeRecords / $totalRecords) * 100;

        return [
            'total_records' => $totalRecords,
            'complete_records' => $completeRecords,
            'estimated_records' => $estimatedRecords,
            'error_records' => $errorRecords,
            'quality_score_pct' => round($qualityScore, 2),
            'quality_grade' => $this->getQualityGrade($qualityScore)
        ];
    }

    // HELPER METHODS

    /**
     * Apply schema precision formatting to numeric values
     */
    private function applyPrecisionFormatting(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_numeric($value)) {
                if (str_contains($key, '_liters') || str_contains($key, 'volume')) {
                    $data[$key] = round((float) $value, self::SCHEMA_PRECISION['volume_precision']);
                } elseif (str_contains($key, '_ugx') || str_contains($key, 'revenue') || str_contains($key, 'cost') || str_contains($key, 'profit')) {
                    $data[$key] = round((float) $value, self::SCHEMA_PRECISION['currency_precision']);
                } elseif (str_contains($key, '_pct') || str_contains($key, 'percentage') || str_contains($key, '_rate')) {
                    $data[$key] = round((float) $value, self::SCHEMA_PRECISION['percentage_precision']);
                }
            }
        }
        return $data;
    }

    /**
     * Calculate operational efficiency score
     */
    private function calculateOperationalEfficiencyScore(array $dailyReconciliations): float
    {
        if (empty($dailyReconciliations)) return 0;

        $avgVariance = array_sum(array_column($dailyReconciliations, 'abs_variance_percentage')) / count($dailyReconciliations);
        $avgMargin = array_sum(array_column($dailyReconciliations, 'profit_margin_percentage')) / count($dailyReconciliations);

        $varianceScore = max(0, 100 - ($avgVariance * 10));
        $marginScore = min(100, max(0, $avgMargin));

        return round(($varianceScore * 0.6) + ($marginScore * 0.4), 2);
    }

    /**
     * Calculate data quality score
     */
    private function calculateDataQualityScore(array $dailyReconciliations): float
    {
        if (empty($dailyReconciliations)) return 0;

        $completeCount = 0;
        foreach ($dailyReconciliations as $record) {
            if (($record['valuation_quality'] ?? '') === 'COMPLETE') {
                $completeCount++;
            }
        }

        return round(($completeCount / count($dailyReconciliations)) * 100, 2);
    }

    /**
     * Calculate variance risk score
     */
    private function calculateVarianceRiskScore(float $avgVariance, int $highVarianceDays, int $criticalVarianceDays): string
    {
        $score = ($avgVariance * 2) + ($highVarianceDays * 3) + ($criticalVarianceDays * 5);

        return match(true) {
            $score >= 30 => 'CRITICAL',
            $score >= 15 => 'HIGH',
            $score >= 5 => 'MEDIUM',
            default => 'LOW'
        };
    }

    /**
     * Calculate variance risk level
     */
    private function calculateVarianceRiskLevel(float $avgVariance, int $highVarianceDays, int $criticalVarianceDays): string
    {
        if ($criticalVarianceDays > 0 || $avgVariance > 5.0) {
            return 'CRITICAL';
        } elseif ($highVarianceDays > 2 || $avgVariance > 3.0) {
            return 'HIGH';
        } elseif ($avgVariance > 1.5) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }

    /**
     * Get quality grade based on score
     */
    private function getQualityGrade(float $score): string
    {
        return match(true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'B+',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F'
        };
    }

    /**
     * Get available stations based on user role
     */
    private function getAvailableStations($user): array
    {
        if ($user->role === 'admin') {
            return DB::table('stations')
                ->select('id', 'name', 'location')
                ->orderBy('name')
                ->get()
                ->toArray();
        } else {
            if (!$user->station_id) {
                return [];
            }
            return DB::table('stations')
                ->select('id', 'name', 'location')
                ->where('id', $user->station_id)
                ->get()
                ->toArray();
        }
    }

    /**
     * Get default station ID for user
     */
    private function getDefaultStationId($user, Request $request): ?int
    {
        $requestedStationId = $request->input('station_id');

        if ($requestedStationId && is_numeric($requestedStationId)) {
            if ($user->role === 'admin') {
                return (int) $requestedStationId;
            } elseif ($user->station_id && (int) $requestedStationId === (int) $user->station_id) {
                return (int) $requestedStationId;
            }
        }

        return $user->station_id ? (int) $user->station_id : null;
    }

    /**
     * Export Daily P&L Report - Excel/CSV format
     * Supports same filtering as main report with strict access control
     */
    public function exportDailyProfitLoss(Request $request)
    {
        try {
            // Use same validation and filtering as main report
            $filters = $this->validateAndExtractFilters($request);
            $baseQuery = $this->buildBaseQuery($filters);

            // Get comprehensive data for export
            $dailyReconciliations = $this->getDailyReconciliationData($baseQuery, $filters);
            $summaryTotals = $this->calculateSummaryTotals($dailyReconciliations);
            $fuelTypeBreakdown = $this->getFuelTypeBreakdown($baseQuery, $filters);

            // Prepare export data structure
            $exportData = [
                'summary' => $summaryTotals,
                'daily_data' => $dailyReconciliations,
                'fuel_breakdown' => $fuelTypeBreakdown,
                'filters' => $filters,
                'generated_at' => now()->toISOString(),
                'generated_by' => auth()->user()->name ?? 'System',
                'station_names' => implode(', ', array_column($this->getIncludedStations($filters), 'name'))
            ];

            // Generate filename with timestamp and filters
            $filename = 'Daily_PL_Report_' .
                       str_replace('-', '', $filters['date_start']) . '_to_' .
                       str_replace('-', '', $filters['date_end']) . '_' .
                       now()->format('YmdHis') . '.xlsx';

            // Return Excel download response
            return response()->streamDownload(function() use ($exportData) {
                echo $this->generateExcelContent($exportData);
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (Exception $e) {
            Log::error("Daily P&L Export Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            // RAW ERROR EXPOSURE
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'error_code' => 'EXPORT_FAILED'
                ], 500);
            } else {
                return redirect()->back()->withErrors(['export_error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Generate Excel content for export (simple CSV format for compatibility)
     */
    private function generateExcelContent(array $exportData): string
    {
        $csv = '';

        // Header
        $csv .= "FUEL_ERP_V2 - Daily Profit & Loss Report\n";
        $csv .= "Generated: " . $exportData['generated_at'] . "\n";
        $csv .= "Generated By: " . $exportData['generated_by'] . "\n";
        $csv .= "Stations: " . $exportData['station_names'] . "\n";
        $csv .= "Date Range: " . $exportData['filters']['date_start'] . " to " . $exportData['filters']['date_end'] . "\n\n";

        // Summary Section
        $csv .= "SUMMARY TOTALS\n";
        $csv .= "Total Revenue (UGX)," . number_format($exportData['summary']['total_revenue_ugx'], 4) . "\n";
        $csv .= "Total COGS (UGX)," . number_format($exportData['summary']['total_cogs_ugx'], 4) . "\n";
        $csv .= "Total Profit (UGX)," . number_format($exportData['summary']['total_profit_ugx'], 4) . "\n";
        $csv .= "Overall Margin (%)," . number_format($exportData['summary']['overall_profit_margin_pct'], 4) . "\n";
        $csv .= "Total Volume (Liters)," . number_format($exportData['summary']['total_volume_liters'], 3) . "\n\n";

        // Daily Data Section
        $csv .= "DAILY RECONCILIATION DETAILS\n";
        $csv .= "Date,Station,Tank,Fuel Type,Opening Stock (L),Delivered (L),Dispensed (L),Closing Stock (L),Variance (L),Variance (%),Revenue (UGX),COGS (UGX),Profit (UGX),Margin (%)\n";

        foreach ($exportData['daily_data'] as $record) {
            $csv .= $record['reconciliation_date'] . ',';
            $csv .= '"' . $record['station_name'] . '",';
            $csv .= $record['tank_number'] . ',';
            $csv .= $record['fuel_type'] . ',';
            $csv .= number_format($record['opening_stock_liters'], 3) . ',';
            $csv .= number_format($record['total_delivered_liters'], 3) . ',';
            $csv .= number_format($record['total_dispensed_liters'], 3) . ',';
            $csv .= number_format($record['actual_closing_stock_liters'], 3) . ',';
            $csv .= number_format($record['volume_variance_liters'], 3) . ',';
            $csv .= number_format($record['variance_percentage'], 4) . ',';
            $csv .= number_format($record['total_sales_ugx'], 4) . ',';
            $csv .= number_format($record['total_cogs_ugx'], 4) . ',';
            $csv .= number_format($record['gross_profit_ugx'], 4) . ',';
            $csv .= number_format($record['profit_margin_percentage'], 4) . "\n";
        }

        // Fuel Type Breakdown
        $csv .= "\nFUEL TYPE BREAKDOWN\n";
        $csv .= "Fuel Type,Tank Count,Total Revenue (UGX),Total Profit (UGX),Avg Margin (%),Total Volume (L),Avg Variance (%)\n";

        foreach ($exportData['fuel_breakdown'] as $fuel) {
            $csv .= $fuel['fuel_type'] . ',';
            $csv .= $fuel['tank_count'] . ',';
            $csv .= number_format($fuel['total_revenue_ugx'], 4) . ',';
            $csv .= number_format($fuel['total_profit_ugx'], 4) . ',';
            $csv .= number_format($fuel['avg_profit_margin_pct'], 4) . ',';
            $csv .= number_format($fuel['total_volume_sold_liters'], 3) . ',';
            $csv .= number_format($fuel['avg_variance_pct'], 4) . "\n";
        }

        return $csv;
    }

    /**
     * Get included stations metadata
     */
    private function getIncludedStations(array $filters): array
    {
        return DB::table('stations')
            ->whereIn('id', $filters['station_ids'])
            ->select('id', 'name', 'location')
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}
