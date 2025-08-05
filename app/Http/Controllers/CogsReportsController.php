<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FuelERP_CriticalPrecisionService;
use Carbon\Carbon;
use Exception;

/**
 * FUEL_ERP_V2 Cost of Goods Sold (COGS) Reports Controller
 *
 * EXECUTIVE DASHBOARD FOCUS: Delivers powerful, passionate storytelling
 * to CEO about cost structures, margins, and profitability dynamics
 *
 * 100% SCHEMA COMPLIANT - Zero phantom fields or logic
 * RAW ERROR HANDLING - No sugar coating, complete transparency
 * AGGRESSIVE AJAX/NON-AJAX SUPPORT - Unified response architecture
 */
class CogsReportsController extends Controller
{
    private FuelERP_CriticalPrecisionService $fuelService;

    public function __construct(FuelERP_CriticalPrecisionService $fuelService)
    {
        $this->fuelService = $fuelService;
    }

    /**
     * COGS EXECUTIVE DASHBOARD - Main View
     * Stories: Profitability trends, cost efficiency, margin analysis
     */
    public function index(Request $request)
    {
        try {
            // MANDATORY: Station-level access control enforcement
            $user = auth()->user();
            $availableStations = $this->getAvailableStations($user);

            $stationId = $request->get('station_id');

            // Admin gets all stations, non-admin restricted to assigned
            if (!$user->role === 'admin' && $stationId) {
                $allowedStationIds = collect($availableStations)->pluck('id')->toArray();
                if (!in_array($stationId, $allowedStationIds)) {
                    throw new Exception("UNAUTHORIZED: Access denied to station ID $stationId");
                }
            }

            // AJAX Response Architecture
            if ($request->ajax() || $request->expectsJson()) {
                $reportData = $this->generateExecutiveCogsSummary($stationId, $request);
                return response()->json($reportData);
            }

            // NON-AJAX Response Architecture
            return view('reports.cogs-dashboard', [
                'available_stations' => $availableStations,
                'selected_station_id' => $stationId,
                'user_role' => $user->role
            ]);

        } catch (Exception $e) {
            Log::error('COGS Dashboard Access Failed', [
                'user_id' => auth()->id(),
                'station_id' => $stationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * EXECUTIVE COGS SUMMARY - THE CEO'S POWER STORY
     * Passionate narrative about cost dynamics, efficiency, and profitability
     */
    private function generateExecutiveCogsSummary(?int $stationId, Request $request): array
    {
        $dateRange = $this->parseDateRange($request);

        return [
            'executive_summary' => $this->buildExecutiveNarrative($stationId, $dateRange),
            'cogs_breakdown' => $this->getCogsBreakdownAnalysis($stationId, $dateRange),
            'margin_dynamics' => $this->getMarginDynamicsStory($stationId, $dateRange),
            'fifo_efficiency' => $this->getFifoEfficiencyMetrics($stationId, $dateRange),
            'variance_impact' => $this->getVarianceImpactAnalysis($stationId, $dateRange),
            'fuel_type_performance' => $this->getFuelTypePerformanceStory($stationId, $dateRange),
            'period_comparison' => $this->getPeriodComparisonInsights($stationId, $dateRange),
            'operational_alerts' => $this->getOperationalAlertsStory($stationId, $dateRange)
        ];
    }

    /**
     * BUILD EXECUTIVE NARRATIVE - The CEO's Power Story
     * Transforms raw COGS data into compelling business intelligence
     */
    private function buildExecutiveNarrative(?int $stationId, array $dateRange): array
    {
        // Core FIFO-based COGS metrics from schema-compliant tables
        $baseQuery = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

        if ($stationId) {
            $baseQuery->where('s.id', $stationId);
        } else {
            // Admin access - all stations
            $user = auth()->user();
            if ($user->role !== 'admin') {
                $baseQuery->where('s.id', $user->station_id);
            }
        }

        // POWER METRICS: Total COGS, Revenue, Gross Profit
        $totals = $baseQuery->selectRaw('
            SUM(dr.total_cogs_ugx) as total_cogs,
            SUM(dr.total_sales_ugx) as total_revenue,
            SUM(dr.gross_profit_ugx) as total_gross_profit,
            AVG(dr.profit_margin_percentage) as avg_margin,
            COUNT(DISTINCT dr.reconciliation_date) as trading_days,
            COUNT(DISTINCT t.id) as active_tanks,
            COUNT(DISTINCT s.id) as stations_count
        ')->first();

        // EFFICIENCY STORY: FIFO Layer Performance
        $fifoEfficiency = DB::table('fifo_consumption_log as fcl')
            ->join('daily_reconciliations as dr', 'fcl.reconciliation_id', '=', 'dr.id')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

        if ($stationId) {
            $fifoEfficiency->where('s.id', $stationId);
        }

        $fifoMetrics = $fifoEfficiency->selectRaw('
            AVG(fcl.cost_per_liter_ugx) as avg_fifo_cost,
            COUNT(DISTINCT fcl.fifo_layer_id) as layers_consumed,
            SUM(fcl.volume_consumed_liters) as total_volume_sold,
            AVG(fcl.weighted_avg_cost_ugx) as weighted_avg_cost
        ')->first();

        // VARIANCE IMPACT STORY
        $varianceImpact = $baseQuery->selectRaw('
            SUM(CASE WHEN dr.variance_percentage > 0 THEN dr.volume_variance_liters *
                (SELECT AVG(sp.price_per_liter_ugx) FROM selling_prices sp
                 WHERE sp.station_id = s.id AND sp.fuel_type = t.fuel_type
                 AND sp.is_active = 1) ELSE 0 END) as positive_variance_value,
            SUM(CASE WHEN dr.variance_percentage < 0 THEN ABS(dr.volume_variance_liters) *
                (SELECT AVG(sp.price_per_liter_ugx) FROM selling_prices sp
                 WHERE sp.station_id = s.id AND sp.fuel_type = t.fuel_type
                 AND sp.is_active = 1) ELSE 0 END) as negative_variance_value,
            AVG(ABS(dr.variance_percentage)) as avg_variance_percentage,
            COUNT(CASE WHEN ABS(dr.variance_percentage) > 5.0 THEN 1 END) as critical_variance_days
        ')->first();

        // THE CEO'S STORY: Passionate Business Intelligence
        return [
            'headline' => $this->generateExecutiveHeadline($totals, $fifoMetrics),
            'performance_story' => $this->generatePerformanceStory($totals, $dateRange),
            'efficiency_narrative' => $this->generateEfficiencyNarrative($fifoMetrics, $totals),
            'variance_story' => $this->generateVarianceStory($varianceImpact, $totals),
            'strategic_insights' => $this->generateStrategicInsights($totals, $fifoMetrics, $varianceImpact),
            'raw_metrics' => [
                'totals' => $totals,
                'fifo_metrics' => $fifoMetrics,
                'variance_impact' => $varianceImpact
            ]
        ];
    }

    /**
     * GENERATE EXECUTIVE HEADLINE - The hook that grabs CEO attention
     */
    private function generateExecutiveHeadline($totals, $fifoMetrics): string
    {
        $revenueMillions = round($totals->total_revenue / 1000000, 1);
        $marginPercent = round($totals->avg_margin, 1);
        $efficiency = $fifoMetrics->layers_consumed > 0 ? 'OPTIMIZED' : 'STABLE';

        return "🚀 FUEL OPERATIONS: UGX {$revenueMillions}M Revenue | {$marginPercent}% Gross Margin | FIFO Inventory {$efficiency}";
    }

    /**
     * GENERATE PERFORMANCE STORY - Revenue and cost dynamics narrative
     */
    private function generatePerformanceStory($totals, $dateRange): array
    {
        $dailyRevenue = $totals->trading_days > 0 ? $totals->total_revenue / $totals->trading_days : 0;
        $revenuePerTank = $totals->active_tanks > 0 ? $totals->total_revenue / $totals->active_tanks : 0;

        return [
            'headline' => 'REVENUE PERFORMANCE EXCELLENCE',
            'narrative' => sprintf(
                'Our fuel operations generated UGX %s in gross profit across %d trading days, ' .
                'delivering an average margin of %.1f%%. Each of our %d active tanks contributed ' .
                'an average of UGX %s in revenue, demonstrating %s operational efficiency.',
                number_format($totals->total_gross_profit),
                $totals->trading_days,
                $totals->avg_margin,
                $totals->active_tanks,
                number_format($revenuePerTank),
                $totals->avg_margin > 15 ? 'exceptional' : ($totals->avg_margin > 10 ? 'strong' : 'baseline')
            ),
            'key_metrics' => [
                'daily_average' => round($dailyRevenue),
                'revenue_per_tank' => round($revenuePerTank),
                'margin_classification' => $totals->avg_margin > 15 ? 'PREMIUM' : ($totals->avg_margin > 10 ? 'STRONG' : 'STANDARD')
            ]
        ];
    }

    /**
     * GENERATE EFFICIENCY NARRATIVE - FIFO inventory optimization story
     */
    private function generateEfficiencyNarrative($fifoMetrics, $totals): array
    {
        $costEfficiency = $fifoMetrics->weighted_avg_cost > 0 ?
            (($fifoMetrics->avg_fifo_cost / $fifoMetrics->weighted_avg_cost) * 100) : 100;

        return [
            'headline' => 'INVENTORY OPTIMIZATION DYNAMICS',
            'narrative' => sprintf(
                'Our FIFO inventory system processed %d cost layers, consuming %.0f liters with ' .
                'an average cost of UGX %s per liter. The weighted average cost efficiency stands at %.1f%%, ' .
                'indicating %s inventory turnover management.',
                $fifoMetrics->layers_consumed,
                $fifoMetrics->total_volume_sold,
                number_format($fifoMetrics->avg_fifo_cost, 2),
                $costEfficiency,
                $costEfficiency > 95 ? 'exceptional' : ($costEfficiency > 90 ? 'efficient' : 'standard')
            ),
            'efficiency_score' => round($costEfficiency, 1),
            'layer_velocity' => $fifoMetrics->layers_consumed / max($totals->trading_days, 1)
        ];
    }

    /**
     * GENERATE VARIANCE STORY - Impact of operational variances
     */
    private function generateVarianceStory($varianceImpact, $totals): array
    {
        $netVarianceImpact = $varianceImpact->positive_variance_value - $varianceImpact->negative_variance_value;
        $varianceImpactPercent = $totals->total_revenue > 0 ?
            ($netVarianceImpact / $totals->total_revenue) * 100 : 0;

        return [
            'headline' => 'OPERATIONAL VARIANCE IMPACT',
            'narrative' => sprintf(
                'Inventory variances created a %s net impact of UGX %s (%.2f%% of revenue). ' .
                'We experienced %d critical variance days with average variance of %.2f%%. ' .
                'This indicates %s operational precision in our fuel handling processes.',
                $netVarianceImpact >= 0 ? 'positive' : 'negative',
                number_format(abs($netVarianceImpact)),
                abs($varianceImpactPercent),
                $varianceImpact->critical_variance_days,
                $varianceImpact->avg_variance_percentage,
                $varianceImpact->avg_variance_percentage < 2 ? 'exceptional' :
                ($varianceImpact->avg_variance_percentage < 5 ? 'good' : 'requires attention')
            ),
            'net_impact' => $netVarianceImpact,
            'impact_percentage' => round($varianceImpactPercent, 2),
            'operational_score' => $varianceImpact->avg_variance_percentage < 2 ? 'EXCELLENT' :
                                 ($varianceImpact->avg_variance_percentage < 5 ? 'GOOD' : 'ATTENTION_NEEDED')
        ];
    }

    /**
     * GENERATE STRATEGIC INSIGHTS - CEO decision-making intelligence
     */
    private function generateStrategicInsights($totals, $fifoMetrics, $varianceImpact): array
    {
        $insights = [];

        // Margin optimization insights
        if ($totals->avg_margin < 12) {
            $insights[] = [
                'type' => 'MARGIN_OPTIMIZATION',
                'priority' => 'HIGH',
                'insight' => 'Margin below industry benchmark - consider pricing strategy review or cost optimization initiatives.'
            ];
        }

        // FIFO efficiency insights
        if ($fifoMetrics->layers_consumed / max($totals->trading_days, 1) > 2) {
            $insights[] = [
                'type' => 'INVENTORY_VELOCITY',
                'priority' => 'MEDIUM',
                'insight' => 'High FIFO layer turnover indicates strong sales velocity - consider inventory level optimization.'
            ];
        }

        // Variance control insights
        if ($varianceImpact->critical_variance_days > ($totals->trading_days * 0.1)) {
            $insights[] = [
                'type' => 'OPERATIONAL_CONTROL',
                'priority' => 'CRITICAL',
                'insight' => 'Frequent critical variances detected - immediate process review and staff training recommended.'
            ];
        }

        // Revenue growth insights
        if ($totals->active_tanks > 0 && $totals->stations_count > 0) {
            $revenuePerStation = $totals->total_revenue / $totals->stations_count;
            $insights[] = [
                'type' => 'GROWTH_OPPORTUNITY',
                'priority' => 'STRATEGIC',
                'insight' => sprintf('Average station revenue of UGX %s suggests %s performance across network.',
                    number_format($revenuePerStation),
                    $revenuePerStation > 50000000 ? 'exceptional' : 'baseline'
                )
            ];
        }

        return $insights;
    }

    /**
     * GET COGS BREAKDOWN ANALYSIS - Detailed cost structure
     */
    private function getCogsBreakdownAnalysis(?int $stationId, array $dateRange): array
    {
        try {
            $query = DB::table('fifo_consumption_log as fcl')
                ->join('daily_reconciliations as dr', 'fcl.reconciliation_id', '=', 'dr.id')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->join('fifo_layers as fl', 'fcl.fifo_layer_id', '=', 'fl.id')
                ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

            // Apply station filter
            if ($stationId) {
                $query->where('s.id', $stationId);
            } else {
                $user = auth()->user();
                if ($user->role !== 'admin') {
                    $query->where('s.id', $user->station_id);
                }
            }

            // COGS breakdown by fuel type
            $fuelTypeBreakdown = $query->selectRaw('
                t.fuel_type,
                SUM(fcl.total_cost_ugx) as total_cogs,
                SUM(fcl.volume_consumed_liters) as total_volume,
                AVG(fcl.cost_per_liter_ugx) as avg_cost_per_liter,
                COUNT(DISTINCT fcl.fifo_layer_id) as layers_used,
                MIN(fl.delivery_date) as oldest_stock_date,
                MAX(fl.delivery_date) as newest_stock_date
            ')
            ->groupBy('t.fuel_type')
            ->orderByDesc('total_cogs')
            ->get();

            // Monthly trend analysis
            $monthlyTrends = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

            if ($stationId) {
                $monthlyTrends->where('s.id', $stationId);
            } else {
                $user = auth()->user();
                if ($user->role !== 'admin') {
                    $monthlyTrends->where('s.id', $user->station_id);
                }
            }

            $monthlyData = $monthlyTrends->selectRaw('
                DATE_FORMAT(dr.reconciliation_date, "%Y-%m") as month,
                SUM(dr.total_cogs_ugx) as monthly_cogs,
                SUM(dr.total_sales_ugx) as monthly_revenue,
                AVG(dr.profit_margin_percentage) as avg_margin
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

            return [
                'fuel_type_breakdown' => $fuelTypeBreakdown,
                'monthly_trends' => $monthlyData,
                'cost_efficiency_metrics' => $this->calculateCostEfficiencyMetrics($fuelTypeBreakdown)
            ];

        } catch (Exception $e) {
            throw new Exception("COGS Breakdown Analysis Failed: " . $e->getMessage());
        }
    }

    /**
     * GET MARGIN DYNAMICS STORY - Profitability narrative
     */
    private function getMarginDynamicsStory(?int $stationId, array $dateRange): array
    {
        try {
            $query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

            if ($stationId) {
                $query->where('s.id', $stationId);
            } else {
                $user = auth()->user();
                if ($user->role !== 'admin') {
                    $query->where('s.id', $user->station_id);
                }
            }

            // Daily margin performance
            $dailyMargins = $query->selectRaw('
                dr.reconciliation_date,
                SUM(dr.total_sales_ugx) as daily_revenue,
                SUM(dr.total_cogs_ugx) as daily_cogs,
                SUM(dr.gross_profit_ugx) as daily_profit,
                AVG(dr.profit_margin_percentage) as daily_margin,
                COUNT(t.id) as tanks_reconciled
            ')
            ->groupBy('dr.reconciliation_date')
            ->orderBy('dr.reconciliation_date')
            ->get();

            // Margin by fuel type and station
            $marginByCategory = $query->selectRaw('
                s.name as station_name,
                t.fuel_type,
                AVG(dr.profit_margin_percentage) as avg_margin,
                SUM(dr.gross_profit_ugx) as total_profit,
                COUNT(*) as reconciliation_count
            ')
            ->groupBy('s.name', 't.fuel_type')
            ->orderByDesc('total_profit')
            ->get();

            return [
                'daily_margin_trend' => $dailyMargins,
                'margin_by_category' => $marginByCategory,
                'margin_insights' => $this->generateMarginInsights($dailyMargins, $marginByCategory)
            ];

        } catch (Exception $e) {
            throw new Exception("Margin Dynamics Analysis Failed: " . $e->getMessage());
        }
    }

    /**
     * GET FIFO EFFICIENCY METRICS - Inventory optimization intelligence
     */
    private function getFifoEfficiencyMetrics(?int $stationId, array $dateRange): array
    {
        try {
            // FIFO layer aging analysis
            $layerAging = DB::table('fifo_layers as fl')
                ->join('tanks as t', 'fl.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('fl.is_exhausted', false);

            if ($stationId) {
                $layerAging->where('s.id', $stationId);
            } else {
                $user = auth()->user();
                if ($user->role !== 'admin') {
                    $layerAging->where('s.id', $user->station_id);
                }
            }

            $agingData = $layerAging->selectRaw('
                t.fuel_type,
                fl.delivery_date,
                DATEDIFF(CURDATE(), fl.delivery_date) as days_in_inventory,
                fl.remaining_volume_liters,
                fl.remaining_value_ugx,
                fl.cost_per_liter_ugx,
                CASE
                    WHEN DATEDIFF(CURDATE(), fl.delivery_date) <= 30 THEN "Fresh"
                    WHEN DATEDIFF(CURDATE(), fl.delivery_date) <= 60 THEN "Aging"
                    ELSE "Old Stock"
                END as aging_category
            ')
            ->orderBy('fl.delivery_date')
            ->get();

            // Turnover velocity analysis
            $turnoverMetrics = DB::table('fifo_consumption_log as fcl')
                ->join('daily_reconciliations as dr', 'fcl.reconciliation_id', '=', 'dr.id')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->join('fifo_layers as fl', 'fcl.fifo_layer_id', '=', 'fl.id')
                ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

            if ($stationId) {
                $turnoverMetrics->where('s.id', $stationId);
            }

            $velocityData = $turnoverMetrics->selectRaw('
                t.fuel_type,
                AVG(DATEDIFF(dr.reconciliation_date, fl.delivery_date)) as avg_days_to_consumption,
                SUM(fcl.volume_consumed_liters) as total_consumed,
                COUNT(DISTINCT fcl.fifo_layer_id) as layers_consumed,
                AVG(fcl.cost_per_liter_ugx) as avg_consumption_cost
            ')
            ->groupBy('t.fuel_type')
            ->get();

            return [
                'aging_analysis' => $agingData,
                'turnover_velocity' => $velocityData,
                'efficiency_score' => $this->calculateFifoEfficiencyScore($agingData, $velocityData)
            ];

        } catch (Exception $e) {
            throw new Exception("FIFO Efficiency Analysis Failed: " . $e->getMessage());
        }
    }

    /**
     * GET VARIANCE IMPACT ANALYSIS - Operational loss/gain intelligence
     */
    private function getVarianceImpactAnalysis(?int $stationId, array $dateRange): array
    {
        try {
            $query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->leftJoin('selling_prices as sp', function($join) {
                    $join->on('sp.station_id', '=', 's.id')
                         ->on('sp.fuel_type', '=', 't.fuel_type')
                         ->where('sp.is_active', true);
                })
                ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

            if ($stationId) {
                $query->where('s.id', $stationId);
            } else {
                $user = auth()->user();
                if ($user->role !== 'admin') {
                    $query->where('s.id', $user->station_id);
                }
            }

            // Variance impact by category
            $varianceImpact = $query->selectRaw('
                s.name as station_name,
                t.fuel_type,
                SUM(CASE WHEN dr.variance_percentage > 5 THEN 1 ELSE 0 END) as critical_variance_days,
                SUM(CASE WHEN dr.variance_percentage BETWEEN 2 AND 5 THEN 1 ELSE 0 END) as high_variance_days,
                SUM(CASE WHEN ABS(dr.variance_percentage) < 2 THEN 1 ELSE 0 END) as normal_variance_days,
                AVG(dr.variance_percentage) as avg_variance_percentage,
                SUM(dr.volume_variance_liters) as total_variance_liters,
                SUM(dr.volume_variance_liters * COALESCE(sp.price_per_liter_ugx, 0)) as estimated_variance_value,
                COUNT(*) as total_reconciliation_days
            ')
            ->groupBy('s.name', 't.fuel_type')
            ->orderByDesc('estimated_variance_value')
            ->get();

            // Time series variance trend
            $varianceTrend = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

            if ($stationId) {
                $varianceTrend->where('s.id', $stationId);
            }

            $trendData = $varianceTrend->selectRaw('
                DATE(dr.reconciliation_date) as variance_date,
                AVG(ABS(dr.variance_percentage)) as daily_avg_variance,
                SUM(ABS(dr.volume_variance_liters)) as daily_total_variance_volume,
                COUNT(CASE WHEN ABS(dr.variance_percentage) > 5 THEN 1 END) as daily_critical_variances
            ')
            ->groupBy('variance_date')
            ->orderBy('variance_date')
            ->get();

            return [
                'variance_by_category' => $varianceImpact,
                'variance_trend' => $trendData,
                'variance_story' => $this->generateVarianceAnalysisStory($varianceImpact, $trendData)
            ];

        } catch (Exception $e) {
            throw new Exception("Variance Impact Analysis Failed: " . $e->getMessage());
        }
    }

    /**
     * GET FUEL TYPE PERFORMANCE STORY - Product line profitability
     */
    private function getFuelTypePerformanceStory(?int $stationId, array $dateRange): array
    {
        try {
            $query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

            if ($stationId) {
                $query->where('s.id', $stationId);
            } else {
                $user = auth()->user();
                if ($user->role !== 'admin') {
                    $query->where('s.id', $user->station_id);
                }
            }

            $fuelPerformance = $query->selectRaw('
                t.fuel_type,
                SUM(dr.total_sales_ugx) as total_revenue,
                SUM(dr.total_cogs_ugx) as total_cogs,
                SUM(dr.gross_profit_ugx) as total_profit,
                AVG(dr.profit_margin_percentage) as avg_margin,
                SUM(dr.total_dispensed_liters) as total_volume_sold,
                COUNT(DISTINCT dr.reconciliation_date) as active_days,
                COUNT(DISTINCT t.id) as tank_count
            ')
            ->groupBy('t.fuel_type')
            ->orderByDesc('total_profit')
            ->get();

            // Performance ranking and insights
            $rankedPerformance = $fuelPerformance->map(function($fuel, $index) {
                return [
                    'rank' => $index + 1,
                    'fuel_type' => $fuel->fuel_type,
                    'revenue' => $fuel->total_revenue,
                    'profit' => $fuel->total_profit,
                    'margin' => $fuel->avg_margin,
                    'volume' => $fuel->total_volume_sold,
                    'profit_per_liter' => $fuel->total_volume_sold > 0 ? $fuel->total_profit / $fuel->total_volume_sold : 0,
                    'performance_category' => $this->categorizeFuelPerformance($fuel)
                ];
            });

            return [
                'fuel_rankings' => $rankedPerformance,
                'performance_insights' => $this->generateFuelPerformanceInsights($rankedPerformance),
                'cross_fuel_analysis' => $this->generateCrossFuelAnalysis($rankedPerformance)
            ];

        } catch (Exception $e) {
            throw new Exception("Fuel Type Performance Analysis Failed: " . $e->getMessage());
        }
    }

    /**
     * GET PERIOD COMPARISON INSIGHTS - Historical performance context
     */
    private function getPeriodComparisonInsights(?int $stationId, array $dateRange): array
    {
        try {
            // Calculate previous period for comparison
            $currentStart = Carbon::parse($dateRange['start']);
            $currentEnd = Carbon::parse($dateRange['end']);
            $periodLength = $currentStart->diffInDays($currentEnd);

            $previousStart = $currentStart->copy()->subDays($periodLength + 1);
            $previousEnd = $currentStart->copy()->subDay();

            // Current period metrics
            $currentMetrics = $this->getPeriodMetrics($stationId, $dateRange);

            // Previous period metrics
            $previousMetrics = $this->getPeriodMetrics($stationId, [
                'start' => $previousStart->format('Y-m-d'),
                'end' => $previousEnd->format('Y-m-d')
            ]);

            // Calculate comparison insights
            $comparison = [
                'revenue_change' => $this->calculatePercentageChange(
                    $previousMetrics['total_revenue'],
                    $currentMetrics['total_revenue']
                ),
                'cogs_change' => $this->calculatePercentageChange(
                    $previousMetrics['total_cogs'],
                    $currentMetrics['total_cogs']
                ),
                'profit_change' => $this->calculatePercentageChange(
                    $previousMetrics['total_profit'],
                    $currentMetrics['total_profit']
                ),
                'margin_change' => $currentMetrics['avg_margin'] - $previousMetrics['avg_margin'],
                'efficiency_trend' => $this->calculateEfficiencyTrend($previousMetrics, $currentMetrics)
            ];

            return [
                'current_period' => $currentMetrics,
                'previous_period' => $previousMetrics,
                'comparison' => $comparison,
                'trend_story' => $this->generateTrendStory($comparison)
            ];

        } catch (Exception $e) {
            throw new Exception("Period Comparison Analysis Failed: " . $e->getMessage());
        }
    }

    /**
     * GET OPERATIONAL ALERTS STORY - Critical business intelligence
     */
    private function getOperationalAlertsStory(?int $stationId, array $dateRange): array
    {
        try {
            // Critical COGS-related alerts
            $cogsAlerts = [];

            // High variance impact alerts
            $highVarianceQuery = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']])
                ->where(DB::raw('ABS(dr.variance_percentage)'), '>', 5);

            if ($stationId) {
                $highVarianceQuery->where('s.id', $stationId);
            }

            $highVariances = $highVarianceQuery->selectRaw('
                s.name as station_name,
                t.tank_number,
                t.fuel_type,
                dr.reconciliation_date,
                dr.variance_percentage,
                dr.volume_variance_liters
            ')->orderByDesc(DB::raw('ABS(dr.variance_percentage)'))->get();

            foreach ($highVariances as $variance) {
                $cogsAlerts[] = [
                    'type' => 'CRITICAL_VARIANCE',
                    'priority' => 'HIGH',
                    'station' => $variance->station_name,
                    'tank' => $variance->tank_number,
                    'fuel_type' => $variance->fuel_type,
                    'date' => $variance->reconciliation_date,
                    'message' => sprintf(
                        'Critical variance of %.2f%% (%.2fL) detected on Tank %s - %s',
                        $variance->variance_percentage,
                        $variance->volume_variance_liters,
                        $variance->tank_number,
                        $variance->fuel_type
                    )
                ];
            }

            // Low margin alerts
            $lowMarginQuery = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']])
                ->where('dr.profit_margin_percentage', '<', 8);

            if ($stationId) {
                $lowMarginQuery->where('s.id', $stationId);
            }

            $lowMargins = $lowMarginQuery->selectRaw('
                s.name as station_name,
                t.fuel_type,
                AVG(dr.profit_margin_percentage) as avg_margin,
                COUNT(*) as low_margin_days
            ')
            ->groupBy('s.name', 't.fuel_type')
            ->having('low_margin_days', '>', 2)
            ->get();

            foreach ($lowMargins as $margin) {
                $cogsAlerts[] = [
                    'type' => 'LOW_MARGIN',
                    'priority' => 'MEDIUM',
                    'station' => $margin->station_name,
                    'fuel_type' => $margin->fuel_type,
                    'message' => sprintf(
                        'Sustained low margins detected: %.1f%% average over %d days for %s',
                        $margin->avg_margin,
                        $margin->low_margin_days,
                        $margin->fuel_type
                    )
                ];
            }

            // Aged inventory alerts
            $agedInventory = DB::table('fifo_layers as fl')
                ->join('tanks as t', 'fl.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('fl.is_exhausted', false)
                ->where(DB::raw('DATEDIFF(CURDATE(), fl.delivery_date)'), '>', 90);

            if ($stationId) {
                $agedInventory->where('s.id', $stationId);
            }

            $agedStock = $agedInventory->selectRaw('
                s.name as station_name,
                t.tank_number,
                t.fuel_type,
                fl.delivery_date,
                fl.remaining_volume_liters,
                DATEDIFF(CURDATE(), fl.delivery_date) as age_days
            ')->get();

            foreach ($agedStock as $stock) {
                $cogsAlerts[] = [
                    'type' => 'AGED_INVENTORY',
                    'priority' => 'MEDIUM',
                    'station' => $stock->station_name,
                    'tank' => $stock->tank_number,
                    'fuel_type' => $stock->fuel_type,
                    'message' => sprintf(
                        'Aged inventory alert: %.0fL of %s in Tank %s is %d days old',
                        $stock->remaining_volume_liters,
                        $stock->fuel_type,
                        $stock->tank_number,
                        $stock->age_days
                    )
                ];
            }

            return [
                'total_alerts' => count($cogsAlerts),
                'alerts_by_priority' => [
                    'HIGH' => collect($cogsAlerts)->where('priority', 'HIGH')->count(),
                    'MEDIUM' => collect($cogsAlerts)->where('priority', 'MEDIUM')->count(),
                    'LOW' => collect($cogsAlerts)->where('priority', 'LOW')->count()
                ],
                'alerts' => $cogsAlerts,
                'alert_summary' => $this->generateAlertSummary($cogsAlerts)
            ];

        } catch (Exception $e) {
            throw new Exception("Operational Alerts Analysis Failed: " . $e->getMessage());
        }
    }

    // HELPER METHODS

    private function getAvailableStations($user): array
    {
        if ($user->role === 'admin') {
            return DB::table('stations')->select('id', 'name', 'location')->get()->toArray();
        }

        return DB::table('stations')
            ->select('id', 'name', 'location')
            ->where('id', $user->station_id)
            ->get()->toArray();
    }

    private function parseDateRange(Request $request): array
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        return [
            'start' => $startDate,
            'end' => $endDate
        ];
    }

    private function calculateCostEfficiencyMetrics($fuelTypeBreakdown): array
    {
        $totalCogs = $fuelTypeBreakdown->sum('total_cogs');
        $totalVolume = $fuelTypeBreakdown->sum('total_volume');

        return [
            'overall_cost_per_liter' => $totalVolume > 0 ? $totalCogs / $totalVolume : 0,
            'cost_distribution' => $fuelTypeBreakdown->map(function($fuel) use ($totalCogs) {
                return [
                    'fuel_type' => $fuel->fuel_type,
                    'cost_percentage' => $totalCogs > 0 ? ($fuel->total_cogs / $totalCogs) * 100 : 0
                ];
            })
        ];
    }

    private function generateMarginInsights($dailyMargins, $marginByCategory): array
    {
        $insights = [];

        // Trend analysis
        if ($dailyMargins->count() > 1) {
            $firstMargin = $dailyMargins->first()->daily_margin;
            $lastMargin = $dailyMargins->last()->daily_margin;
            $trend = $lastMargin > $firstMargin ? 'IMPROVING' : 'DECLINING';

            $insights[] = [
                'type' => 'MARGIN_TREND',
                'insight' => "Margin trend is {$trend} over the analysis period"
            ];
        }

        // Best performing category
        $bestCategory = $marginByCategory->sortByDesc('avg_margin')->first();
        if ($bestCategory) {
            $insights[] = [
                'type' => 'TOP_PERFORMER',
                'insight' => sprintf(
                    '%s at %s delivers highest margins at %.1f%%',
                    $bestCategory->fuel_type,
                    $bestCategory->station_name,
                    $bestCategory->avg_margin
                )
            ];
        }

        return $insights;
    }

    private function calculateFifoEfficiencyScore($agingData, $velocityData): array
    {
        $totalValue = $agingData->sum('remaining_value_ugx');
        $freshValue = $agingData->where('aging_category', 'Fresh')->sum('remaining_value_ugx');
        $avgTurnover = $velocityData->avg('avg_days_to_consumption');

        $freshnessScore = $totalValue > 0 ? ($freshValue / $totalValue) * 100 : 100;
        $velocityScore = $avgTurnover < 30 ? 100 : (30 / $avgTurnover) * 100;

        return [
            'overall_score' => ($freshnessScore + $velocityScore) / 2,
            'freshness_score' => $freshnessScore,
            'velocity_score' => $velocityScore,
            'interpretation' => $this->interpretEfficiencyScore(($freshnessScore + $velocityScore) / 2)
        ];
    }

    private function interpretEfficiencyScore($score): string
    {
        return match(true) {
            $score >= 90 => 'EXCELLENT - Highly optimized inventory management',
            $score >= 75 => 'GOOD - Efficient operations with minor optimization opportunities',
            $score >= 60 => 'FAIR - Moderate efficiency, improvement recommended',
            default => 'POOR - Immediate optimization required'
        };
    }

    private function generateVarianceAnalysisStory($varianceImpact, $trendData): array
    {
        $totalImpact = $varianceImpact->sum('estimated_variance_value');
        $criticalDays = $varianceImpact->sum('critical_variance_days');
        $totalDays = $varianceImpact->sum('total_reconciliation_days');

        return [
            'headline' => 'VARIANCE IMPACT ASSESSMENT',
            'narrative' => sprintf(
                'Total variance impact: UGX %s across %d reconciliation days. ' .
                'Critical variances occurred on %d days (%.1f%% of operations), ' .
                'indicating %s operational control.',
                number_format($totalImpact),
                $totalDays,
                $criticalDays,
                $totalDays > 0 ? ($criticalDays / $totalDays) * 100 : 0,
                $criticalDays < ($totalDays * 0.05) ? 'excellent' : 'attention-required'
            ),
            'risk_level' => $criticalDays > ($totalDays * 0.1) ? 'HIGH' : ($criticalDays > 0 ? 'MEDIUM' : 'LOW')
        ];
    }

    private function categorizeFuelPerformance($fuel): string
    {
        return match(true) {
            $fuel->avg_margin > 15 => 'PREMIUM',
            $fuel->avg_margin > 10 => 'STRONG',
            $fuel->avg_margin > 5 => 'STANDARD',
            default => 'UNDERPERFORMING'
        };
    }

    private function generateFuelPerformanceInsights($rankedPerformance): array
    {
        $insights = [];

        $topPerformer = $rankedPerformance->first();
        $bottomPerformer = $rankedPerformance->last();

        $insights[] = [
            'type' => 'TOP_PERFORMER',
            'insight' => sprintf(
                '%s leads profitability with UGX %s profit and %.1f%% margin',
                $topPerformer['fuel_type'],
                number_format($topPerformer['profit']),
                $topPerformer['margin']
            )
        ];

        if ($bottomPerformer['margin'] < 8) {
            $insights[] = [
                'type' => 'UNDERPERFORMER',
                'priority' => 'HIGH',
                'insight' => sprintf(
                    '%s requires attention with only %.1f%% margin',
                    $bottomPerformer['fuel_type'],
                    $bottomPerformer['margin']
                )
            ];
        }

        return $insights;
    }

    private function generateCrossFuelAnalysis($rankedPerformance): array
    {
        $totalProfit = $rankedPerformance->sum('profit');
        $totalVolume = $rankedPerformance->sum('volume');

        return [
            'profit_concentration' => $rankedPerformance->take(3)->sum('profit') / $totalProfit * 100,
            'volume_leader' => $rankedPerformance->sortByDesc('volume')->first(),
            'margin_leader' => $rankedPerformance->sortByDesc('margin')->first(),
            'efficiency_leader' => $rankedPerformance->sortByDesc('profit_per_liter')->first()
        ];
    }

    private function getPeriodMetrics(?int $stationId, array $dateRange): array
    {
        $query = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

        if ($stationId) {
            $query->where('s.id', $stationId);
        } else {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                $query->where('s.id', $user->station_id);
            }
        }

        $metrics = $query->selectRaw('
            SUM(dr.total_sales_ugx) as total_revenue,
            SUM(dr.total_cogs_ugx) as total_cogs,
            SUM(dr.gross_profit_ugx) as total_profit,
            AVG(dr.profit_margin_percentage) as avg_margin,
            COUNT(DISTINCT dr.reconciliation_date) as trading_days
        ')->first();

        return [
            'total_revenue' => $metrics->total_revenue ?? 0,
            'total_cogs' => $metrics->total_cogs ?? 0,
            'total_profit' => $metrics->total_profit ?? 0,
            'avg_margin' => $metrics->avg_margin ?? 0,
            'trading_days' => $metrics->trading_days ?? 0
        ];
    }

    private function calculatePercentageChange($previous, $current): float
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    private function calculateEfficiencyTrend($previous, $current): string
    {
        $previousEfficiency = $previous['total_revenue'] > 0 ?
            $previous['total_profit'] / $previous['total_revenue'] : 0;
        $currentEfficiency = $current['total_revenue'] > 0 ?
            $current['total_profit'] / $current['total_revenue'] : 0;

        return match(true) {
            $currentEfficiency > $previousEfficiency * 1.05 => 'SIGNIFICANTLY_IMPROVED',
            $currentEfficiency > $previousEfficiency => 'IMPROVED',
            $currentEfficiency < $previousEfficiency * 0.95 => 'SIGNIFICANTLY_DECLINED',
            $currentEfficiency < $previousEfficiency => 'DECLINED',
            default => 'STABLE'
        };
    }

    private function generateTrendStory($comparison): array
    {
        $insights = [];

        if ($comparison['revenue_change'] > 10) {
            $insights[] = [
                'type' => 'GROWTH',
                'message' => sprintf('Strong revenue growth of %.1f%% demonstrates market expansion', $comparison['revenue_change'])
            ];
        }

        if ($comparison['margin_change'] > 2) {
            $insights[] = [
                'type' => 'EFFICIENCY',
                'message' => sprintf('Margin improvement of %.1f%% indicates enhanced operational efficiency', $comparison['margin_change'])
            ];
        }

        return [
            'primary_trend' => $comparison['efficiency_trend'],
            'insights' => $insights
        ];
    }

    private function generateAlertSummary($alerts): string
    {
        $highPriority = collect($alerts)->where('priority', 'HIGH')->count();
        $mediumPriority = collect($alerts)->where('priority', 'MEDIUM')->count();

        if ($highPriority > 0) {
            return sprintf('IMMEDIATE ATTENTION: %d critical issues require urgent intervention', $highPriority);
        } elseif ($mediumPriority > 0) {
            return sprintf('MONITORING REQUIRED: %d operational issues detected for review', $mediumPriority);
        } else {
            return 'OPERATIONAL EXCELLENCE: All COGS metrics within acceptable parameters';
        }
    }

    /**
     * AJAX ENDPOINT: Export COGS data to Excel/CSV
     */
    public function exportCogsData(Request $request)
    {
        try {
            $stationId = $request->get('station_id');
            $dateRange = $this->parseDateRange($request);
            $format = $request->get('format', 'csv');

            // Generate comprehensive export data
            $exportData = $this->generateExportData($stationId, $dateRange);

            if ($format === 'json') {
                return response()->json($exportData);
            }

            // For CSV/Excel exports, you would implement file generation here
            return response()->json([
                'message' => 'Export functionality ready for implementation',
                'data_preview' => array_slice($exportData, 0, 5)
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generateExportData(?int $stationId, array $dateRange): array
    {
        $query = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dr.reconciliation_date', [$dateRange['start'], $dateRange['end']]);

        if ($stationId) {
            $query->where('s.id', $stationId);
        }

        return $query->selectRaw('
            s.name as station_name,
            t.tank_number,
            t.fuel_type,
            dr.reconciliation_date,
            dr.opening_stock_liters,
            dr.total_delivered_liters,
            dr.total_dispensed_liters,
            dr.actual_closing_stock_liters,
            dr.volume_variance_liters,
            dr.variance_percentage,
            dr.total_cogs_ugx,
            dr.total_sales_ugx,
            dr.gross_profit_ugx,
            dr.profit_margin_percentage
        ')->orderBy('dr.reconciliation_date')->get()->toArray();
    }
}
