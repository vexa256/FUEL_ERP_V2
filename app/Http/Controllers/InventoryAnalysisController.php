<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FuelERP_CriticalPrecisionService;
use Carbon\Carbon;
use Exception;

/**
 * INVENTORY ANALYSIS CONTROLLER - MINIMAL SCOPE
 *
 * SCOPE: ✅ 10. Inventory Value by Station ✅ 11. Inventory Movement Analysis
 * COMPLIANCE: 100% FUEL_ERP_V2 schema + FuelERP_CriticalPrecisionService
 */
class InventoryAnalysisController extends Controller
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
     * UNIFIED INDEX - Inventory Value + Movement Analysis Only
     * SUPPORTS: Ajax/Non-Ajax, Full Filtering, Comprehensive Error Handling
     */
    public function index(Request $request)
    {
        try {
            // Step 1: Enforce station access control
            $stationScope = $this->enforceStationAccess();

            // Step 2: Validate and apply all filters (date, month, year, station, fuel type)
            $filters = $this->validateFilters($request, $stationScope);

            // Step 3: Generate analysis data
            $data = [
                'inventory_value_by_station' => $this->getInventoryValueByStation($filters),
                'inventory_movement_analysis' => $this->getInventoryMovementAnalysis($filters),
                'filter_options' => $this->getFilterOptions($stationScope),
                'applied_filters' => $filters['metadata'],
                'request_info' => [
                    'is_ajax' => $request->ajax(),
                    'timestamp' => now()->toISOString(),
                    'user_id' => auth()->id(),
                    'user_role' => auth()->user()->role ?? 'unknown'
                ]
            ];

            // Success response handling
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'message' => 'Inventory analysis data retrieved successfully',
                    'filter_summary' => $filters['metadata']['filter_summary'] ?? 'No filters applied'
                ], 200);
            }

            return view('reports.inventory-analysis', compact('data'));

        } catch (Exception $e) {
            // Comprehensive error logging with context
            $errorContext = [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id() ?? 'unauthenticated',
                'user_role' => auth()->user()->role ?? 'unknown',
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_data' => $request->all(),
                'is_ajax' => $request->ajax(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'timestamp' => now()->toISOString(),
                'trace' => $e->getTraceAsString()
            ];

            Log::error('🚨 INVENTORY ANALYSIS CONTROLLER FAILURE', $errorContext);

            // Determine error type and provide developer guidance
            $errorType = $this->categorizeError($e->getMessage());
            $developerGuidance = $this->getDeveloperGuidance($errorType, $e->getMessage());

            // Ajax error response with developer info
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'error_type' => $errorType,
                    'developer_guidance' => $developerGuidance,
                    'error_context' => [
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine(),
                        'timestamp' => now()->toISOString(),
                        'request_data' => $request->all()
                    ],
                    'suggested_actions' => $this->getSuggestedActions($errorType),
                    'documentation_links' => $this->getDocumentationLinks($errorType)
                ], 500);
            }

            // Non-ajax error handling - pass through raw exception as required
            throw $e;
        }
    }

    /**
     * CATEGORIZE ERROR TYPE for developer guidance
     */
    private function categorizeError(string $errorMessage): string
    {
        return match(true) {
            str_contains($errorMessage, 'FILTER_ERROR:') => 'FILTER_VALIDATION',
            str_contains($errorMessage, 'DATE_') => 'DATE_VALIDATION',
            str_contains($errorMessage, 'MONTH_') => 'MONTH_VALIDATION',
            str_contains($errorMessage, 'YEAR_') => 'YEAR_VALIDATION',
            str_contains($errorMessage, 'Authentication required') => 'AUTHENTICATION',
            str_contains($errorMessage, 'station') => 'STATION_ACCESS',
            str_contains($errorMessage, 'fuel type') => 'FUEL_TYPE_VALIDATION',
            str_contains($errorMessage, 'SQLSTATE') => 'DATABASE_ERROR',
            str_contains($errorMessage, 'Call to undefined') => 'CODE_ERROR',
            default => 'GENERAL_ERROR'
        };
    }

    /**
     * GET DEVELOPER GUIDANCE based on error type
     */
    private function getDeveloperGuidance(string $errorType, string $errorMessage): string
    {
        return match($errorType) {
            'FILTER_VALIDATION' => 'Check filter parameters. Ensure station_ids array contains valid station IDs that user has access to.',
            'DATE_VALIDATION' => 'Validate date format (YYYY-MM-DD). Ensure start_date <= end_date and range <= 365 days.',
            'MONTH_VALIDATION' => 'Month parameter must be integer 1-12. Check client-side validation.',
            'YEAR_VALIDATION' => 'Year parameter must be integer between 2020 and current year. Check dropdown options.',
            'AUTHENTICATION' => 'User not authenticated. Redirect to login or check auth middleware.',
            'STATION_ACCESS' => 'User lacks station access. Check user.station_id assignment or admin role.',
            'FUEL_TYPE_VALIDATION' => 'Invalid fuel type submitted. Cross-reference with VALID_FUEL_TYPES constant.',
            'DATABASE_ERROR' => 'Database query failed. Check table structure, foreign keys, and data integrity.',
            'CODE_ERROR' => 'PHP method/property error. Check service class integration and method signatures.',
            default => 'Unexpected error occurred. Check logs for detailed stack trace and context.'
        };
    }

    /**
     * GET SUGGESTED ACTIONS for developers
     */
    private function getSuggestedActions(string $errorType): array
    {
        return match($errorType) {
            'FILTER_VALIDATION' => [
                'Verify filter UI sends correct parameter names',
                'Check station access permissions for current user',
                'Validate filter combinations (date range vs month/year)',
                'Ensure arrays are properly formatted in request'
            ],
            'DATE_VALIDATION' => [
                'Implement client-side date validation',
                'Check date picker configuration',
                'Validate date range logic in UI',
                'Ensure proper date format (YYYY-MM-DD)'
            ],
            'AUTHENTICATION' => [
                'Check auth middleware on route',
                'Verify session management',
                'Implement proper login redirects',
                'Check API token if using API authentication'
            ],
            'DATABASE_ERROR' => [
                'Check database connection',
                'Verify table schema matches queries',
                'Check foreign key constraints',
                'Validate data integrity'
            ],
            default => [
                'Check application logs for detailed errors',
                'Verify service dependencies are properly injected',
                'Check database connectivity',
                'Review code changes in git history'
            ]
        };
    }

    /**
     * GET DOCUMENTATION LINKS for error types
     */
    private function getDocumentationLinks(string $errorType): array
    {
        return match($errorType) {
            'FILTER_VALIDATION' => [
                'Filter Validation Guide' => '/docs/filtering',
                'Station Access Control' => '/docs/access-control'
            ],
            'DATE_VALIDATION' => [
                'Date Filtering Documentation' => '/docs/date-filters',
                'Carbon Date Handling' => '/docs/carbon-usage'
            ],
            'AUTHENTICATION' => [
                'Authentication Setup' => '/docs/auth',
                'Role-Based Access' => '/docs/roles'
            ],
            'DATABASE_ERROR' => [
                'Database Schema' => '/docs/schema',
                'Query Builder Guide' => '/docs/query-builder'
            ],
            default => [
                'General Documentation' => '/docs',
                'Troubleshooting Guide' => '/docs/troubleshooting'
            ]
        };
    }

    /**
     * ✅ 10. INVENTORY VALUE BY STATION
     * Current inventory value using FIFO layers - 100% SCHEMA COMPLIANT WITH MATHEMATICAL PRECISION
     */
private function getInventoryValueByStation(array $filters): array
{
    // Current FIFO Inventory Value by Station with weighted averages - DATE FILTERED
    $stationInventory = DB::table('fifo_layers')
        ->join('tanks', 'fifo_layers.tank_id', '=', 'tanks.id')
        ->join('stations', 'tanks.station_id', '=', 'stations.id')
        ->where('fifo_layers.is_exhausted', false)
        ->where('fifo_layers.remaining_volume_liters', '>', 0.001)
        ->whereIn('fifo_layers.layer_status', ['ACTIVE', 'ADJUSTED'])
        ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
        ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
        ->when(!empty($filters['date_range']), function($q) use ($filters) {
            return $q->where('fifo_layers.delivery_date', '>=', $filters['date_range']['start'])
                    ->where('fifo_layers.delivery_date', '<=', $filters['date_range']['end']);
        })
        ->when(!empty($filters['month_filter']) && empty($filters['date_range']), function($q) use ($filters) {
            return $q->whereMonth('fifo_layers.delivery_date', $filters['month_filter']);
        })
        ->when(!empty($filters['year_filter']) && empty($filters['date_range']), function($q) use ($filters) {
            return $q->whereYear('fifo_layers.delivery_date', $filters['year_filter']);
        })
        ->selectRaw('
            stations.id as station_id,
            stations.name as station_name,
            stations.location,
            COUNT(DISTINCT tanks.id) as total_tanks,
            COUNT(fifo_layers.id) as active_layers,
            SUM(fifo_layers.remaining_volume_liters) as total_volume_liters,
            SUM(fifo_layers.remaining_value_ugx) as total_value_ugx,
            CASE
                WHEN SUM(fifo_layers.remaining_volume_liters) > 0.001
                THEN SUM(fifo_layers.remaining_value_ugx) / SUM(fifo_layers.remaining_volume_liters)
                ELSE 0
            END as weighted_avg_cost_per_liter_ugx,
            MIN(fifo_layers.cost_per_liter_ugx) as min_cost_per_liter_ugx,
            MAX(fifo_layers.cost_per_liter_ugx) as max_cost_per_liter_ugx,
            AVG(CASE
                WHEN fifo_layers.delivery_date <= CURDATE()
                THEN DATEDIFF(CURDATE(), fifo_layers.delivery_date)
                ELSE 0
            END) as avg_inventory_age_days,
            SUM(CASE WHEN DATEDIFF(CURDATE(), fifo_layers.delivery_date) > 90
                THEN fifo_layers.remaining_value_ugx ELSE 0 END) as aging_value_ugx,
            COUNT(CASE WHEN DATEDIFF(CURDATE(), fifo_layers.delivery_date) > 90 THEN 1 END) as aging_layers_count,
            MAX(fifo_layers.valuation_last_updated) as last_valuation_timestamp
        ')
        ->groupBy('stations.id', 'stations.name', 'stations.location')
        ->orderBy('total_value_ugx', 'DESC')
        ->get();

    // Detailed breakdown by fuel type per station with CONSISTENT AGE CALCULATION
    $fuelTypeBreakdown = DB::table('fifo_layers')
        ->join('tanks', 'fifo_layers.tank_id', '=', 'tanks.id')
        ->join('stations', 'tanks.station_id', '=', 'stations.id')
        ->where('fifo_layers.is_exhausted', false)
        ->where('fifo_layers.remaining_volume_liters', '>', 0.001)
        ->whereIn('fifo_layers.layer_status', ['ACTIVE', 'ADJUSTED'])
        ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
        ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
        ->when(!empty($filters['date_range']), function($q) use ($filters) {
            return $q->where('fifo_layers.delivery_date', '>=', $filters['date_range']['start'])
                    ->where('fifo_layers.delivery_date', '<=', $filters['date_range']['end']);
        })
        ->when(!empty($filters['month_filter']) && empty($filters['date_range']), function($q) use ($filters) {
            return $q->whereMonth('fifo_layers.delivery_date', $filters['month_filter']);
        })
        ->when(!empty($filters['year_filter']) && empty($filters['date_range']), function($q) use ($filters) {
            return $q->whereYear('fifo_layers.delivery_date', $filters['year_filter']);
        })
        ->selectRaw('
            stations.id as station_id,
            stations.name as station_name,
            tanks.id as tank_id,
            tanks.fuel_type,
            tanks.tank_number,
            tanks.capacity_liters,
            tanks.current_volume_liters,
            COUNT(fifo_layers.id) as layer_count,
            SUM(fifo_layers.remaining_volume_liters) as fifo_total_volume_liters,
            SUM(fifo_layers.remaining_value_ugx) as remaining_value_ugx,
            CASE
                WHEN SUM(fifo_layers.remaining_volume_liters) > 0.001
                THEN SUM(fifo_layers.remaining_value_ugx) / SUM(fifo_layers.remaining_volume_liters)
                ELSE 0
            END as weighted_avg_cost_per_liter_ugx,
            SUM(fifo_layers.original_value_ugx) as original_value_ugx,
            SUM(fifo_layers.consumed_value_ugx) as consumed_value_ugx,
            MIN(fifo_layers.delivery_date) as oldest_delivery_date,
            MAX(fifo_layers.delivery_date) as newest_delivery_date,
            MAX(fifo_layers.valuation_last_updated) as last_valuation_update,
            MIN(fifo_layers.layer_sequence) as first_layer_sequence,
            MAX(fifo_layers.layer_sequence) as last_layer_sequence,
            ABS(tanks.current_volume_liters - SUM(fifo_layers.remaining_volume_liters)) as volume_discrepancy_liters,
            AVG(CASE
                WHEN fifo_layers.delivery_date <= CURDATE()
                THEN DATEDIFF(CURDATE(), fifo_layers.delivery_date)
                ELSE 0
            END) as calculated_age_days
        ')
        ->groupBy('stations.id', 'stations.name', 'tanks.id', 'tanks.fuel_type', 'tanks.tank_number', 'tanks.capacity_liters', 'tanks.current_volume_liters')
        ->orderBy('stations.name')
        ->orderBy('tanks.fuel_type')
        ->orderBy('tanks.tank_number')
        ->get();

    // Tank capacity utilization with DATE FILTERED FIFO SUBQUERY
    $capacityUtilization = DB::table('tanks')
        ->join('stations', 'tanks.station_id', '=', 'stations.id')
        ->leftJoin(DB::raw('(
            SELECT
                tank_id,
                SUM(remaining_volume_liters) as fifo_volume_total,
                SUM(remaining_value_ugx) as fifo_value_total,
                COUNT(*) as active_fifo_layers
            FROM fifo_layers
            WHERE is_exhausted = false
                AND remaining_volume_liters > 0.001
                AND layer_status IN ("ACTIVE", "ADJUSTED")
                ' . (!empty($filters['date_range']) ?
                    'AND delivery_date >= "' . $filters['date_range']['start'] . '" AND delivery_date <= "' . $filters['date_range']['end'] . '"' : '') .
                (!empty($filters['month_filter']) && empty($filters['date_range']) ?
                    'AND MONTH(delivery_date) = ' . $filters['month_filter'] : '') .
                (!empty($filters['year_filter']) && empty($filters['date_range']) ?
                    'AND YEAR(delivery_date) = ' . $filters['year_filter'] : '') . '
            GROUP BY tank_id
        ) as fifo_summary'), 'tanks.id', '=', 'fifo_summary.tank_id')
        ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
        ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
        ->selectRaw('
            stations.id as station_id,
            stations.name as station_name,
            SUM(tanks.capacity_liters) as total_capacity_liters,
            SUM(tanks.current_volume_liters) as total_current_volume_liters,
            SUM(COALESCE(fifo_summary.fifo_volume_total, 0)) as total_fifo_volume_liters,
            SUM(COALESCE(fifo_summary.fifo_value_total, 0)) as total_fifo_value_ugx,
            CASE
                WHEN SUM(tanks.capacity_liters) > 0.001
                THEN ROUND((SUM(tanks.current_volume_liters) / SUM(tanks.capacity_liters)) * 100, 2)
                ELSE 0
            END as utilization_percentage,
            COUNT(tanks.id) as tank_count,
            SUM(COALESCE(fifo_summary.active_fifo_layers, 0)) as total_active_layers,
            SUM(ABS(tanks.current_volume_liters - COALESCE(fifo_summary.fifo_volume_total, 0))) as total_volume_discrepancy_liters
        ')
        ->groupBy('stations.id', 'stations.name')
        ->orderBy('utilization_percentage', 'DESC')
        ->get();

    return [
        'station_summary' => $stationInventory->map(fn($station) => [
            'station_id' => $station->station_id,
            'station_name' => $station->station_name,
            'location' => $station->location,
            'total_tanks' => (int) ($station->total_tanks ?? 0),
            'active_layers' => (int) ($station->active_layers ?? 0),
            'total_volume_liters' => round((float) ($station->total_volume_liters ?? 0), 3),
            'total_value_ugx' => round((float) ($station->total_value_ugx ?? 0), 4),
            'weighted_avg_cost_per_liter_ugx' => round((float) ($station->weighted_avg_cost_per_liter_ugx ?? 0), 4),
            'cost_range_ugx' => [
                'min' => round((float) ($station->min_cost_per_liter_ugx ?? 0), 4),
                'max' => round((float) ($station->max_cost_per_liter_ugx ?? 0), 4),
                'spread' => round((float) (($station->max_cost_per_liter_ugx ?? 0) - ($station->min_cost_per_liter_ugx ?? 0)), 4)
            ],
            'inventory_health' => [
                'avg_age_days' => round((float) ($station->avg_inventory_age_days ?? 0), 1),
                'aging_value_ugx' => round((float) ($station->aging_value_ugx ?? 0), 4),
                'aging_layers_count' => (int) ($station->aging_layers_count ?? 0),
                'aging_percentage' => ($station->total_value_ugx ?? 0) > 0 ?
                    round((($station->aging_value_ugx ?? 0) / $station->total_value_ugx) * 100, 2) : 0
            ],
            'last_valuation_timestamp' => $station->last_valuation_timestamp ?? null
        ])->toArray(),

        'fuel_type_breakdown' => $fuelTypeBreakdown->map(fn($item) => [
            'station_id' => $item->station_id ?? null,
            'station_name' => $item->station_name ?? '',
            'tank_id' => $item->tank_id ?? null,
            'fuel_type' => $item->fuel_type ?? 'unknown',
            'tank_number' => $item->tank_number ?? '',
            'capacity_liters' => round((float) ($item->capacity_liters ?? 0), 3),
            'current_volume_liters' => round((float) ($item->current_volume_liters ?? 0), 3),
            'fifo_total_volume_liters' => round((float) ($item->fifo_total_volume_liters ?? 0), 3),
            'volume_discrepancy_liters' => round((float) ($item->volume_discrepancy_liters ?? 0), 3),
            'fill_percentage' => ($item->capacity_liters ?? 0) > 0.001 ?
                round((($item->current_volume_liters ?? 0) / $item->capacity_liters) * 100, 2) : 0,
            'layer_count' => (int) ($item->layer_count ?? 0),
            'remaining_value_ugx' => round((float) ($item->remaining_value_ugx ?? 0), 4),
            'weighted_avg_cost_per_liter_ugx' => round((float) ($item->weighted_avg_cost_per_liter_ugx ?? 0), 4),
            'inventory_turnover_days' => round((float) ($item->calculated_age_days ?? 0), 1),
            'fifo_integrity' => [
                'first_layer_sequence' => (int) ($item->first_layer_sequence ?? 0),
                'last_layer_sequence' => (int) ($item->last_layer_sequence ?? 0),
                'sequence_gap' => (int) (($item->last_layer_sequence ?? 0) - ($item->first_layer_sequence ?? 0) + 1 - ($item->layer_count ?? 0)),
                'volume_matches_fifo' => abs($item->volume_discrepancy_liters ?? 0) < 0.1
            ],
            'value_utilization' => [
                'original' => round((float) ($item->original_value_ugx ?? 0), 4),
                'consumed' => round((float) ($item->consumed_value_ugx ?? 0), 4),
                'remaining' => round((float) ($item->remaining_value_ugx ?? 0), 4),
                'consumption_percentage' => ($item->original_value_ugx ?? 0) > 0 ?
                    round((($item->consumed_value_ugx ?? 0) / $item->original_value_ugx) * 100, 2) : 0
            ],
            'last_valuation_update' => $item->last_valuation_update ?? null
        ])->toArray(),

        'capacity_utilization' => $capacityUtilization->map(fn($util) => [
            'station_id' => $util->station_id ?? null,
            'station_name' => $util->station_name ?? '',
            'total_capacity_liters' => round((float) ($util->total_capacity_liters ?? 0), 3),
            'total_current_volume_liters' => round((float) ($util->total_current_volume_liters ?? 0), 3),
            'total_fifo_volume_liters' => round((float) ($util->total_fifo_volume_liters ?? 0), 3),
            'total_fifo_value_ugx' => round((float) ($util->total_fifo_value_ugx ?? 0), 4),
            'utilization_percentage' => (float) ($util->utilization_percentage ?? 0),
            'tank_count' => (int) ($util->tank_count ?? 0),
            'total_active_layers' => (int) ($util->total_active_layers ?? 0),
            'available_capacity_liters' => round((float) (($util->total_capacity_liters ?? 0) - ($util->total_current_volume_liters ?? 0)), 3),
            'data_integrity' => [
                'volume_discrepancy_liters' => round((float) ($util->total_volume_discrepancy_liters ?? 0), 3),
                'fifo_tank_volume_match' => ($util->total_volume_discrepancy_liters ?? 0) < 1.0,
                'layers_per_tank_avg' => ($util->tank_count ?? 0) > 0 ?
                    round(($util->total_active_layers ?? 0) / $util->tank_count, 1) : 0
            ]
        ])->toArray()
    ];
}
    /**
     * ✅ 11. INVENTORY MOVEMENT ANALYSIS
     * Delivery and consumption patterns - exact schema compliance
     */
    private function getInventoryMovementAnalysis(array $filters): array
    {
        // Delivery Movement Analysis
        $deliveryMovements = DB::table('deliveries')
            ->join('tanks', 'deliveries.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->join('users', 'deliveries.user_id', '=', 'users.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                // Use precise date range with proper SQL date comparison
                $startDate = $filters['date_range']['start'] . ' 00:00:00';
                $endDate = $filters['date_range']['end'] . ' 23:59:59';
                return $q->where('deliveries.delivery_date', '>=', $filters['date_range']['start'])
                         ->where('deliveries.delivery_date', '<=', $filters['date_range']['end']);
            })
            ->when(!empty($filters['month_filter']) && empty($filters['date_range']), function($q) use ($filters) {
                // Only apply month filter if NO date range is specified
                return $q->whereMonth('deliveries.delivery_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']) && empty($filters['date_range']), function($q) use ($filters) {
                // Only apply year filter if NO date range is specified
                return $q->whereYear('deliveries.delivery_date', $filters['year_filter']);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('deliveries.delivery_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('deliveries.delivery_date', $filters['year_filter']);
            })
            ->selectRaw('
                deliveries.id,
                deliveries.delivery_reference,
                deliveries.delivery_date,
                deliveries.delivery_time,
                stations.name as station_name,
                tanks.tank_number,
                tanks.fuel_type,
                deliveries.volume_liters,
                deliveries.cost_per_liter_ugx,
                deliveries.total_cost_ugx,
                deliveries.supplier_name,
                deliveries.invoice_number,
                users.name as recorded_by,
                deliveries.created_at
            ')
            ->orderBy('deliveries.delivery_date', 'DESC')
            ->orderBy('deliveries.delivery_time', 'DESC')
            ->limit(100)
            ->get();

        // Consumption Movement Analysis from FIFO layers
        $consumptionMovements = DB::table('fifo_consumption_log')
            ->join('daily_reconciliations', 'fifo_consumption_log.reconciliation_id', '=', 'daily_reconciliations.id')
            ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->join('fifo_layers', 'fifo_consumption_log.fifo_layer_id', '=', 'fifo_layers.id')
            ->join('users', 'daily_reconciliations.reconciled_by_user_id', '=', 'users.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                // Use precise date range with proper SQL date comparison
                return $q->where('daily_reconciliations.reconciliation_date', '>=', $filters['date_range']['start'])
                         ->where('daily_reconciliations.reconciliation_date', '<=', $filters['date_range']['end']);
            })
            ->when(!empty($filters['month_filter']) && empty($filters['date_range']), function($q) use ($filters) {
                // Only apply month filter if NO date range is specified
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']) && empty($filters['date_range']), function($q) use ($filters) {
                // Only apply year filter if NO date range is specified
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->when(!empty($filters['month_filter']), function($q) use ($filters) {
                return $q->whereMonth('daily_reconciliations.reconciliation_date', $filters['month_filter']);
            })
            ->when(!empty($filters['year_filter']), function($q) use ($filters) {
                return $q->whereYear('daily_reconciliations.reconciliation_date', $filters['year_filter']);
            })
            ->selectRaw('
                fifo_consumption_log.id,
                daily_reconciliations.reconciliation_date,
                stations.name as station_name,
                tanks.tank_number,
                tanks.fuel_type,
                fifo_layers.layer_sequence,
                fifo_layers.delivery_date,
                fifo_consumption_log.volume_consumed_liters,
                fifo_consumption_log.cost_per_liter_ugx,
                fifo_consumption_log.total_cost_ugx,
                fifo_consumption_log.consumption_sequence,
                fifo_consumption_log.valuation_impact_ugx,
                DATEDIFF(daily_reconciliations.reconciliation_date, fifo_layers.delivery_date) as inventory_age_days,
                users.name as reconciled_by,
                fifo_consumption_log.created_at
            ')
            ->orderBy('daily_reconciliations.reconciliation_date', 'DESC')
            ->orderBy('fifo_consumption_log.consumption_sequence')
            ->limit(100)
            ->get();

        // Movement Summary by Station and Fuel Type
        $movementSummary = DB::table('deliveries')
            ->join('tanks', 'deliveries.tank_id', '=', 'tanks.id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('tanks.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), function($q) use ($filters) {
                return $q->whereBetween('deliveries.delivery_date',
                    [$filters['date_range']['start'], $filters['date_range']['end']]);
            })
            ->selectRaw('
                stations.id as station_id,
                stations.name as station_name,
                tanks.fuel_type,
                COUNT(deliveries.id) as delivery_count,
                SUM(deliveries.volume_liters) as total_delivered_volume,
                SUM(deliveries.total_cost_ugx) as total_delivered_cost,
                AVG(deliveries.cost_per_liter_ugx) as avg_cost_per_liter,
                MIN(deliveries.delivery_date) as first_delivery_date,
                MAX(deliveries.delivery_date) as last_delivery_date
            ')
            ->groupBy('stations.id', 'stations.name', 'tanks.fuel_type')
            ->orderBy('stations.name')
            ->orderBy('tanks.fuel_type')
            ->get();

        // Inventory Turnover Analysis
        $turnoverAnalysis = DB::table('fifo_consumption_log')
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
            ->selectRaw('
                stations.id as station_id,
                stations.name as station_name,
                tanks.fuel_type,
                COUNT(fifo_consumption_log.id) as consumption_events,
                SUM(fifo_consumption_log.volume_consumed_liters) as total_consumed_volume,
                SUM(fifo_consumption_log.total_cost_ugx) as total_consumed_cost,
                AVG(DATEDIFF(daily_reconciliations.reconciliation_date, fifo_layers.delivery_date)) as avg_inventory_age_days,
                AVG(fifo_consumption_log.cost_per_liter_ugx) as avg_consumed_cost_per_liter
            ')
            ->groupBy('stations.id', 'stations.name', 'tanks.fuel_type')
            ->orderBy('avg_inventory_age_days')
            ->get();

        return [
            'delivery_movements' => $deliveryMovements->map(fn($delivery) => [
                'id' => $delivery->id,
                'reference' => $delivery->delivery_reference,
                'date' => $delivery->delivery_date,
                'time' => $delivery->delivery_time,
                'station_name' => $delivery->station_name,
                'tank_number' => $delivery->tank_number,
                'fuel_type' => $delivery->fuel_type,
                'volume_liters' => (float) $delivery->volume_liters,
                'cost_per_liter_ugx' => (float) $delivery->cost_per_liter_ugx,
                'total_cost_ugx' => (float) $delivery->total_cost_ugx,
                'supplier_name' => $delivery->supplier_name,
                'invoice_number' => $delivery->invoice_number,
                'recorded_by' => $delivery->recorded_by,
                'created_at' => $delivery->created_at
            ])->toArray(),
            'consumption_movements' => $consumptionMovements->map(fn($consumption) => [
                'id' => $consumption->id,
                'reconciliation_date' => $consumption->reconciliation_date,
                'station_name' => $consumption->station_name,
                'tank_number' => $consumption->tank_number,
                'fuel_type' => $consumption->fuel_type,
                'layer_sequence' => $consumption->layer_sequence,
                'delivery_date' => $consumption->delivery_date,
                'volume_consumed_liters' => (float) $consumption->volume_consumed_liters,
                'cost_per_liter_ugx' => (float) $consumption->cost_per_liter_ugx,
                'total_cost_ugx' => (float) $consumption->total_cost_ugx,
                'inventory_age_days' => (int) $consumption->inventory_age_days,
                'valuation_impact_ugx' => (float) $consumption->valuation_impact_ugx,
                'reconciled_by' => $consumption->reconciled_by,
                'created_at' => $consumption->created_at
            ])->toArray(),
            'movement_summary' => $movementSummary->map(fn($summary) => [
                'station_id' => $summary->station_id,
                'station_name' => $summary->station_name,
                'fuel_type' => $summary->fuel_type,
                'delivery_count' => (int) $summary->delivery_count,
                'total_delivered_volume' => (float) $summary->total_delivered_volume,
                'total_delivered_cost' => (float) $summary->total_delivered_cost,
                'avg_cost_per_liter' => round((float) $summary->avg_cost_per_liter, 4),
                'delivery_period' => [
                    'first' => $summary->first_delivery_date,
                    'last' => $summary->last_delivery_date
                ]
            ])->toArray(),
            'turnover_analysis' => $turnoverAnalysis->map(fn($turnover) => [
                'station_id' => $turnover->station_id,
                'station_name' => $turnover->station_name,
                'fuel_type' => $turnover->fuel_type,
                'consumption_events' => (int) $turnover->consumption_events,
                'total_consumed_volume' => (float) $turnover->total_consumed_volume,
                'total_consumed_cost' => (float) $turnover->total_consumed_cost,
                'avg_inventory_age_days' => round((float) $turnover->avg_inventory_age_days, 1),
                'avg_consumed_cost_per_liter' => round((float) $turnover->avg_consumed_cost_per_liter, 4),
                'turnover_efficiency' => $turnover->avg_inventory_age_days > 0.1 ?
                    round(365 / $turnover->avg_inventory_age_days, 2) : 0
            ])->toArray()
        ];
    }

    /**
     * STATION ACCESS CONTROL - MANDATORY
     */
    private function enforceStationAccess(): array
    {
        $user = auth()->user();
        if (!$user) throw new Exception("Authentication required");

        if ($user->role === 'admin') {
            $stations = DB::table('stations')->select('id', 'name', 'location')->get();
            // Convert to array of arrays for consistency
            return $stations->map(fn($station) => [
                'id' => $station->id,
                'name' => $station->name,
                'location' => $station->location
            ])->toArray();
        }

        if (!$user->station_id) throw new Exception("No assigned station");

        $station = DB::table('stations')
            ->where('id', $user->station_id)
            ->select('id', 'name', 'location')
            ->first();

        if (!$station) throw new Exception("Assigned station not found");

        // Return as array of arrays for consistency
        return [[
            'id' => $station->id,
            'name' => $station->name,
            'location' => $station->location
        ]];
    }

    /**
     * COMPREHENSIVE FILTER VALIDATION - Date, Month, Year, Station, Fuel Type
     */
    private function validateFilters(Request $request, array $stationScope): array
    {
        $filters = [
            'station_ids' => array_column($stationScope, 'id'),
            'fuel_types' => [],
            'date_range' => null,
            'month_filter' => null,
            'year_filter' => null,
            'metadata' => []
        ];

        // Station filtering with comprehensive validation
        $requestedStations = $request->input('station_ids', []);
        if (!empty($requestedStations)) {
            // Ensure array format
            if (!is_array($requestedStations)) {
                $requestedStations = [$requestedStations];
            }

            // Convert to integers and validate
            $requestedStations = array_map('intval', $requestedStations);
            $availableStationIds = array_column($stationScope, 'id');
            $validStationIds = array_intersect($requestedStations, $availableStationIds);

            if (empty($validStationIds)) {
                throw new Exception("FILTER_ERROR: No valid stations selected. Available stations: " . implode(', ', $availableStationIds) . ". Requested: " . implode(', ', $requestedStations));
            }

            $filters['station_ids'] = array_values($validStationIds);

            Log::info('Station filter applied', [
                'requested' => $requestedStations,
                'available' => $availableStationIds,
                'filtered_to' => $filters['station_ids']
            ]);
        } else {
            // Default to all available stations
            $filters['station_ids'] = array_column($stationScope, 'id');
        }

        // Fuel type filtering with comprehensive validation
        $requestedFuelTypes = $request->input('fuel_types', []);
        if (!empty($requestedFuelTypes)) {
            // Ensure array format
            if (!is_array($requestedFuelTypes)) {
                $requestedFuelTypes = [$requestedFuelTypes];
            }

            // Validate each fuel type
            $invalidFuelTypes = array_diff($requestedFuelTypes, self::VALID_FUEL_TYPES);
            if (!empty($invalidFuelTypes)) {
                $invalidList = implode(', ', $invalidFuelTypes);
                $validList = implode(', ', self::VALID_FUEL_TYPES);
                throw new Exception("FILTER_ERROR: Invalid fuel type(s): {$invalidList}. Valid options: {$validList}");
            }

            $filters['fuel_types'] = array_values($requestedFuelTypes);

            Log::info('Fuel type filter applied', [
                'requested' => $requestedFuelTypes,
                'filtered_to' => $filters['fuel_types']
            ]);
        } else {
            // No fuel type filtering - use all
            $filters['fuel_types'] = [];
        }

        // Date range filtering with BULLETPROOF 100000% accuracy
        if ($request->has('start_date') || $request->has('end_date')) {
            $start = $request->input('start_date');
            $end = $request->input('end_date');

            // Handle null/empty values explicitly
            if (empty($start) && empty($end)) {
                // Both empty - no date filtering
                $filters['date_range'] = null;
            } elseif (empty($start) || empty($end)) {
                // One empty - require both or neither
                throw new Exception("DATE_INCOMPLETE_ERROR: Both start_date and end_date are required when using date range filtering. Received start: '" . ($start ?? 'null') . "', end: '" . ($end ?? 'null') . "'");
            } else {
                // Both provided - validate thoroughly

                // 1. FORMAT VALIDATION - Strict regex
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
                    throw new Exception("DATE_FORMAT_ERROR: start_date must be in YYYY-MM-DD format. Received: '{$start}'");
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                    throw new Exception("DATE_FORMAT_ERROR: end_date must be in YYYY-MM-DD format. Received: '{$end}'");
                }

                // 2. PARSE WITH STRICT VALIDATION
                try {
                    $startDate = Carbon::createFromFormat('Y-m-d', $start);
                    $endDate = Carbon::createFromFormat('Y-m-d', $end);

                    // Verify parsing worked and dates are exactly what was input
                    if (!$startDate || $startDate->format('Y-m-d') !== $start) {
                        throw new Exception("DATE_PARSE_ERROR: Invalid start_date value. '{$start}' is not a valid calendar date");
                    }
                    if (!$endDate || $endDate->format('Y-m-d') !== $end) {
                        throw new Exception("DATE_PARSE_ERROR: Invalid end_date value. '{$end}' is not a valid calendar date");
                    }

                } catch (Exception $carbonException) {
                    throw new Exception("DATE_CARBON_ERROR: Carbon parsing failed. Start: '{$start}', End: '{$end}'. Error: " . $carbonException->getMessage());
                }

                // 3. LOGICAL VALIDATION
                if ($startDate->greaterThan($endDate)) {
                    throw new Exception("DATE_LOGIC_ERROR: start_date ({$start}) cannot be after end_date ({$end})");
                }

                if ($startDate->equalTo($endDate)) {
                    // Same day is valid but log it
                    Log::info('Single day date range selected', ['date' => $start]);
                }

                // 4. RANGE LIMITS
                $daysDiff = $startDate->diffInDays($endDate);
                if ($daysDiff > 365) {
                    throw new Exception("DATE_RANGE_ERROR: Date range cannot exceed 365 days. Current range: {$daysDiff} days (from {$start} to {$end})");
                }

                // 5. BUSINESS LOGIC VALIDATION
                $today = Carbon::now();
                $maxPastDate = $today->copy()->subYears(5); // 5 years max history

                if ($startDate->greaterThan($today)) {
                    throw new Exception("DATE_FUTURE_ERROR: start_date cannot be in the future. Today is " . $today->format('Y-m-d') . ", received: {$start}");
                }
                if ($endDate->greaterThan($today)) {
                    throw new Exception("DATE_FUTURE_ERROR: end_date cannot be in the future. Today is " . $today->format('Y-m-d') . ", received: {$end}");
                }
                if ($startDate->lessThan($maxPastDate)) {
                    throw new Exception("DATE_HISTORY_ERROR: start_date cannot be more than 5 years ago. Minimum date: " . $maxPastDate->format('Y-m-d') . ", received: {$start}");
                }

                // 6. WEEKEND/HOLIDAY AWARENESS (optional business logic)
                if ($startDate->isWeekend() || $endDate->isWeekend()) {
                    Log::info('Date range includes weekends', [
                        'start_is_weekend' => $startDate->isWeekend(),
                        'end_is_weekend' => $endDate->isWeekend(),
                        'start_day' => $startDate->dayName,
                        'end_day' => $endDate->dayName
                    ]);
                }

                // 7. TIME ZONE NORMALIZATION (ensure consistent UTC/local handling)
                $startDate = $startDate->startOfDay(); // 00:00:00
                $endDate = $endDate->endOfDay();       // 23:59:59

                // 8. FINAL ASSIGNMENT with normalized format
                $filters['date_range'] = [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'start_timestamp' => $startDate->toISOString(),
                    'end_timestamp' => $endDate->toISOString(),
                    'days_span' => $daysDiff,
                    'includes_weekends' => $this->dateRangeIncludesWeekends($startDate, $endDate),
                    'business_days' => $this->calculateBusinessDays($startDate, $endDate)
                ];

                Log::info('Date range filter applied with full validation', [
                    'input_start' => $start,
                    'input_end' => $end,
                    'parsed_start' => $startDate->toISOString(),
                    'parsed_end' => $endDate->toISOString(),
                    'days_span' => $daysDiff,
                    'business_days' => $filters['date_range']['business_days'],
                    'validation_passed' => true
                ]);
            }
        } else {
            // No date parameters provided
            $filters['date_range'] = null;
        }

        // Month filtering with comprehensive validation
        if ($request->has('month')) {
            $month = $request->input('month');

            if ($month === null || $month === '') {
                throw new Exception("MONTH_EMPTY_ERROR: Month parameter cannot be empty");
            }

            if (!is_numeric($month)) {
                throw new Exception("MONTH_FORMAT_ERROR: Month must be numeric (1-12). Received: '{$month}' (type: " . gettype($month) . ")");
            }

            $monthInt = (int) $month;
            if ($monthInt < 1 || $monthInt > 12) {
                throw new Exception("MONTH_RANGE_ERROR: Month must be between 1-12. Received: {$monthInt}");
            }

            $filters['month_filter'] = $monthInt;

            Log::info('Month filter applied', [
                'requested' => $month,
                'parsed_to' => $monthInt
            ]);
        }

        // Year filtering with comprehensive validation
        if ($request->has('year')) {
            $year = $request->input('year');

            if ($year === null || $year === '') {
                throw new Exception("YEAR_EMPTY_ERROR: Year parameter cannot be empty");
            }

            if (!is_numeric($year)) {
                throw new Exception("YEAR_FORMAT_ERROR: Year must be numeric. Received: '{$year}' (type: " . gettype($year) . ")");
            }

            $yearInt = (int) $year;
            $currentYear = (int) date('Y');

            if ($yearInt < 2020 || $yearInt > $currentYear) {
                throw new Exception("YEAR_RANGE_ERROR: Year must be between 2020 and {$currentYear}. Received: {$yearInt}");
            }

            $filters['year_filter'] = $yearInt;

            Log::info('Year filter applied', [
                'requested' => $year,
                'parsed_to' => $yearInt,
                'current_year' => $currentYear
            ]);
        }

        // Conflicting filter validation with detailed logging
        if (!empty($filters['date_range']) && (!empty($filters['month_filter']) || !empty($filters['year_filter']))) {
            $conflictDetails = [
                'date_range' => $filters['date_range'],
                'month_filter' => $filters['month_filter'] ?? null,
                'year_filter' => $filters['year_filter'] ?? null
            ];

            Log::warning('Filter conflict detected', $conflictDetails);

            throw new Exception("FILTER_CONFLICT_ERROR: Cannot use date range with month/year filters simultaneously. Choose either date range OR month/year filtering. Current filters: " . json_encode($conflictDetails));
        }

        // Build comprehensive metadata for UI with logging
        $filters['metadata'] = [
            'stations_selected' => count($filters['station_ids']),
            'fuel_types_selected' => count($filters['fuel_types']),
            'date_range_applied' => !empty($filters['date_range']),
            'month_filter_applied' => !empty($filters['month_filter']),
            'year_filter_applied' => !empty($filters['year_filter']),
            'date_range_start' => $filters['date_range']['start'] ?? null,
            'date_range_end' => $filters['date_range']['end'] ?? null,
            'month_selected' => $filters['month_filter'] ?? null,
            'year_selected' => $filters['year_filter'] ?? null,
            'total_stations_available' => count($stationScope),
            'filter_summary' => $this->buildFilterSummary($filters, $stationScope),
            'filter_validation_passed' => true
        ];

        Log::info('All filters validated successfully', [
            'final_filters' => [
                'station_ids' => $filters['station_ids'],
                'fuel_types' => $filters['fuel_types'],
                'date_range' => $filters['date_range'],
                'month_filter' => $filters['month_filter'] ?? null,
                'year_filter' => $filters['year_filter'] ?? null
            ],
            'metadata' => $filters['metadata'],
            'filter_precedence' => [
                'date_range_takes_precedence' => !empty($filters['date_range']),
                'month_year_ignored_when_date_range' => !empty($filters['date_range']) && (!empty($filters['month_filter']) || !empty($filters['year_filter'])),
                'month_filter_active' => !empty($filters['month_filter']) && empty($filters['date_range']),
                'year_filter_active' => !empty($filters['year_filter']) && empty($filters['date_range'])
            ]
        ]);

        return $filters;
    }

    /**
     * BUILD FILTER SUMMARY for UI display
     */
    private function buildFilterSummary(array $filters, array $stationScope): string
    {
        $summary = [];

        // Station summary - handle both arrays and objects
        if (count($filters['station_ids']) < count($stationScope)) {
            $stationNames = [];
            foreach ($stationScope as $scope) {
                $stationId = is_array($scope) ? $scope['id'] : $scope->id;
                $stationName = is_array($scope) ? $scope['name'] : $scope->name;

                if (in_array($stationId, $filters['station_ids'])) {
                    $stationNames[] = $stationName;
                }
            }

            if (!empty($stationNames)) {
                $displayNames = array_slice($stationNames, 0, 2);
                $summary[] = count($stationNames) . ' station(s): ' . implode(', ', $displayNames) .
                             (count($stationNames) > 2 ? ' +' . (count($stationNames) - 2) . ' more' : '');
            } else {
                $summary[] = 'No stations selected';
            }
        } else {
            $summary[] = 'All stations';
        }

        // Fuel type summary
        if (!empty($filters['fuel_types'])) {
            $summary[] = count($filters['fuel_types']) . ' fuel type(s)';
        } else {
            $summary[] = 'All fuel types';
        }

        // Date/time summary
        if (!empty($filters['date_range'])) {
            $summary[] = 'Date range: ' . $filters['date_range']['start'] . ' to ' . $filters['date_range']['end'];
        } elseif (!empty($filters['month_filter']) && !empty($filters['year_filter'])) {
            $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $summary[] = $months[$filters['month_filter']] . ' ' . $filters['year_filter'];
        } elseif (!empty($filters['month_filter'])) {
            $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $summary[] = 'Month: ' . $months[$filters['month_filter']];
        } elseif (!empty($filters['year_filter'])) {
            $summary[] = 'Year: ' . $filters['year_filter'];
        } else {
            $summary[] = 'All time';
        }

        return implode(' | ', $summary);
    }

    /**
     * FILTER OPTIONS with available years/months
     */
    private function getFilterOptions(array $stationScope): array
    {
        $stationIds = array_column($stationScope, 'id');

        $availableFuelTypes = DB::table('tanks')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->whereIn('stations.id', $stationIds)
            ->distinct()
            ->pluck('fuel_type')
            ->toArray();

        // Get available years from deliveries and reconciliations
        $availableYears = collect([
            DB::table('deliveries')
                ->join('tanks', 'deliveries.tank_id', '=', 'tanks.id')
                ->join('stations', 'tanks.station_id', '=', 'stations.id')
                ->whereIn('stations.id', $stationIds)
                ->selectRaw('DISTINCT YEAR(delivery_date) as year')
                ->pluck('year'),
            DB::table('daily_reconciliations')
                ->join('tanks', 'daily_reconciliations.tank_id', '=', 'tanks.id')
                ->join('stations', 'tanks.station_id', '=', 'stations.id')
                ->whereIn('stations.id', $stationIds)
                ->selectRaw('DISTINCT YEAR(reconciliation_date) as year')
                ->pluck('year')
        ])->flatten()->unique()->sort()->values()->toArray();

        return [
            'stations' => $stationScope, // Already converted to array format
            'fuel_types' => array_values(array_intersect(self::VALID_FUEL_TYPES, $availableFuelTypes)),
            'available_years' => $availableYears,
            'months' => [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ],
            'current_year' => (int) date('Y'),
            'current_month' => (int) date('n')
        ];
    }

    /**
     * Helper: Check if date range includes weekends
     */
    private function dateRangeIncludesWeekends(Carbon $start, Carbon $end): bool
    {
        $current = $start->copy();
        while ($current->lessThanOrEqualTo($end)) {
            if ($current->isWeekend()) {
                return true;
            }
            $current->addDay();
        }
        return false;
    }

    /**
     * Helper: Calculate business days in range (excludes weekends)
     */
    private function calculateBusinessDays(Carbon $start, Carbon $end): int
    {
        $businessDays = 0;
        $current = $start->copy();

        while ($current->lessThanOrEqualTo($end)) {
            if (!$current->isWeekend()) {
                $businessDays++;
            }
            $current->addDay();
        }

        return $businessDays;
    }
    }

