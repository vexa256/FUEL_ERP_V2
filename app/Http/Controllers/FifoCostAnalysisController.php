<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FuelERP_CriticalPrecisionService;
use Carbon\Carbon;
use Exception;

/**
 * FIFO Cost Analysis Controller - SINGLE CONSOLIDATED VIEW
 *
 * REFACTORED: One unified view with tabbed interface
 * ENHANCED: Complete filtering by station, date, month, year, fuel type
 * MAINTAINED: 100% schema accuracy + lean approach
 */
class FifoCostAnalysisController extends Controller
{
    private FuelERP_CriticalPrecisionService $fuelService;

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
     * SINGLE UNIFIED FIFO COST ANALYSIS VIEW
     * Contains all analysis data in tabbed interface with comprehensive filtering
     * FULLY SCHEMA COMPLIANT - uses exact field names from FUEL_ERP_V2 database
     */
    public function index(Request $request)
    {
        try {
            $stationScope = $this->enforceStationAccess();
            $filters = $this->validateAndApplyFilters($request, $stationScope);

            // Generate all analysis data for single view
            $analysisData = [
                // Executive Summary Dashboard
                'executive_summary' => $this->generateExecutiveSummary($filters),

                // Detailed Analysis Tabs
                'inventory_aging' => $this->getInventoryAgingData($filters),
                'margin_analysis' => $this->getMarginAnalysisData($filters),
                'fifo_consumption' => $this->getFifoConsumptionData($filters),
                'variance_analysis' => $this->getVarianceAnalysisData($filters),
                'capital_efficiency' => $this->getCapitalEfficiencyData($filters),

                // Filter & UI Support
                'filter_options' => $this->getFilterOptions($stationScope),
                'applied_filters' => $filters['metadata'],
                'pagination_info' => $this->getPaginationInfo($request)
            ];

            if ($request->ajax()) {
                $tabRequest = $request->input('tab');
                if ($tabRequest && isset($analysisData[$tabRequest])) {
                    return response()->json([
                        'success' => true,
                        'data' => $analysisData[$tabRequest],
                        'applied_filters' => $filters['metadata']
                    ]);
                }
                return response()->json(['success' => true, 'data' => $analysisData]);
            }

            return view('reports.fifo-cost-analysis', compact('analysisData'));

        } catch (Exception $e) {
            Log::error('FIFO Analysis Failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            // Raw exception exposure as per service requirements
            throw $e;
        }
    }

    /**
     * EXPORT FUNCTIONALITY - CSV/Excel export for any tab
     */
    public function exportData(Request $request)
    {
        try {
            $tabType = $request->input('tab', 'executive_summary');
            $format = $request->input('format', 'csv');

            if (!in_array($format, ['csv', 'excel'])) {
                throw new Exception("Invalid export format");
            }

            $stationScope = $this->enforceStationAccess();
            $filters = $this->validateAndApplyFilters($request, $stationScope);

            // Get export data based on tab
            $exportData = match($tabType) {
                'inventory_aging' => $this->getInventoryAgingData($filters, true),
                'margin_analysis' => $this->getMarginAnalysisData($filters, true),
                'fifo_consumption' => $this->getFifoConsumptionData($filters, true),
                'variance_analysis' => $this->getVarianceAnalysisData($filters, true),
                'capital_efficiency' => $this->getCapitalEfficiencyData($filters, true),
                default => $this->generateExecutiveSummary($filters)
            };

            $filename = "fifo_{$tabType}_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

            if ($format === 'csv') {
                return $this->exportToCsv($exportData, $filename);
            } else {
                return $this->exportToExcel($exportData, $filename);
            }

        } catch (Exception $e) {
            Log::error('Export Failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * EXECUTIVE SUMMARY - Financial KPIs and high-level metrics
     */
    private function generateExecutiveSummary(array $filters): array
    {
        // Financial Performance Summary
        $financial = DB::table('daily_reconciliations')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                COUNT(*) as total_reconciliations,
                SUM(total_sales_ugx) as total_revenue,
                SUM(total_cogs_ugx) as total_cogs,
                SUM(gross_profit_ugx) as total_profit,
                AVG(profit_margin_percentage) as avg_margin,
                MIN(profit_margin_percentage) as min_margin,
                MAX(profit_margin_percentage) as max_margin,
                STDDEV(profit_margin_percentage) as margin_volatility,
                AVG(abs_variance_percentage) as avg_variance,
                COUNT(CASE WHEN abs_variance_percentage >= 10.0 THEN 1 END) as critical_variances,
                COUNT(CASE WHEN abs_variance_percentage >= 5.0 THEN 1 END) as high_variances,
                SUM(total_dispensed_liters) as total_volume_sold
            ')
            ->first();

        // Current Inventory Status
        $inventory = DB::table('fifo_layers')
            ->join('tanks', 'fifo_layers.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->where('fifo_layers.is_exhausted', false)
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->selectRaw('
                COUNT(*) as active_layers,
                SUM(remaining_volume_liters) as total_volume,
                SUM(remaining_value_ugx) as total_value,
                AVG(cost_per_liter_ugx) as avg_cost_per_liter,
                COUNT(CASE WHEN DATEDIFF(CURDATE(), delivery_date) > 90 THEN 1 END) as aging_layers,
                SUM(CASE WHEN DATEDIFF(CURDATE(), delivery_date) > 90
                    THEN remaining_value_ugx ELSE 0 END) as aging_value,
                COUNT(CASE WHEN DATEDIFF(CURDATE(), delivery_date) > 180 THEN 1 END) as critical_aging_layers,
                SUM(CASE WHEN DATEDIFF(CURDATE(), delivery_date) > 180
                    THEN remaining_value_ugx ELSE 0 END) as critical_aging_value
            ')
            ->first();

        // Station Performance Breakdown
        $stationPerformance = DB::table('daily_reconciliations')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                stations.id as station_id,
                stations.name as station_name,
                SUM(total_sales_ugx) as station_revenue,
                SUM(gross_profit_ugx) as station_profit,
                AVG(profit_margin_percentage) as station_margin,
                AVG(abs_variance_percentage) as station_variance,
                COUNT(CASE WHEN abs_variance_percentage >= 5.0 THEN 1 END) as station_high_variances
            ')
            ->groupBy('stations.id', 'stations.name')
            ->orderBy('station_revenue', 'DESC')
            ->limit(10)
            ->get();

        return [
            'financial_summary' => [
                'total_revenue' => (float) ($financial->total_revenue ?? 0),
                'total_cogs' => (float) ($financial->total_cogs ?? 0),
                'gross_profit' => (float) ($financial->total_profit ?? 0),
                'profit_margin' => round((float) ($financial->avg_margin ?? 0), 2),
                'min_margin' => round((float) ($financial->min_margin ?? 0), 2),
                'max_margin' => round((float) ($financial->max_margin ?? 0), 2),
                'margin_volatility' => round((float) ($financial->margin_volatility ?? 0), 2),
                'total_reconciliations' => (int) ($financial->total_reconciliations ?? 0),
                'total_volume_sold' => (float) ($financial->total_volume_sold ?? 0)
            ],
            'inventory_summary' => [
                'active_layers' => (int) ($inventory->active_layers ?? 0),
                'total_volume' => (float) ($inventory->total_volume ?? 0),
                'total_value' => (float) ($inventory->total_value ?? 0),
                'avg_cost_per_liter' => round((float) ($inventory->avg_cost_per_liter ?? 0), 4),
                'aging_layers' => (int) ($inventory->aging_layers ?? 0),
                'aging_value' => (float) ($inventory->aging_value ?? 0),
                'aging_percentage' => $inventory->total_value > 0 ?
                    round(($inventory->aging_value / $inventory->total_value) * 100, 1) : 0,
                'critical_aging_layers' => (int) ($inventory->critical_aging_layers ?? 0),
                'critical_aging_value' => (float) ($inventory->critical_aging_value ?? 0)
            ],
            'variance_summary' => [
                'avg_variance' => round((float) ($financial->avg_variance ?? 0), 2),
                'critical_variances' => (int) ($financial->critical_variances ?? 0),
                'high_variances' => (int) ($financial->high_variances ?? 0),
                'variance_rate' => $financial->total_reconciliations > 0 ?
                    round(($financial->high_variances / $financial->total_reconciliations) * 100, 1) : 0
            ],
            'station_performance' => $stationPerformance->map(fn($station) => [
                'station_id' => $station->station_id,
                'station_name' => $station->station_name,
                'revenue' => (float) $station->station_revenue,
                'profit' => (float) $station->station_profit,
                'margin' => round((float) $station->station_margin, 2),
                'variance' => round((float) $station->station_variance, 2),
                'high_variances' => (int) $station->station_high_variances
            ])->toArray()
        ];
    }

    /**
     * INVENTORY AGING DATA - Detailed aging analysis with drill-down
     */
    private function getInventoryAgingData(array $filters, bool $forExport = false): array
    {
        $perPage = $forExport ? 10000 : 50;
        $page = request()->input('aging_page', 1);

        $agingQuery = DB::table('fifo_layers')
            ->join('tanks', 'fifo_layers.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->leftJoin('deliveries', 'fifo_layers.delivery_id', '=', 'deliveries.id')
            ->where('fifo_layers.is_exhausted', false)
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('fifo_layers.delivery_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->selectRaw('
                fifo_layers.id as layer_id,
                stations.name as station_name,
                tanks.tank_number,
                tanks.fuel_type,
                fifo_layers.layer_sequence,
                fifo_layers.delivery_date,
                DATEDIFF(CURDATE(), fifo_layers.delivery_date) as age_days,
                CASE
                    WHEN DATEDIFF(CURDATE(), fifo_layers.delivery_date) <= 30 THEN "Fresh (0-30 days)"
                    WHEN DATEDIFF(CURDATE(), fifo_layers.delivery_date) <= 60 THEN "Aging (31-60 days)"
                    WHEN DATEDIFF(CURDATE(), fifo_layers.delivery_date) <= 90 THEN "Stale (61-90 days)"
                    WHEN DATEDIFF(CURDATE(), fifo_layers.delivery_date) <= 180 THEN "Critical (91-180 days)"
                    ELSE "Emergency (180+ days)"
                END as age_category,
                fifo_layers.original_volume_liters,
                fifo_layers.remaining_volume_liters,
                fifo_layers.cost_per_liter_ugx,
                fifo_layers.remaining_value_ugx,
                fifo_layers.consumed_value_ugx,
                fifo_layers.layer_status,
                deliveries.supplier_name,
                deliveries.delivery_reference,
                deliveries.invoice_number
            ')
            ->orderBy('age_days', 'DESC')
            ->orderBy('fifo_layers.remaining_value_ugx', 'DESC');

        if ($forExport) {
            return $agingQuery->get()->toArray();
        }

        $agingData = $agingQuery->paginate($perPage, ['*'], 'aging_page', $page);

        // Aging Distribution Summary
        $distribution = DB::table('fifo_layers')
            ->join('tanks', 'fifo_layers.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->where('fifo_layers.is_exhausted', false)
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->selectRaw('
                CASE
                    WHEN DATEDIFF(CURDATE(), delivery_date) <= 30 THEN "fresh"
                    WHEN DATEDIFF(CURDATE(), delivery_date) <= 60 THEN "aging"
                    WHEN DATEDIFF(CURDATE(), delivery_date) <= 90 THEN "stale"
                    WHEN DATEDIFF(CURDATE(), delivery_date) <= 180 THEN "critical"
                    ELSE "emergency"
                END as age_bucket,
                COUNT(*) as layer_count,
                SUM(remaining_volume_liters) as total_volume,
                SUM(remaining_value_ugx) as total_value
            ')
            ->groupBy('age_bucket')
            ->get();

        $distributionSummary = [];
        $totalValue = 0;
        foreach ($distribution as $bucket) {
            $distributionSummary[$bucket->age_bucket] = [
                'count' => (int) $bucket->layer_count,
                'volume' => (float) $bucket->total_volume,
                'value' => (float) $bucket->total_value
            ];
            $totalValue += $bucket->total_value;
        }

        return [
            'detailed_data' => $agingData->items(),
            'pagination' => [
                'current_page' => $agingData->currentPage(),
                'per_page' => $agingData->perPage(),
                'total' => $agingData->total(),
                'last_page' => $agingData->lastPage()
            ],
            'distribution_summary' => $distributionSummary,
            'total_inventory_value' => $totalValue
        ];
    }

    /**
     * MARGIN ANALYSIS DATA - Profitability and margin protection analysis
     */
    private function getMarginAnalysisData(array $filters, bool $forExport = false): array
    {
        $perPage = $forExport ? 10000 : 50;
        $page = request()->input('margin_page', 1);

        $marginQuery = DB::table('daily_reconciliations')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->join('users', 'daily_reconciliations.reconciled_by_user_id', '=', 'users.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                daily_reconciliations.id as reconciliation_id,
                daily_reconciliations.reconciliation_date,
                stations.name as station_name,
                tanks.tank_number,
                tanks.fuel_type,
                daily_reconciliations.total_dispensed_liters,
                daily_reconciliations.total_sales_ugx,
                daily_reconciliations.total_cogs_ugx,
                daily_reconciliations.gross_profit_ugx,
                daily_reconciliations.profit_margin_percentage,
                daily_reconciliations.volume_variance_liters,
                daily_reconciliations.variance_percentage,
                daily_reconciliations.abs_variance_percentage,
                daily_reconciliations.valuation_method,
                daily_reconciliations.valuation_quality,
                users.name as reconciled_by,
                CASE
                    WHEN daily_reconciliations.profit_margin_percentage >= 20 THEN "Excellent"
                    WHEN daily_reconciliations.profit_margin_percentage >= 15 THEN "Good"
                    WHEN daily_reconciliations.profit_margin_percentage >= 10 THEN "Average"
                    WHEN daily_reconciliations.profit_margin_percentage >= 5 THEN "Poor"
                    ELSE "Critical"
                END as margin_category
            ')
            ->orderBy('daily_reconciliations.reconciliation_date', 'DESC')
            ->orderBy('daily_reconciliations.profit_margin_percentage', 'ASC');

        if ($forExport) {
            return $marginQuery->get()->toArray();
        }

        $marginData = $marginQuery->paginate($perPage, ['*'], 'margin_page', $page);

        // Margin Distribution by Fuel Type
        $fuelMargins = DB::table('daily_reconciliations')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                tanks.fuel_type,
                COUNT(*) as transaction_count,
                SUM(total_sales_ugx) as total_revenue,
                SUM(gross_profit_ugx) as total_profit,
                AVG(profit_margin_percentage) as avg_margin,
                MIN(profit_margin_percentage) as min_margin,
                MAX(profit_margin_percentage) as max_margin,
                STDDEV(profit_margin_percentage) as margin_volatility,
                COUNT(CASE WHEN profit_margin_percentage < 10 THEN 1 END) as low_margin_count
            ')
            ->groupBy('tanks.fuel_type')
            ->orderBy('total_revenue', 'DESC')
            ->get();

        return [
            'detailed_data' => $marginData->items(),
            'pagination' => [
                'current_page' => $marginData->currentPage(),
                'per_page' => $marginData->perPage(),
                'total' => $marginData->total(),
                'last_page' => $marginData->lastPage()
            ],
            'fuel_margins' => $fuelMargins->map(fn($fuel) => [
                'fuel_type' => $fuel->fuel_type,
                'transaction_count' => (int) $fuel->transaction_count,
                'total_revenue' => (float) $fuel->total_revenue,
                'total_profit' => (float) $fuel->total_profit,
                'avg_margin' => round((float) $fuel->avg_margin, 2),
                'min_margin' => round((float) $fuel->min_margin, 2),
                'max_margin' => round((float) $fuel->max_margin, 2),
                'volatility' => round((float) ($fuel->margin_volatility ?? 0), 2),
                'low_margin_percentage' => $fuel->transaction_count > 0 ?
                    round(($fuel->low_margin_count / $fuel->transaction_count) * 100, 1) : 0
            ])->toArray()
        ];
    }

    /**
     * FIFO CONSUMPTION DATA - Layer consumption tracking and efficiency
     */
    private function getFifoConsumptionData(array $filters, bool $forExport = false): array
    {
        $perPage = $forExport ? 10000 : 50;
        $page = request()->input('fifo_page', 1);

        $fifoQuery = DB::table('fifo_consumption_log')
            ->join('daily_reconciliations', 'fifo_consumption_log.reconciliation_id', '=', 'daily_reconciliations.id')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->join('fifo_layers', 'fifo_consumption_log.fifo_layer_id', '=', 'fifo_layers.id')
            ->leftJoin('deliveries', 'fifo_layers.delivery_id', '=', 'deliveries.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                fifo_consumption_log.id as consumption_id,
                daily_reconciliations.reconciliation_date,
                stations.name as station_name,
                tanks.tank_number,
                tanks.fuel_type,
                fifo_layers.layer_sequence,
                fifo_layers.delivery_date,
                DATEDIFF(daily_reconciliations.reconciliation_date, fifo_layers.delivery_date) as inventory_age_days,
                fifo_consumption_log.volume_consumed_liters,
                fifo_consumption_log.cost_per_liter_ugx,
                fifo_consumption_log.total_cost_ugx,
                fifo_consumption_log.consumption_sequence,
                fifo_consumption_log.valuation_impact_ugx,
                fifo_layers.original_volume_liters,
                fifo_layers.remaining_volume_liters,
                fifo_layers.is_exhausted,
                deliveries.supplier_name,
                deliveries.delivery_reference
            ')
            ->orderBy('daily_reconciliations.reconciliation_date', 'DESC')
            ->orderBy('fifo_consumption_log.consumption_sequence', 'ASC');

        if ($forExport) {
            return $fifoQuery->get()->toArray();
        }

        $fifoData = $fifoQuery->paginate($perPage, ['*'], 'fifo_page', $page);

        // FIFO Efficiency Metrics
        $efficiency = DB::table('fifo_consumption_log')
            ->join('daily_reconciliations', 'fifo_consumption_log.reconciliation_id', '=', 'daily_reconciliations.id')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->join('fifo_layers', 'fifo_consumption_log.fifo_layer_id', '=', 'fifo_layers.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                tanks.fuel_type,
                COUNT(*) as total_consumptions,
                COUNT(DISTINCT fifo_consumption_log.fifo_layer_id) as unique_layers,
                SUM(fifo_consumption_log.volume_consumed_liters) as total_volume,
                SUM(fifo_consumption_log.total_cost_ugx) as total_cost,
                AVG(fifo_consumption_log.cost_per_liter_ugx) as avg_cost_per_liter,
                AVG(DATEDIFF(daily_reconciliations.reconciliation_date, fifo_layers.delivery_date)) as avg_age_days,
                SUM(fifo_consumption_log.valuation_impact_ugx) as total_valuation_impact
            ')
            ->groupBy('tanks.fuel_type')
            ->orderBy('total_cost', 'DESC')
            ->get();

        return [
            'detailed_data' => $fifoData->items(),
            'pagination' => [
                'current_page' => $fifoData->currentPage(),
                'per_page' => $fifoData->perPage(),
                'total' => $fifoData->total(),
                'last_page' => $fifoData->lastPage()
            ],
            'efficiency_metrics' => $efficiency->map(fn($metric) => [
                'fuel_type' => $metric->fuel_type,
                'total_consumptions' => (int) $metric->total_consumptions,
                'unique_layers' => (int) $metric->unique_layers,
                'total_volume' => (float) $metric->total_volume,
                'total_cost' => (float) $metric->total_cost,
                'avg_cost_per_liter' => round((float) $metric->avg_cost_per_liter, 4),
                'avg_age_days' => round((float) $metric->avg_age_days, 1),
                'total_valuation_impact' => (float) $metric->total_valuation_impact
            ])->toArray()
        ];
    }

    /**
     * VARIANCE ANALYSIS DATA - Detailed variance tracking and patterns
     */
    private function getVarianceAnalysisData(array $filters, bool $forExport = false): array
    {
        $perPage = $forExport ? 10000 : 50;
        $page = request()->input('variance_page', 1);

        $varianceQuery = DB::table('daily_reconciliations')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->join('users', 'daily_reconciliations.reconciled_by_user_id', '=', 'users.id')
            ->where('daily_reconciliations.abs_variance_percentage', '>=', 1.0) // Show significant variances
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                daily_reconciliations.id as reconciliation_id,
                daily_reconciliations.reconciliation_date,
                stations.name as station_name,
                tanks.tank_number,
                tanks.fuel_type,
                daily_reconciliations.opening_stock_liters,
                daily_reconciliations.total_delivered_liters,
                daily_reconciliations.total_dispensed_liters,
                daily_reconciliations.theoretical_closing_stock_liters,
                daily_reconciliations.actual_closing_stock_liters,
                daily_reconciliations.volume_variance_liters,
                daily_reconciliations.variance_percentage,
                daily_reconciliations.abs_variance_percentage,
                CASE
                    WHEN daily_reconciliations.abs_variance_percentage >= 10.0 THEN "Critical"
                    WHEN daily_reconciliations.abs_variance_percentage >= 5.0 THEN "High"
                    WHEN daily_reconciliations.abs_variance_percentage >= 2.0 THEN "Medium"
                    ELSE "Low"
                END as variance_severity,
                daily_reconciliations.gross_profit_ugx,
                daily_reconciliations.profit_margin_percentage,
                users.name as reconciled_by,
                daily_reconciliations.reconciled_at
            ')
            ->orderBy('daily_reconciliations.abs_variance_percentage', 'DESC')
            ->orderBy('daily_reconciliations.reconciliation_date', 'DESC');

        if ($forExport) {
            return $varianceQuery->get()->toArray();
        }

        $varianceData = $varianceQuery->paginate($perPage, ['*'], 'variance_page', $page);

        // Variance Pattern Analysis
        $patterns = DB::table('daily_reconciliations')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                stations.name as station_name,
                tanks.fuel_type,
                COUNT(*) as total_reconciliations,
                AVG(abs_variance_percentage) as avg_variance,
                MAX(abs_variance_percentage) as max_variance,
                COUNT(CASE WHEN abs_variance_percentage >= 10.0 THEN 1 END) as critical_count,
                COUNT(CASE WHEN abs_variance_percentage >= 5.0 THEN 1 END) as high_count,
                COUNT(CASE WHEN abs_variance_percentage >= 2.0 THEN 1 END) as medium_count,
                SUM(ABS(volume_variance_liters)) as total_volume_variance,
                COUNT(CASE WHEN volume_variance_liters > 0 THEN 1 END) as positive_variances,
                COUNT(CASE WHEN volume_variance_liters < 0 THEN 1 END) as negative_variances
            ')
            ->groupBy('stations.name', 'tanks.fuel_type')
            ->orderBy('avg_variance', 'DESC')
            ->get();

        return [
            'detailed_data' => $varianceData->items(),
            'pagination' => [
                'current_page' => $varianceData->currentPage(),
                'per_page' => $varianceData->perPage(),
                'total' => $varianceData->total(),
                'last_page' => $varianceData->lastPage()
            ],
            'variance_patterns' => $patterns->map(fn($pattern) => [
                'station_name' => $pattern->station_name,
                'fuel_type' => $pattern->fuel_type,
                'total_reconciliations' => (int) $pattern->total_reconciliations,
                'avg_variance' => round((float) $pattern->avg_variance, 2),
                'max_variance' => round((float) $pattern->max_variance, 2),
                'critical_percentage' => $pattern->total_reconciliations > 0 ?
                    round(($pattern->critical_count / $pattern->total_reconciliations) * 100, 1) : 0,
                'high_percentage' => $pattern->total_reconciliations > 0 ?
                    round(($pattern->high_count / $pattern->total_reconciliations) * 100, 1) : 0,
                'positive_variance_rate' => $pattern->total_reconciliations > 0 ?
                    round(($pattern->positive_variances / $pattern->total_reconciliations) * 100, 1) : 0,
                'total_volume_variance' => (float) $pattern->total_volume_variance
            ])->toArray()
        ];
    }

    /**
     * CAPITAL EFFICIENCY DATA - Working capital and inventory turnover analysis
     */
    private function getCapitalEfficiencyData(array $filters, bool $forExport = false): array
    {
        // Capital Efficiency by Fuel Type
        $efficiency = DB::table('fifo_consumption_log')
            ->join('daily_reconciliations', 'fifo_consumption_log.reconciliation_id', '=', 'daily_reconciliations.id')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->join('fifo_layers', 'fifo_consumption_log.fifo_layer_id', '=', 'fifo_layers.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                tanks.fuel_type,
                COUNT(*) as consumption_events,
                SUM(fifo_consumption_log.total_cost_ugx) as total_cogs,
                SUM(fifo_consumption_log.volume_consumed_liters) as total_volume,
                AVG(DATEDIFF(daily_reconciliations.reconciliation_date, fifo_layers.delivery_date)) as avg_inventory_days,
                AVG(fifo_consumption_log.cost_per_liter_ugx) as avg_cost_per_liter,
                SUM(fifo_consumption_log.total_cost_ugx) / NULLIF(AVG(DATEDIFF(daily_reconciliations.reconciliation_date, fifo_layers.delivery_date)), 0) as capital_velocity,
                (365 / NULLIF(AVG(DATEDIFF(daily_reconciliations.reconciliation_date, fifo_layers.delivery_date)), 0)) as inventory_turnover_ratio
            ')
            ->groupBy('tanks.fuel_type')
            ->orderBy('capital_velocity', 'DESC')
            ->get();

        // Current Inventory Status by Station
        $currentInventory = DB::table('fifo_layers')
            ->join('tanks', 'fifo_layers.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->where('fifo_layers.is_exhausted', false)
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->selectRaw('
                stations.name as station_name,
                tanks.fuel_type,
                COUNT(*) as active_layers,
                SUM(remaining_volume_liters) as total_volume,
                SUM(remaining_value_ugx) as total_value,
                AVG(cost_per_liter_ugx) as avg_cost_per_liter,
                AVG(DATEDIFF(CURDATE(), delivery_date)) as avg_age_days,
                SUM(remaining_value_ugx) / NULLIF(AVG(DATEDIFF(CURDATE(), delivery_date)), 0) as current_capital_velocity
            ')
            ->groupBy('stations.name', 'tanks.fuel_type')
            ->orderBy('total_value', 'DESC')
            ->get();

        // Working Capital Summary
        $workingCapital = DB::table('daily_reconciliations')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('daily_reconciliations.reconciliation_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                AVG(opening_stock_value_ugx) as avg_opening_value,
                AVG(closing_stock_value_ugx) as avg_closing_value,
                AVG(inventory_value_change_ugx) as avg_value_change,
                SUM(total_cogs_ugx) as total_cogs,
                AVG(total_cogs_ugx / NULLIF(opening_stock_value_ugx, 0)) as inventory_turnover_rate
            ')
            ->first();

        if ($forExport) {
            return [
                'efficiency_analysis' => $efficiency->toArray(),
                'current_inventory' => $currentInventory->toArray(),
                'working_capital_summary' => (array) $workingCapital
            ];
        }

        return [
            'efficiency_analysis' => $efficiency->map(fn($eff) => [
                'fuel_type' => $eff->fuel_type,
                'consumption_events' => (int) $eff->consumption_events,
                'total_cogs' => (float) $eff->total_cogs,
                'total_volume' => (float) $eff->total_volume,
                'avg_inventory_days' => round((float) $eff->avg_inventory_days, 1),
                'avg_cost_per_liter' => round((float) $eff->avg_cost_per_liter, 4),
                'capital_velocity' => round((float) ($eff->capital_velocity ?? 0), 2),
                'inventory_turnover_ratio' => round((float) ($eff->inventory_turnover_ratio ?? 0), 2)
            ])->toArray(),
            'current_inventory' => $currentInventory->map(fn($inv) => [
                'station_name' => $inv->station_name,
                'fuel_type' => $inv->fuel_type,
                'active_layers' => (int) $inv->active_layers,
                'total_volume' => (float) $inv->total_volume,
                'total_value' => (float) $inv->total_value,
                'avg_cost_per_liter' => round((float) $inv->avg_cost_per_liter, 4),
                'avg_age_days' => round((float) $inv->avg_age_days, 1),
                'capital_velocity' => round((float) ($inv->current_capital_velocity ?? 0), 2)
            ])->toArray(),
            'working_capital_summary' => [
                'avg_opening_value' => (float) ($workingCapital->avg_opening_value ?? 0),
                'avg_closing_value' => (float) ($workingCapital->avg_closing_value ?? 0),
                'avg_value_change' => (float) ($workingCapital->avg_value_change ?? 0),
                'total_cogs' => (float) ($workingCapital->total_cogs ?? 0),
                'inventory_turnover_rate' => round((float) ($workingCapital->inventory_turnover_rate ?? 0), 2)
            ]
        ];
    }

    /**
     * COMPLETE FILTER VALIDATION AND APPLICATION
     */
    private function validateAndApplyFilters(Request $request, array $stationScope): array
    {
        $filters = [
            'station_ids' => [],
            'fuel_types' => [],
            'date_range' => null,
            'month_filter' => null,
            'year_filter' => null,
            'metadata' => []
        ];

        // Station filtering with access control
        $requestedStations = $request->input('station_ids', []);
        if (!empty($requestedStations)) {
            $availableStationIds = array_column($stationScope, 'id');
            $validStationIds = array_intersect($requestedStations, $availableStationIds);
            $filters['station_ids'] = $validStationIds;
        } else {
            $filters['station_ids'] = array_column($stationScope, 'id');
        }

        // Fuel type filtering with validation
        $requestedFuelTypes = $request->input('fuel_types', []);
        if (!empty($requestedFuelTypes)) {
            foreach ($requestedFuelTypes as $fuelType) {
                if (!in_array($fuelType, self::VALID_FUEL_TYPES)) {
                    throw new Exception("Invalid fuel type: $fuelType");
                }
            }
            $filters['fuel_types'] = $requestedFuelTypes;
        }

        // Date range filtering
        if ($request->has('start_date') || $request->has('end_date')) {
            $filters['date_range'] = $this->validateDateRange($request);
        }

        // Month filtering
        if ($request->has('month')) {
            $month = (int) $request->input('month');
            if ($month < 1 || $month > 12) {
                throw new Exception("Invalid month: must be 1-12");
            }
            $filters['month_filter'] = $month;
        }

        // Year filtering
        if ($request->has('year')) {
            $year = (int) $request->input('year');
            if ($year < 2020 || $year > (int) date('Y') + 1) {
                throw new Exception("Invalid year: must be between 2020 and " . (date('Y') + 1));
            }
            $filters['year_filter'] = $year;
        }

        // Build metadata for UI
        $filters['metadata'] = [
            'stations_selected' => count($filters['station_ids']),
            'fuel_types_selected' => count($filters['fuel_types']),
            'date_range_applied' => !empty($filters['date_range']),
            'month_filter_applied' => !empty($filters['month_filter']),
            'year_filter_applied' => !empty($filters['year_filter']),
            'date_range_start' => $filters['date_range']['start'] ?? null,
            'date_range_end' => $filters['date_range']['end'] ?? null,
            'month_selected' => $filters['month_filter'],
            'year_selected' => $filters['year_filter'],
            'filter_summary' => $this->buildFilterSummary($filters, $stationScope)
        ];

        return $filters;
    }

    /**
     * GET FILTER OPTIONS for UI dropdowns
     */
    private function getFilterOptions(array $stationScope): array
    {
        $stationIds = array_column($stationScope, 'id');

        // Available fuel types based on station scope
        $availableFuelTypes = DB::table('tanks')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->whereIn('stations.id', $stationIds)
            ->distinct()
            ->pluck('fuel_type')
            ->toArray();

        // Available years with data
        $availableYears = DB::table('daily_reconciliations')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->whereIn('stations.id', $stationIds)
            ->selectRaw('DISTINCT YEAR(reconciliation_date) as year')
            ->orderBy('year', 'DESC')
            ->pluck('year')
            ->toArray();

        return [
            'stations' => $stationScope,
            'fuel_types' => array_values(array_intersect(self::VALID_FUEL_TYPES, $availableFuelTypes)),
            'years' => $availableYears,
            'months' => [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ]
        ];
    }

    /**
     * Station Access Control - MANDATORY
     */
    private function enforceStationAccess(): array
    {
        $user = auth()->user();
        if (!$user) throw new Exception("Authentication required");

        if ($user->role === 'admin') {
            return DB::table('stations')->select('id', 'name', 'location')->get()->toArray();
        }

        if (!$user->station_id) throw new Exception("No assigned station");

        $station = DB::table('stations')
            ->where('id', $user->station_id)
            ->select('id', 'name', 'location')
            ->first();

        if (!$station) throw new Exception("Assigned station not found");

        return [(array) $station];
    }

    /**
     * Date Range Validation
     */
    private function validateDateRange(Request $request): array
    {
        $start = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $end = $request->input('end_date', now()->format('Y-m-d'));

        try {
            $startDate = Carbon::createFromFormat('Y-m-d', $start);
            $endDate = Carbon::createFromFormat('Y-m-d', $end);
        } catch (Exception $e) {
            throw new Exception("Invalid date format: YYYY-MM-DD required");
        }

        if ($startDate->greaterThan($endDate)) {
            throw new Exception("Start date cannot be after end date");
        }

        if ($startDate->diffInDays($endDate) > 365) {
            throw new Exception("Date range cannot exceed 365 days");
        }

        return ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')];
    }

    /**
     * Build Filter Summary for UI
     */
    private function buildFilterSummary(array $filters, array $stationScope): string
    {
        $summary = [];

        if (!empty($filters['station_ids'])) {
    $stationNames = array_filter(array_map(function($scope) use ($filters) {
        return in_array($scope->id, $filters['station_ids']) ? $scope->name : null;
    }, $stationScope));

    if (count($stationNames) < count($stationScope)) {
        $summary[] = count($stationNames) . ' station(s)';
    }
}

        if (!empty($filters['fuel_types'])) {
            $summary[] = count($filters['fuel_types']) . ' fuel type(s)';
        }

        if (!empty($filters['date_range'])) {
            $summary[] = 'Date range: ' . $filters['date_range']['start'] . ' to ' . $filters['date_range']['end'];
        }

        if (!empty($filters['month_filter'])) {
            $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $summary[] = 'Month: ' . $months[$filters['month_filter']];
        }

        if (!empty($filters['year_filter'])) {
            $summary[] = 'Year: ' . $filters['year_filter'];
        }

        return empty($summary) ? 'All data' : implode(', ', $summary);
    }

    /**
     * Get Pagination Info
     */
    private function getPaginationInfo(Request $request): array
    {
        return [
            'aging_page' => (int) $request->input('aging_page', 1),
            'margin_page' => (int) $request->input('margin_page', 1),
            'fifo_page' => (int) $request->input('fifo_page', 1),
            'variance_page' => (int) $request->input('variance_page', 1),
            'per_page' => 50
        ];
    }

    /**
     * Export to CSV
     */
    private function exportToCsv(array $data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            if (!empty($data)) {
                // Flatten nested arrays for CSV export
                $flatData = $this->flattenArrayForExport($data);

                if (!empty($flatData)) {
                    // Write headers
                    fputcsv($file, array_keys($flatData[0]));

                    // Write data
                    foreach ($flatData as $row) {
                        fputcsv($file, array_values($row));
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to Excel (CSV with .xlsx extension for compatibility)
     */
    private function exportToExcel(array $data, string $filename)
    {
        return $this->exportToCsv($data, str_replace('.excel', '.csv', $filename));
    }

    /**
     * Flatten nested arrays for export
     */
    private function flattenArrayForExport(array $data): array
    {
        if (empty($data)) return [];

        // If it's already a flat array of objects/arrays
        if (isset($data[0]) && (is_array($data[0]) || is_object($data[0]))) {
            return array_map(fn($item) => (array) $item, $data);
        }

        // If it's a nested structure, extract the first exportable array
        foreach ($data as $key => $value) {
            if (is_array($value) && !empty($value) && (is_array($value[0]) || is_object($value[0]))) {
                return array_map(fn($item) => (array) $item, $value);
            }
        }

        // Fallback: convert the data structure itself
        return [array_map(fn($value) => is_array($value) ? json_encode($value) : $value, $data)];
    }
}
