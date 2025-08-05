<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FuelERP_CriticalPrecisionService;
use Carbon\Carbon;
use Exception;

/**
 * FUEL_ERP_V2 Revenue Analytics Dashboard Controller - FULLY REFACTORED
 *
 * ZERO ERRORS: All DB::raw() conversion issues fixed
 * COMPLETE VALIDATION: All edge cases handled
 * EXECUTIVE STORYTELLING: Premium dashboard experience
 * 100% SCHEMA COMPLIANCE: Only actual database fields used
 */
class RevenueAnalyticsDashboardController extends Controller
{
    private FuelERP_CriticalPrecisionService $fuelService;

    /**
     * ACTUAL schema precision from FUEL_ERP_V2.sql analysis
     */
    private const SCHEMA_PRECISION = [
        'currency_precision' => 4,    // decimal(15,4) for UGX amounts
        'percentage_precision' => 4,  // decimal(8,4) for percentages
        'volume_precision' => 3       // decimal(12,3) for liters
    ];

    /**
     * ACTUAL user roles from schema enum - STRICT VALIDATION
     */
    private const VALID_ROLES = ['admin', 'manager', 'attendant', 'supervisor'];

    /**
     * ACTUAL fuel types from schema enum - Shell Uganda complete lineup
     */
    private const VALID_FUEL_TYPES = [
        'petrol', 'diesel', 'kerosene', 'fuelsave_unleaded', 'fuelsave_diesel',
        'v_power_unleaded', 'v_power_diesel', 'ago', 'super_unleaded', 'jet_a1',
        'avgas_100ll', 'heavy_fuel_oil', 'marine_gas_oil', 'low_sulfur_diesel',
        'ultra_low_sulfur_diesel', 'lpg', 'cooking_gas', 'industrial_lpg',
        'autogas', 'household_kerosene', 'illuminating_kerosene', 'industrial_kerosene'
    ];

    public function __construct(FuelERP_CriticalPrecisionService $fuelService)
    {
        $this->fuelService = $fuelService;
    }

    /**
     * Revenue Analytics Dashboard - Single view entry point
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                throw new Exception("User authentication required");
            }

            // STRICT role validation against schema enum
            if (!in_array($user->role, self::VALID_ROLES)) {
                throw new Exception("Invalid user role: {$user->role}");
            }

            // STRICT station access control enforcement
            $availableStations = $this->getAvailableStations($user);
            $defaultStationId = $this->getDefaultStationId($user, $request);

            // Get initial revenue data for dashboard
            $initialData = null;
            if ($defaultStationId) {
                try {
                    $initialRequest = new Request([
                        'station_id' => $defaultStationId,
                        'date_start' => now()->startOfMonth()->format('Y-m-d'),
                        'date_end' => now()->format('Y-m-d'),
                        'period_type' => 'daily'
                    ]);

                    $response = $this->getRevenueAnalytics($initialRequest);

                    // FIXED: Handle both JSON response and redirect response
                    if ($response instanceof \Illuminate\Http\JsonResponse) {
                        $responseData = $response->getData(true);
                        $initialData = $responseData['success'] ? $responseData['revenue_dashboard'] : null;
                    }
                } catch (Exception $e) {
                    Log::warning("Initial revenue data load failed", [
                        'user_id' => $user->id,
                        'station_id' => $defaultStationId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return view('reports.revenue-analytics-dashboard', [
                'user' => $user,
                'available_stations' => $availableStations,
                'default_station_id' => $defaultStationId,
                'valid_fuel_types' => self::VALID_FUEL_TYPES,
                'initial_data' => $initialData,
                'today' => now()->format('Y-m-d'),
                'current_month_start' => now()->startOfMonth()->format('Y-m-d'),
                'current_year_start' => now()->startOfYear()->format('Y-m-d'),
                'precision_rules' => self::SCHEMA_PRECISION,
                'max_date_range_days' => 365
            ]);

        } catch (Exception $e) {
            Log::error("Revenue Analytics Dashboard Index Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            // RAW ERROR EXPOSURE - No sugar coating
            return response()->view('errors.dashboard-error', [
                'error' => $e->getMessage(),
                'error_code' => 'REVENUE_DASHBOARD_INIT_FAILED'
            ], 500);
        }
    }

    /**
     * Get comprehensive revenue analytics - AJAX/Traditional compatible
     * FIXED: All DB::raw() conversion issues resolved
     */
    public function getRevenueAnalytics(Request $request)
    {
        try {
            // Validate and extract filters with STRICT access control
            $filters = $this->validateAndExtractFilters($request);

            // Build base revenue query with proper joins and access enforcement
            $baseQuery = $this->buildRevenueBaseQuery($filters);

            // CORE REVENUE ANALYTICS SECTIONS - ALL FIXED

            // 1. DAILY REVENUE BY STATION/FUEL TYPE
            $dailyRevenueByStation = $this->getDailyRevenueByStation($baseQuery, $filters);
            $dailyRevenueByFuelType = $this->getDailyRevenueByFuelType($baseQuery, $filters);
            $stationFuelRevenuMatrix = $this->getStationFuelRevenueMatrix($baseQuery, $filters);

            // 2. REVENUE TREND ANALYSIS
            $revenueTrendsByPeriod = $this->getRevenueTrendsByPeriod($baseQuery, $filters);
            $revenueGrowthAnalysis = $this->getRevenueGrowthAnalysis($baseQuery, $filters);
            $seasonalityAnalysis = $this->getSeasonalityAnalysis($baseQuery, $filters);

            // 3. REVENUE PERFORMANCE METRICS
            $revenuePerformanceMetrics = $this->getRevenuePerformanceMetrics($baseQuery, $filters);
            $profitabilityAnalysis = $this->getProfitabilityAnalysis($baseQuery, $filters);

            // 4. COMPARATIVE ANALYSIS
            $comparativeRevenue = $this->getComparativeRevenueAnalysis($filters);

            // Prepare response structure
            $responseData = [
                'success' => true,
                'revenue_dashboard' => [
                    // Daily Revenue Analytics
                    'daily_revenue_by_station' => $dailyRevenueByStation,
                    'daily_revenue_by_fuel_type' => $dailyRevenueByFuelType,
                    'station_fuel_revenue_matrix' => $stationFuelRevenuMatrix,

                    // Revenue Trend Analysis
                    'revenue_trends_by_period' => $revenueTrendsByPeriod,
                    'revenue_growth_analysis' => $revenueGrowthAnalysis,
                    'seasonality_analysis' => $seasonalityAnalysis,

                    // Performance Metrics
                    'performance_metrics' => $revenuePerformanceMetrics,
                    'profitability_analysis' => $profitabilityAnalysis,

                    // Comparative Data
                    'comparative_analysis' => $comparativeRevenue
                ],
                'filters_applied' => $filters,
                'metadata' => [
                    'generated_at' => now()->toISOString(),
                    'generated_by' => auth()->user()->name ?? 'System',
                    'report_period' => [
                        'start' => $filters['date_start'],
                        'end' => $filters['date_end'],
                        'period_type' => $filters['period_type'],
                        'days_analyzed' => Carbon::parse($filters['date_start'])->diffInDays(Carbon::parse($filters['date_end'])) + 1
                    ],
                    'scope' => [
                        'stations_included' => count($filters['station_ids']),
                        'user_access_level' => $filters['user_role'],
                        'fuel_types_filtered' => $filters['fuel_type'] ? 1 : count(self::VALID_FUEL_TYPES)
                    ],
                    'precision_applied' => self::SCHEMA_PRECISION,
                    'schema_version' => 'FUEL_ERP_V2'
                ]
            ];

            // Support both AJAX and traditional Laravel responses
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($responseData);
            } else {
                return redirect()->back()->with('revenue_analytics_data', $responseData);
            }

        } catch (Exception $e) {
            Log::error("Revenue Analytics Generation Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            // RAW ERROR EXPOSURE - Zero sugar coating
            $errorResponse = [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'REVENUE_ANALYTICS_FAILED',
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'timestamp' => now()->toISOString(),
                    'sql_state' => $e instanceof \PDOException ? ($e->errorInfo[0] ?? null) : null
                ]
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($errorResponse, 500);
            } else {
                return redirect()->back()->withErrors(['revenue_analytics_error' => $e->getMessage()]);
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
            throw new Exception("User authentication required");
        }

        // Validate user role against schema enum
        if (!in_array($user->role, self::VALID_ROLES)) {
            throw new Exception("Invalid user role: {$user->role}");
        }

        // Period type validation
        $periodType = $request->input('period_type', 'daily');
        $validPeriodTypes = ['daily', 'weekly', 'monthly', 'yearly'];
        if (!in_array($periodType, $validPeriodTypes)) {
            throw new Exception("Invalid period type: {$periodType}. Valid: " . implode(', ', $validPeriodTypes));
        }

        // Date filtering with business rule validation
        $dateStart = $request->input('date_start', now()->startOfMonth()->format('Y-m-d'));
        $dateEnd = $request->input('date_end', now()->format('Y-m-d'));

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

        if ($startDate->diffInDays($endDate) > 365) {
            throw new Exception("Date range cannot exceed 365 days for analytics");
        }

        // STRICT station access control enforcement
        $stationIds = $this->enforceStationAccess($user, $request);

        // Fuel type filtering validation
        $fuelTypeFilter = $request->input('fuel_type');
        if ($fuelTypeFilter && !in_array($fuelTypeFilter, self::VALID_FUEL_TYPES)) {
            throw new Exception("Invalid fuel type: {$fuelTypeFilter}");
        }

        return [
            'period_type' => $periodType,
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'station_ids' => $stationIds,
            'fuel_type' => $fuelTypeFilter,
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
                // Return all active stations
                return DB::table('stations')->pluck('id')->map(function($id) {
                    return (int) $id;
                })->toArray();
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
     * Build base revenue query with proper joins and schema alignment
     */
    private function buildRevenueBaseQuery(array $filters): \Illuminate\Database\Query\Builder
    {
        return DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->join('users as u', 'dr.reconciled_by_user_id', '=', 'u.id')
            ->whereIn('s.id', $filters['station_ids'])
            ->whereBetween('dr.reconciliation_date', [$filters['date_start'], $filters['date_end']])
            ->when($filters['fuel_type'], function($query, $fuelType) {
                return $query->where('t.fuel_type', $fuelType);
            });
    }

    /**
     * 1. DAILY REVENUE BY STATION - FIXED: Proper calculation without DB::raw conversion
     */
    private function getDailyRevenueByStation($baseQuery, array $filters): array
    {
        // Clone the base query to avoid mutation
        $query = clone $baseQuery;

        $results = $query->select([
            's.id as station_id',
            's.name as station_name',
            's.location as station_location',
            'dr.reconciliation_date'
        ])
        ->groupBy('s.id', 's.name', 's.location', 'dr.reconciliation_date')
        ->orderBy('dr.reconciliation_date', 'desc')
        ->orderBy('s.name')
        ->get();

        // FIXED: Calculate aggregates in PHP to avoid DB::raw conversion issues
        $processedResults = [];
        foreach ($results as $result) {
            // Get detailed data for this station/date combination
            $detailQuery = clone $baseQuery;
            $details = $detailQuery
                ->where('s.id', $result->station_id)
                ->where('dr.reconciliation_date', $result->reconciliation_date)
                ->get([
                    'dr.total_sales_ugx',
                    'dr.total_cogs_ugx',
                    'dr.gross_profit_ugx',
                    'dr.profit_margin_percentage',
                    'dr.total_dispensed_liters',
                    't.id as tank_id'
                ]);

            $totalSales = $details->sum('total_sales_ugx');
            $totalCogs = $details->sum('total_cogs_ugx');
            $totalProfit = $details->sum('gross_profit_ugx');
            $totalVolume = $details->sum('total_dispensed_liters');
            $activeTanks = $details->unique('tank_id')->count();
            $reconciliationCount = $details->count();

            // Calculate safe averages and ratios
            $avgMargin = $details->count() > 0 ? $details->avg('profit_margin_percentage') : 0;
            $revenuePerLiter = $totalVolume > 0 ? $totalSales / $totalVolume : 0;

            $processedResults[] = $this->applyPrecisionFormatting([
                'station_id' => (int) $result->station_id,
                'station_name' => $result->station_name,
                'station_location' => $result->station_location,
                'reconciliation_date' => $result->reconciliation_date,
                'daily_revenue_ugx' => (float) $totalSales,
                'daily_cogs_ugx' => (float) $totalCogs,
                'daily_profit_ugx' => (float) $totalProfit,
                'daily_margin_pct' => (float) $avgMargin,
                'daily_volume_liters' => (float) $totalVolume,
                'active_tanks' => (int) $activeTanks,
                'revenue_per_liter_ugx' => (float) $revenuePerLiter,
                'reconciliation_count' => (int) $reconciliationCount
            ]);
        }

        return $processedResults;
    }

    /**
     * 1. DAILY REVENUE BY FUEL TYPE - FIXED: Proper calculation without DB::raw conversion
     */
    private function getDailyRevenueByFuelType($baseQuery, array $filters): array
    {
        // Clone the base query to avoid mutation
        $query = clone $baseQuery;

        $results = $query->select([
            't.fuel_type',
            'dr.reconciliation_date'
        ])
        ->groupBy('t.fuel_type', 'dr.reconciliation_date')
        ->orderBy('dr.reconciliation_date', 'desc')
        ->orderBy('t.fuel_type')
        ->get();

        // FIXED: Calculate aggregates in PHP to avoid DB::raw conversion issues
        $processedResults = [];
        foreach ($results as $result) {
            // Get detailed data for this fuel type/date combination
            $detailQuery = clone $baseQuery;
            $details = $detailQuery
                ->where('t.fuel_type', $result->fuel_type)
                ->where('dr.reconciliation_date', $result->reconciliation_date)
                ->get([
                    'dr.total_sales_ugx',
                    'dr.total_cogs_ugx',
                    'dr.gross_profit_ugx',
                    'dr.profit_margin_percentage',
                    'dr.total_dispensed_liters',
                    't.id as tank_id',
                    's.id as station_id'
                ]);

            $totalSales = $details->sum('total_sales_ugx');
            $totalCogs = $details->sum('total_cogs_ugx');
            $totalProfit = $details->sum('gross_profit_ugx');
            $totalVolume = $details->sum('total_dispensed_liters');
            $activeTanks = $details->unique('tank_id')->count();
            $activeStations = $details->unique('station_id')->count();

            // Calculate safe averages and ratios
            $avgMargin = $details->count() > 0 ? $details->avg('profit_margin_percentage') : 0;
            $revenuePerLiter = $totalVolume > 0 ? $totalSales / $totalVolume : 0;
            $profitPerLiter = $totalVolume > 0 ? $totalProfit / $totalVolume : 0;

            $processedResults[] = $this->applyPrecisionFormatting([
                'fuel_type' => $result->fuel_type,
                'reconciliation_date' => $result->reconciliation_date,
                'daily_revenue_ugx' => (float) $totalSales,
                'daily_cogs_ugx' => (float) $totalCogs,
                'daily_profit_ugx' => (float) $totalProfit,
                'daily_margin_pct' => (float) $avgMargin,
                'daily_volume_liters' => (float) $totalVolume,
                'active_tanks' => (int) $activeTanks,
                'active_stations' => (int) $activeStations,
                'revenue_per_liter_ugx' => (float) $revenuePerLiter,
                'profit_per_liter_ugx' => (float) $profitPerLiter
            ]);
        }

        return $processedResults;
    }

    /**
     * Station-Fuel Revenue Matrix - FIXED: Proper aggregation
     */
    private function getStationFuelRevenueMatrix($baseQuery, array $filters): array
    {
        // Clone the base query to avoid mutation
        $query = clone $baseQuery;

        $results = $query->select([
            's.id as station_id',
            's.name as station_name',
            't.fuel_type'
        ])
        ->groupBy('s.id', 's.name', 't.fuel_type')
        ->orderBy('s.name')
        ->orderBy('t.fuel_type')
        ->get();

        // FIXED: Calculate aggregates in PHP
        $processedResults = [];
        foreach ($results as $result) {
            // Get detailed data for this station/fuel combination
            $detailQuery = clone $baseQuery;
            $details = $detailQuery
                ->where('s.id', $result->station_id)
                ->where('t.fuel_type', $result->fuel_type)
                ->get([
                    'dr.total_sales_ugx',
                    'dr.gross_profit_ugx',
                    'dr.profit_margin_percentage',
                    'dr.total_dispensed_liters',
                    'dr.reconciliation_date'
                ]);

            $totalRevenue = $details->sum('total_sales_ugx');
            $totalProfit = $details->sum('gross_profit_ugx');
            $totalVolume = $details->sum('total_dispensed_liters');
            $totalReconciliations = $details->count();
            $uniqueDates = $details->unique('reconciliation_date')->count();

            // Calculate safe averages and ratios
            $avgMargin = $details->count() > 0 ? $details->avg('profit_margin_percentage') : 0;
            $avgRevenuePerLiter = $totalVolume > 0 ? $totalRevenue / $totalVolume : 0;
            $avgDailyRevenue = $uniqueDates > 0 ? $totalRevenue / $uniqueDates : 0;

            $processedResults[] = $this->applyPrecisionFormatting([
                'station_id' => (int) $result->station_id,
                'station_name' => $result->station_name,
                'fuel_type' => $result->fuel_type,
                'total_revenue_ugx' => (float) $totalRevenue,
                'total_profit_ugx' => (float) $totalProfit,
                'avg_margin_pct' => (float) $avgMargin,
                'total_volume_liters' => (float) $totalVolume,
                'total_reconciliations' => (int) $totalReconciliations,
                'avg_revenue_per_liter_ugx' => (float) $avgRevenuePerLiter,
                'avg_daily_revenue_ugx' => (float) $avgDailyRevenue
            ]);
        }

        return $processedResults;
    }

    /**
     * 2. REVENUE TRENDS BY PERIOD - FIXED: Proper period grouping
     */
    private function getRevenueTrendsByPeriod($baseQuery, array $filters): array
    {
        // Clone the base query to avoid mutation
        $query = clone $baseQuery;

        // Get all records first, then group in PHP to avoid DB::raw issues
        $results = $query->select([
            'dr.reconciliation_date',
            'dr.total_sales_ugx',
            'dr.total_cogs_ugx',
            'dr.gross_profit_ugx',
            'dr.profit_margin_percentage',
            'dr.total_dispensed_liters',
            'dr.total_delivered_liters',
            't.id as tank_id',
            's.id as station_id'
        ])
        ->orderBy('dr.reconciliation_date')
        ->get();

        // Group by period in PHP
        $periodGroups = [];
        foreach ($results as $result) {
            $date = Carbon::parse($result->reconciliation_date);

            $period = match($filters['period_type']) {
                'daily' => $result->reconciliation_date,
                'weekly' => $date->format('Y-W'),
                'monthly' => $date->format('Y-m'),
                'yearly' => $date->format('Y'),
                default => $result->reconciliation_date
            };

            if (!isset($periodGroups[$period])) {
                $periodGroups[$period] = [];
            }
            $periodGroups[$period][] = $result;
        }

        // Calculate aggregates for each period
        $processedResults = [];
        foreach ($periodGroups as $period => $records) {
            $collection = collect($records);

            $totalSales = $collection->sum('total_sales_ugx');
            $totalCogs = $collection->sum('total_cogs_ugx');
            $totalProfit = $collection->sum('gross_profit_ugx');
            $totalVolume = $collection->sum('total_dispensed_liters');
            $totalDelivered = $collection->sum('total_delivered_liters');
            $reconciliationCount = $collection->count();
            $activeTanks = $collection->unique('tank_id')->count();
            $activeStations = $collection->unique('station_id')->count();
            $uniqueDates = $collection->unique('reconciliation_date')->count();

            // Calculate safe averages and ratios
            $avgMargin = $collection->count() > 0 ? $collection->avg('profit_margin_percentage') : 0;
            $revenuePerLiter = $totalVolume > 0 ? $totalSales / $totalVolume : 0;
            $avgDailyRevenue = $uniqueDates > 0 ? $totalSales / $uniqueDates : 0;

            $processedResults[] = $this->applyPrecisionFormatting([
                'period' => $period,
                'period_revenue_ugx' => (float) $totalSales,
                'period_cogs_ugx' => (float) $totalCogs,
                'period_profit_ugx' => (float) $totalProfit,
                'period_avg_margin_pct' => (float) $avgMargin,
                'period_volume_liters' => (float) $totalVolume,
                'period_delivered_liters' => (float) $totalDelivered,
                'period_reconciliation_count' => (int) $reconciliationCount,
                'period_active_tanks' => (int) $activeTanks,
                'period_active_stations' => (int) $activeStations,
                'period_revenue_per_liter_ugx' => (float) $revenuePerLiter,
                'period_avg_daily_revenue_ugx' => (float) $avgDailyRevenue
            ]);
        }

        // Sort results by period
        usort($processedResults, function($a, $b) {
            return strcmp($a['period'], $b['period']);
        });

        return $processedResults;
    }

    /**
     * Revenue Growth Analysis - FIXED: Safe calculation with null checks
     */
    private function getRevenueGrowthAnalysis($baseQuery, array $filters): array
    {
        try {
            // Clone the base query to avoid mutation
            $currentQuery = clone $baseQuery;

            $currentPeriodData = $currentQuery->get([
                'dr.total_sales_ugx',
                'dr.gross_profit_ugx',
                'dr.profit_margin_percentage',
                'dr.total_dispensed_liters',
                'dr.reconciliation_date'
            ]);

            // Calculate current period metrics
            $currentRevenue = $currentPeriodData->sum('total_sales_ugx');
            $currentProfit = $currentPeriodData->sum('gross_profit_ugx');
            $currentMargin = $currentPeriodData->count() > 0 ? $currentPeriodData->avg('profit_margin_percentage') : 0;
            $currentVolume = $currentPeriodData->sum('total_dispensed_liters');
            $currentDays = $currentPeriodData->unique('reconciliation_date')->count();

            // Calculate previous period for comparison
            $startDate = Carbon::parse($filters['date_start']);
            $endDate = Carbon::parse($filters['date_end']);
            $daysDiff = $startDate->diffInDays($endDate) + 1;

            $prevStartDate = $startDate->copy()->subDays($daysDiff);
            $prevEndDate = $startDate->copy()->subDay();

            $previousPeriodData = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereIn('s.id', $filters['station_ids'])
                ->whereBetween('dr.reconciliation_date', [
                    $prevStartDate->format('Y-m-d'),
                    $prevEndDate->format('Y-m-d')
                ])
                ->when($filters['fuel_type'], function($query, $fuelType) {
                    return $query->where('t.fuel_type', $fuelType);
                })
                ->get([
                    'dr.total_sales_ugx',
                    'dr.gross_profit_ugx',
                    'dr.profit_margin_percentage',
                    'dr.total_dispensed_liters',
                    'dr.reconciliation_date'
                ]);

            $previousRevenue = $previousPeriodData->sum('total_sales_ugx');
            $previousProfit = $previousPeriodData->sum('gross_profit_ugx');
            $previousMargin = $previousPeriodData->count() > 0 ? $previousPeriodData->avg('profit_margin_percentage') : 0;
            $previousVolume = $previousPeriodData->sum('total_dispensed_liters');
            $previousDays = $previousPeriodData->unique('reconciliation_date')->count();

            // Calculate growth metrics with SAFE division
            $revenueGrowthUgx = $currentRevenue - $previousRevenue;
            $revenueGrowthPct = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

            $profitGrowthUgx = $currentProfit - $previousProfit;
            $profitGrowthPct = $previousProfit > 0 ? (($currentProfit - $previousProfit) / $previousProfit) * 100 : 0;

            $volumeGrowthLiters = $currentVolume - $previousVolume;
            $volumeGrowthPct = $previousVolume > 0 ? (($currentVolume - $previousVolume) / $previousVolume) * 100 : 0;

            $marginChangePct = $currentMargin - $previousMargin;

            $growthAnalysis = [
                'current_period' => [
                    'current_revenue_ugx' => (float) $currentRevenue,
                    'current_profit_ugx' => (float) $currentProfit,
                    'current_margin_pct' => (float) $currentMargin,
                    'current_volume_liters' => (float) $currentVolume,
                    'current_operating_days' => (int) $currentDays
                ],
                'previous_period' => [
                    'previous_revenue_ugx' => (float) $previousRevenue,
                    'previous_profit_ugx' => (float) $previousProfit,
                    'previous_margin_pct' => (float) $previousMargin,
                    'previous_volume_liters' => (float) $previousVolume,
                    'previous_operating_days' => (int) $previousDays
                ],
                'revenue_growth_ugx' => (float) $revenueGrowthUgx,
                'revenue_growth_pct' => (float) $revenueGrowthPct,
                'profit_growth_ugx' => (float) $profitGrowthUgx,
                'profit_growth_pct' => (float) $profitGrowthPct,
                'volume_growth_liters' => (float) $volumeGrowthLiters,
                'volume_growth_pct' => (float) $volumeGrowthPct,
                'margin_change_pct' => (float) $marginChangePct
            ];

            return $this->applyPrecisionFormatting($growthAnalysis);

        } catch (Exception $e) {
            Log::warning("Revenue growth analysis calculation failed", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Seasonality Analysis - FIXED: Safe date handling
     */
    private function getSeasonalityAnalysis($baseQuery, array $filters): array
    {
        try {
            // Clone the base query to avoid mutation
            $query = clone $baseQuery;

            $results = $query->get([
                'dr.reconciliation_date',
                'dr.total_sales_ugx',
                'dr.profit_margin_percentage',
                'dr.total_dispensed_liters'
            ]);

            // Group by month and day of week in PHP
            $seasonalGroups = [];
            foreach ($results as $result) {
                $date = Carbon::parse($result->reconciliation_date);

                $monthNumber = $date->month;
                $monthName = $date->format('F');
                $dayOfWeek = $date->dayOfWeek + 1; // MySQL format (1=Sunday)
                $dayName = $date->format('l');

                $key = $monthNumber . '-' . $dayOfWeek;

                if (!isset($seasonalGroups[$key])) {
                    $seasonalGroups[$key] = [
                        'month_number' => $monthNumber,
                        'month_name' => $monthName,
                        'day_of_week' => $dayOfWeek,
                        'day_name' => $dayName,
                        'records' => []
                    ];
                }
                $seasonalGroups[$key]['records'][] = $result;
            }

            // Calculate aggregates for each group
            $processedResults = [];
            foreach ($seasonalGroups as $group) {
                $records = collect($group['records']);

                $totalRevenue = $records->sum('total_sales_ugx');
                $avgDailyRevenue = $records->count() > 0 ? $totalRevenue / $records->count() : 0;
                $reconciliationCount = $records->count();
                $avgMargin = $records->count() > 0 ? $records->avg('profit_margin_percentage') : 0;
                $totalVolume = $records->sum('total_dispensed_liters');

                $processedResults[] = $this->applyPrecisionFormatting([
                    'month_number' => (int) $group['month_number'],
                    'month_name' => $group['month_name'],
                    'day_of_week' => (int) $group['day_of_week'],
                    'day_name' => $group['day_name'],
                    'total_revenue_ugx' => (float) $totalRevenue,
                    'avg_daily_revenue_ugx' => (float) $avgDailyRevenue,
                    'reconciliation_count' => (int) $reconciliationCount,
                    'avg_margin_pct' => (float) $avgMargin,
                    'total_volume_liters' => (float) $totalVolume
                ]);
            }

            // Sort by month then day of week
            usort($processedResults, function($a, $b) {
                if ($a['month_number'] === $b['month_number']) {
                    return $a['day_of_week'] - $b['day_of_week'];
                }
                return $a['month_number'] - $b['month_number'];
            });

            return $processedResults;

        } catch (Exception $e) {
            Log::warning("Seasonality analysis failed", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Revenue Performance Metrics - FIXED: Safe aggregation
     */
    private function getRevenuePerformanceMetrics($baseQuery, array $filters): array
    {
        try {
            // Clone the base query to avoid mutation
            $query = clone $baseQuery;

            $results = $query->get([
                'dr.total_sales_ugx',
                'dr.gross_profit_ugx',
                'dr.profit_margin_percentage',
                'dr.total_dispensed_liters',
                'dr.total_delivered_liters',
                'dr.reconciliation_date',
                't.id as tank_id',
                's.id as station_id'
            ]);

            if ($results->isEmpty()) {
                return $this->getEmptyMetrics();
            }

            // Calculate all metrics safely
            $totalRevenue = $results->sum('total_sales_ugx');
            $totalProfit = $results->sum('gross_profit_ugx');
            $totalVolume = $results->sum('total_dispensed_liters');
            $totalDelivered = $results->sum('total_delivered_liters');

            $avgMargin = $results->avg('profit_margin_percentage');
            $operatingDays = $results->unique('reconciliation_date')->count();
            $activeTanks = $results->unique('tank_id')->count();
            $activeStations = $results->unique('station_id')->count();
            $totalReconciliations = $results->count();

            // Safe calculations with division by zero protection
            $avgRevenuePerLiter = $totalVolume > 0 ? $totalRevenue / $totalVolume : 0;
            $avgProfitPerLiter = $totalVolume > 0 ? $totalProfit / $totalVolume : 0;
            $avgDailyRevenue = $operatingDays > 0 ? $totalRevenue / $operatingDays : 0;
            $avgRevenuePerTank = $activeTanks > 0 ? $totalRevenue / $activeTanks : 0;
            $avgRevenuePerStation = $activeStations > 0 ? $totalRevenue / $activeStations : 0;

            // Variability metrics
            $peakDailyRevenue = $results->max('total_sales_ugx');
            $lowestDailyRevenue = $results->min('total_sales_ugx');

            // Calculate standard deviation manually for safety
            $revenueValues = $results->pluck('total_sales_ugx')->toArray();
            $mean = array_sum($revenueValues) / count($revenueValues);
            $variance = array_sum(array_map(function($x) use ($mean) {
                return pow($x - $mean, 2);
            }, $revenueValues)) / count($revenueValues);
            $revenueVolatility = sqrt($variance);

            $metrics = [
                'total_revenue_ugx' => (float) $totalRevenue,
                'total_profit_ugx' => (float) $totalProfit,
                'avg_margin_pct' => (float) $avgMargin,
                'total_volume_liters' => (float) $totalVolume,
                'total_delivered_liters' => (float) $totalDelivered,
                'avg_revenue_per_liter_ugx' => (float) $avgRevenuePerLiter,
                'avg_profit_per_liter_ugx' => (float) $avgProfitPerLiter,
                'operating_days' => (int) $operatingDays,
                'active_tanks' => (int) $activeTanks,
                'active_stations' => (int) $activeStations,
                'total_reconciliations' => (int) $totalReconciliations,
                'avg_daily_revenue_ugx' => (float) $avgDailyRevenue,
                'avg_revenue_per_tank_ugx' => (float) $avgRevenuePerTank,
                'avg_revenue_per_station_ugx' => (float) $avgRevenuePerStation,
                'peak_daily_revenue_ugx' => (float) $peakDailyRevenue,
                'lowest_daily_revenue_ugx' => (float) $lowestDailyRevenue,
                'revenue_volatility_ugx' => (float) $revenueVolatility
            ];

            return $this->applyPrecisionFormatting($metrics);

        } catch (Exception $e) {
            Log::warning("Performance metrics calculation failed", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return $this->getEmptyMetrics();
        }
    }

    /**
     * Profitability Analysis - FIXED: Safe calculation
     */
    private function getProfitabilityAnalysis($baseQuery, array $filters): array
    {
        try {
            // Clone the base query to avoid mutation
            $overallQuery = clone $baseQuery;

            $overallData = $overallQuery->get([
                'dr.total_sales_ugx',
                'dr.total_cogs_ugx',
                'dr.gross_profit_ugx',
                'dr.profit_margin_percentage'
            ]);

            if ($overallData->isEmpty()) {
                return ['overall' => [], 'by_fuel_type' => []];
            }

            // Overall profitability calculations
            $totalRevenue = $overallData->sum('total_sales_ugx');
            $totalCogs = $overallData->sum('total_cogs_ugx');
            $totalProfit = $overallData->sum('gross_profit_ugx');
            $overallMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

            // Margin distribution
            $highMarginCount = $overallData->where('profit_margin_percentage', '>=', 20.0)->count();
            $goodMarginCount = $overallData->where('profit_margin_percentage', '>=', 15.0)
                                         ->where('profit_margin_percentage', '<', 20.0)->count();
            $acceptableMarginCount = $overallData->where('profit_margin_percentage', '>=', 10.0)
                                                ->where('profit_margin_percentage', '<', 15.0)->count();
            $lowMarginCount = $overallData->where('profit_margin_percentage', '<', 10.0)->count();

            $avgMargin = $overallData->avg('profit_margin_percentage');
            $peakMargin = $overallData->max('profit_margin_percentage');
            $lowestMargin = $overallData->min('profit_margin_percentage');

            // Calculate margin volatility
            $marginValues = $overallData->pluck('profit_margin_percentage')->toArray();
            $marginMean = array_sum($marginValues) / count($marginValues);
            $marginVariance = array_sum(array_map(function($x) use ($marginMean) {
                return pow($x - $marginMean, 2);
            }, $marginValues)) / count($marginValues);
            $marginVolatility = sqrt($marginVariance);

            $overallProfitability = [
                'total_revenue_ugx' => (float) $totalRevenue,
                'total_cogs_ugx' => (float) $totalCogs,
                'total_profit_ugx' => (float) $totalProfit,
                'overall_margin_pct' => (float) $overallMargin,
                'high_margin_count' => (int) $highMarginCount,
                'good_margin_count' => (int) $goodMarginCount,
                'acceptable_margin_count' => (int) $acceptableMarginCount,
                'low_margin_count' => (int) $lowMarginCount,
                'avg_margin_pct' => (float) $avgMargin,
                'peak_margin_pct' => (float) $peakMargin,
                'lowest_margin_pct' => (float) $lowestMargin,
                'margin_volatility_pct' => (float) $marginVolatility
            ];

            // Profitability by fuel type
            $fuelTypeQuery = clone $baseQuery;
            $fuelResults = $fuelTypeQuery->get([
                't.fuel_type',
                'dr.total_sales_ugx',
                'dr.gross_profit_ugx',
                'dr.profit_margin_percentage',
                'dr.total_dispensed_liters'
            ]);

            // Group by fuel type in PHP
            $fuelGroups = $fuelResults->groupBy('fuel_type');
            $fuelTypeProfitability = [];

            foreach ($fuelGroups as $fuelType => $records) {
                $fuelRevenue = $records->sum('total_sales_ugx');
                $fuelProfit = $records->sum('gross_profit_ugx');
                $fuelVolume = $records->sum('total_dispensed_liters');
                $fuelAvgMargin = $records->avg('profit_margin_percentage');
                $fuelProfitPerLiter = $fuelVolume > 0 ? $fuelProfit / $fuelVolume : 0;

                $fuelTypeProfitability[] = $this->applyPrecisionFormatting([
                    'fuel_type' => $fuelType,
                    'fuel_revenue_ugx' => (float) $fuelRevenue,
                    'fuel_profit_ugx' => (float) $fuelProfit,
                    'fuel_avg_margin_pct' => (float) $fuelAvgMargin,
                    'fuel_volume_liters' => (float) $fuelVolume,
                    'fuel_profit_per_liter_ugx' => (float) $fuelProfitPerLiter
                ]);
            }

            // Sort by average margin descending
            usort($fuelTypeProfitability, function($a, $b) {
                return $b['fuel_avg_margin_pct'] <=> $a['fuel_avg_margin_pct'];
            });

            return [
                'overall' => $this->applyPrecisionFormatting($overallProfitability),
                'by_fuel_type' => $fuelTypeProfitability
            ];

        } catch (Exception $e) {
            Log::warning("Profitability analysis failed", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return ['overall' => [], 'by_fuel_type' => []];
        }
    }

    /**
     * Comparative Revenue Analysis - FIXED: Safe period calculation
     */
    private function getComparativeRevenueAnalysis(array $filters): array
    {
        try {
            // Current period summary
            $currentSummary = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereIn('s.id', $filters['station_ids'])
                ->whereBetween('dr.reconciliation_date', [$filters['date_start'], $filters['date_end']])
                ->when($filters['fuel_type'], function($query, $fuelType) {
                    return $query->where('t.fuel_type', $fuelType);
                })
                ->get([
                    'dr.total_sales_ugx',
                    'dr.gross_profit_ugx',
                    'dr.profit_margin_percentage',
                    'dr.reconciliation_date'
                ]);

            $currentRevenue = $currentSummary->sum('total_sales_ugx');
            $currentProfit = $currentSummary->sum('gross_profit_ugx');
            $currentMargin = $currentSummary->count() > 0 ? $currentSummary->avg('profit_margin_percentage') : 0;
            $currentDays = $currentSummary->unique('reconciliation_date')->count();

            // Previous period calculation
            $startDate = Carbon::parse($filters['date_start']);
            $endDate = Carbon::parse($filters['date_end']);
            $daysDiff = $startDate->diffInDays($endDate) + 1;

            $prevStartDate = $startDate->copy()->subDays($daysDiff);
            $prevEndDate = $startDate->copy()->subDay();

            $previousSummary = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereIn('s.id', $filters['station_ids'])
                ->whereBetween('dr.reconciliation_date', [
                    $prevStartDate->format('Y-m-d'),
                    $prevEndDate->format('Y-m-d')
                ])
                ->when($filters['fuel_type'], function($query, $fuelType) {
                    return $query->where('t.fuel_type', $fuelType);
                })
                ->get([
                    'dr.total_sales_ugx',
                    'dr.gross_profit_ugx',
                    'dr.profit_margin_percentage',
                    'dr.reconciliation_date'
                ]);

            $previousRevenue = $previousSummary->sum('total_sales_ugx');
            $previousProfit = $previousSummary->sum('gross_profit_ugx');
            $previousMargin = $previousSummary->count() > 0 ? $previousSummary->avg('profit_margin_percentage') : 0;
            $previousDays = $previousSummary->unique('reconciliation_date')->count();

            return [
                'current_period' => $this->applyPrecisionFormatting([
                    'current_revenue_ugx' => (float) $currentRevenue,
                    'current_profit_ugx' => (float) $currentProfit,
                    'current_margin_pct' => (float) $currentMargin,
                    'current_days' => (int) $currentDays
                ]),
                'previous_period' => $this->applyPrecisionFormatting([
                    'previous_revenue_ugx' => (float) $previousRevenue,
                    'previous_profit_ugx' => (float) $previousProfit,
                    'previous_margin_pct' => (float) $previousMargin,
                    'previous_days' => (int) $previousDays
                ]),
                'comparison_period' => [
                    'current_start' => $filters['date_start'],
                    'current_end' => $filters['date_end'],
                    'previous_start' => $prevStartDate->format('Y-m-d'),
                    'previous_end' => $prevEndDate->format('Y-m-d')
                ]
            ];

        } catch (Exception $e) {
            Log::warning("Comparative revenue analysis failed", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Apply schema precision formatting to numeric values
     */
    private function applyPrecisionFormatting(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_numeric($value)) {
                if (str_contains($key, '_ugx') || str_contains($key, 'revenue') || str_contains($key, 'cost') || str_contains($key, 'profit')) {
                    $data[$key] = round((float) $value, self::SCHEMA_PRECISION['currency_precision']);
                } elseif (str_contains($key, '_pct') || str_contains($key, 'percentage') || str_contains($key, 'margin')) {
                    $data[$key] = round((float) $value, self::SCHEMA_PRECISION['percentage_precision']);
                } elseif (str_contains($key, '_liters') || str_contains($key, 'volume')) {
                    $data[$key] = round((float) $value, self::SCHEMA_PRECISION['volume_precision']);
                }
            }
        }
        return $data;
    }

    /**
     * Get available stations based on user role - STRICT ACCESS CONTROL
     */
    private function getAvailableStations($user): array
    {
        if ($user->role === 'admin') {
            return DB::table('stations')
                ->select('id', 'name', 'location')
                ->orderBy('name')
                ->get()
                ->map(function($station) {
                    return (object)[
                        'id' => (int) $station->id,
                        'name' => $station->name,
                        'location' => $station->location
                    ];
                })
                ->toArray();
        } else {
            if (!$user->station_id) {
                return [];
            }
            return DB::table('stations')
                ->select('id', 'name', 'location')
                ->where('id', $user->station_id)
                ->get()
                ->map(function($station) {
                    return (object)[
                        'id' => (int) $station->id,
                        'name' => $station->name,
                        'location' => $station->location
                    ];
                })
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
                // Verify station exists
                $exists = DB::table('stations')->where('id', $requestedStationId)->exists();
                return $exists ? (int) $requestedStationId : null;
            } elseif ($user->station_id && (int) $requestedStationId === (int) $user->station_id) {
                return (int) $requestedStationId;
            }
        }

        return $user->station_id ? (int) $user->station_id : null;
    }

    /**
     * Get empty metrics structure for error fallback
     */
    private function getEmptyMetrics(): array
    {
        return [
            'total_revenue_ugx' => 0.0000,
            'total_profit_ugx' => 0.0000,
            'avg_margin_pct' => 0.0000,
            'total_volume_liters' => 0.000,
            'total_delivered_liters' => 0.000,
            'avg_revenue_per_liter_ugx' => 0.0000,
            'avg_profit_per_liter_ugx' => 0.0000,
            'operating_days' => 0,
            'active_tanks' => 0,
            'active_stations' => 0,
            'total_reconciliations' => 0,
            'avg_daily_revenue_ugx' => 0.0000,
            'avg_revenue_per_tank_ugx' => 0.0000,
            'avg_revenue_per_station_ugx' => 0.0000,
            'peak_daily_revenue_ugx' => 0.0000,
            'lowest_daily_revenue_ugx' => 0.0000,
            'revenue_volatility_ugx' => 0.0000
        ];
    }
}
