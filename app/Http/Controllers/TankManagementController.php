<?php

namespace App\Http\Controllers;

use App\Services\FuelERP_CriticalPrecisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TankManagementController extends Controller
{
    protected $fuelService;

  // EXACT DATABASE ENUM MATCH - SHELL UGANDA COMPLETE FUEL TYPES
    private const FUEL_TYPES = [
        'petrol', 'diesel', 'kerosene', 'fuelsave_unleaded', 'fuelsave_diesel',
        'v_power_unleaded', 'v_power_diesel', 'ago', 'super_unleaded', 'jet_a1',
        'avgas_100ll', 'heavy_fuel_oil', 'marine_gas_oil', 'low_sulfur_diesel',
        'ultra_low_sulfur_diesel', 'lpg', 'cooking_gas', 'industrial_lpg',
        'autogas', 'household_kerosene', 'illuminating_kerosene', 'industrial_kerosene'
    ];

    // FUEL CATEGORIES FOR UI ORGANIZATION
    private const FUEL_CATEGORIES = [
        'Legacy Fuels' => ['petrol', 'diesel', 'kerosene'],
        'Shell Automotive' => ['fuelsave_unleaded', 'fuelsave_diesel', 'v_power_unleaded', 'v_power_diesel', 'ago', 'super_unleaded'],
        'Aviation Fuels' => ['jet_a1', 'avgas_100ll'],
        'Commercial/Industrial' => ['heavy_fuel_oil', 'marine_gas_oil', 'low_sulfur_diesel', 'ultra_low_sulfur_diesel'],
        'LPG Products' => ['lpg', 'cooking_gas', 'industrial_lpg', 'autogas'],
        'Kerosene Variants' => ['household_kerosene', 'illuminating_kerosene', 'industrial_kerosene']
    ];

    public function __construct(FuelERP_CriticalPrecisionService $fuelService)
    {
        $this->fuelService = $fuelService;
    }

  /**
     * Display tank management dashboard with strict station access control + 3D VISUALIZATION DATA
     * 🔒 ENFORCES STATION-LEVEL ACCESS CONTROL
     * 📊 INCLUDES FIFO LAYERS & CRITICAL STATS FOR 3D VISUALIZATION
     */
    public function index(Request $request)
    {
        try {
            // Get user's accessible stations based on role
            $accessible_stations = $this->getUserAccessibleStations();

            $search = $request->get('search');
            $fuel_type = $request->get('fuel_type');
            $fuel_category = $request->get('fuel_category');
            $status = $request->get('status');
            $station_id = $request->get('station_id');

            // Validate station access if specified
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            // Build tanks query with station filtering - EXACT DATABASE SCHEMA
            $query = DB::table('tanks as t')
                ->select([
                    't.id',
                    't.station_id',
                    't.tank_number',
                    't.fuel_type',
                    't.capacity_liters',
                    't.current_volume_liters',
                    't.created_at',
                    't.updated_at',
                    's.name as station_name',
                    's.location as station_location',
                    's.currency_code',
                    's.timezone',
                    DB::raw('ROUND((t.current_volume_liters / t.capacity_liters) * 100, 2) as fill_percentage'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id) as total_meters'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id AND is_active = 1) as active_meters'),
                    DB::raw('(SELECT COUNT(*) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0) as active_fifo_layers'),
                    DB::raw('(SELECT COALESCE(SUM(remaining_volume_liters), 0) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0 AND remaining_volume_liters > 0.001) as fifo_total_volume'),
                    DB::raw('(SELECT COALESCE(SUM(remaining_value_ugx), 0) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0 AND remaining_volume_liters > 0.001) as fifo_total_value'),
                    DB::raw('(SELECT COUNT(*) FROM deliveries d WHERE d.tank_id = t.id AND DATE(d.delivery_date) = CURDATE()) as today_deliveries'),
                    DB::raw('(SELECT COUNT(*) FROM daily_reconciliations dr WHERE dr.tank_id = t.id AND dr.reconciliation_date = CURDATE()) as today_reconciliations'),
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open") as open_notifications'),
                    DB::raw('(SELECT price_per_liter_ugx FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1 ORDER BY sp.effective_from_date DESC LIMIT 1) as current_selling_price'),
                    DB::raw('(SELECT effective_from_date FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1 ORDER BY sp.effective_from_date DESC LIMIT 1) as price_effective_date'),
                    DB::raw('CASE
                        WHEN t.current_volume_liters <= (t.capacity_liters * 0.10) THEN "Critical"
                        WHEN t.current_volume_liters <= (t.capacity_liters * 0.20) THEN "Low"
                        WHEN t.current_volume_liters >= (t.capacity_liters * 0.90) THEN "High"
                        ELSE "Normal"
                    END as stock_status'),
                    DB::raw('CASE
                        WHEN (SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) = 0
                        THEN "INCOMPLETE"
                        WHEN (SELECT COUNT(*) FROM stock_alert_thresholds sat WHERE sat.tank_id = t.id AND sat.is_active = 1) = 0
                        THEN "INCOMPLETE"
                        ELSE "OPERATIONAL"
                    END as business_status')
                ])
                ->join('stations as s', 't.station_id', '=', 's.id');

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $query->where('t.station_id', auth()->user()->station_id);
            }

            $query->orderBy('s.name')->orderBy('t.tank_number');

            // Apply filters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('t.tank_number', 'like', "%{$search}%")
                        ->orWhere('s.name', 'like', "%{$search}%")
                        ->orWhere('s.location', 'like', "%{$search}%");
                });
            }

            if ($fuel_type && in_array($fuel_type, self::FUEL_TYPES)) {
                $query->where('t.fuel_type', $fuel_type);
            }

            // Filter by fuel category
            if ($fuel_category && isset(self::FUEL_CATEGORIES[$fuel_category])) {
                $query->whereIn('t.fuel_type', self::FUEL_CATEGORIES[$fuel_category]);
            }

            if ($status && in_array($status, ['Critical', 'Low', 'Normal', 'High'])) {
                $query->having('stock_status', $status);
            }

            if ($station_id) {
                $query->where('t.station_id', $station_id);
            }

            $tanks = $query->paginate(15)->withQueryString();

            // Get summary statistics with station filtering - EXACT CALCULATIONS
            $stats_query = DB::table('tanks as t')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select([
                    DB::raw('COUNT(*) as total_tanks'),
                    DB::raw('COUNT(DISTINCT t.station_id) as total_stations'),
                    DB::raw('COUNT(DISTINCT t.fuel_type) as fuel_types_count'),
                    DB::raw('SUM(t.capacity_liters) as total_capacity'),
                    DB::raw('SUM(t.current_volume_liters) as total_current_volume'),
                    DB::raw('ROUND(AVG((t.current_volume_liters / t.capacity_liters) * 100), 2) as avg_fill_percentage'),
                    DB::raw('COUNT(CASE WHEN (SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) = 0 THEN 1 END) as incomplete_pricing_tanks'),
                    DB::raw('COUNT(CASE WHEN (SELECT COUNT(*) FROM stock_alert_thresholds sat WHERE sat.tank_id = t.id AND sat.is_active = 1) = 0 THEN 1 END) as incomplete_threshold_tanks')
                ]);

            // Apply station access control to stats
            if (auth()->user()->role !== 'admin') {
                $stats_query->where('t.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $stats_query->where('t.station_id', $station_id);
            }

            $stats = $stats_query->first();

            // Get fuel type breakdown with station filtering - ALL SHELL UGANDA FUEL TYPES
            $fuel_breakdown_query = DB::table('tanks as t')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select([
                    't.fuel_type',
                    DB::raw('COUNT(*) as tank_count'),
                    DB::raw('SUM(t.capacity_liters) as total_capacity'),
                    DB::raw('SUM(t.current_volume_liters) as current_volume'),
                    DB::raw('ROUND(AVG((t.current_volume_liters / t.capacity_liters) * 100), 2) as avg_fill_percentage'),
                    DB::raw('COUNT(CASE WHEN (SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) = 0 THEN 1 END) as incomplete_tanks')
                ]);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $fuel_breakdown_query->where('t.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $fuel_breakdown_query->where('t.station_id', $station_id);
            }

            $fuel_breakdown = $fuel_breakdown_query->groupBy('t.fuel_type')->get();

            // Get station breakdown for admin users only
            $station_breakdown = collect();
            if (auth()->user()->role === 'admin') {
                $station_breakdown = DB::table('tanks as t')
                    ->join('stations as s', 't.station_id', '=', 's.id')
                    ->select([
                        't.station_id',
                        's.name as station_name',
                        's.location',
                        's.currency_code',
                        's.timezone',
                        DB::raw('COUNT(*) as tank_count'),
                        DB::raw('SUM(t.capacity_liters) as total_capacity'),
                        DB::raw('SUM(t.current_volume_liters) as current_volume'),
                        DB::raw('ROUND(AVG((t.current_volume_liters / t.capacity_liters) * 100), 2) as avg_fill_percentage'),
                        DB::raw('COUNT(CASE WHEN (SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) = 0 THEN 1 END) as incomplete_tanks')
                    ])
                    ->groupBy('t.station_id', 's.name', 's.location', 's.currency_code', 's.timezone')
                    ->orderBy('s.name')
                    ->get();
            }

            // =================== 3D VISUALIZATION DATA ===================

            // Get FIFO layers for 3D tank visualization with exact business calculations
            $fifo_visualization_query = DB::table('fifo_layers as fl')
                ->join('tanks as t', 'fl.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->leftJoin('deliveries as d', 'fl.delivery_id', '=', 'd.id')
                ->select([
                    'fl.id as layer_id',
                    'fl.tank_id',
                    'fl.layer_sequence',
                    'fl.remaining_volume_liters',
                    'fl.original_volume_liters',
                    'fl.cost_per_liter_ugx',
                    'fl.remaining_value_ugx',
                    'fl.delivery_date',
                    'fl.layer_status',
                    'fl.is_exhausted',
                    't.tank_number',
                    't.fuel_type',
                    't.capacity_liters',
                    't.current_volume_liters',
                    's.name as station_name',
                    's.id as station_id',
                    DB::raw('CASE WHEN fl.delivery_id IS NULL THEN "Initial Stock" ELSE d.delivery_reference END as source_reference'),
                    DB::raw('DATEDIFF(CURDATE(), fl.delivery_date) as layer_age_days'),
                    DB::raw('ROUND((fl.remaining_volume_liters / t.capacity_liters) * 100, 3) as layer_fill_percentage'),
                    DB::raw('ROUND((t.current_volume_liters / t.capacity_liters) * 100, 2) as tank_fill_percentage'),
                    // 3D positioning calculations for visualization layers
                    DB::raw('ROUND((fl.layer_sequence - 1) * (fl.remaining_volume_liters / t.current_volume_liters), 4) as layer_start_percentage'),
                    DB::raw('ROUND(fl.layer_sequence * (fl.remaining_volume_liters / t.current_volume_liters), 4) as layer_end_percentage'),
                    // Color coding based on age and cost for 3D visualization
                    DB::raw('CASE
                        WHEN DATEDIFF(CURDATE(), fl.delivery_date) <= 7 THEN "fresh"
                        WHEN DATEDIFF(CURDATE(), fl.delivery_date) <= 30 THEN "recent"
                        WHEN DATEDIFF(CURDATE(), fl.delivery_date) <= 90 THEN "aging"
                        ELSE "old"
                    END as layer_age_category'),
                    DB::raw('CASE
                        WHEN fl.cost_per_liter_ugx >= 6000 THEN "premium"
                        WHEN fl.cost_per_liter_ugx >= 4000 THEN "standard"
                        WHEN fl.cost_per_liter_ugx >= 2000 THEN "economy"
                        ELSE "budget"
                    END as cost_category'),
                    // Tank geometry for 3D rendering (assuming cylindrical tanks)
                    DB::raw('ROUND(POWER(t.capacity_liters / (3.14159 * 4), 0.333), 2) as estimated_tank_radius'),
                    DB::raw('ROUND(t.capacity_liters / (3.14159 * POWER(POWER(t.capacity_liters / (3.14159 * 4), 0.333), 2)), 2) as estimated_tank_height')
                ])
                ->where('fl.is_exhausted', false)
                ->where('fl.remaining_volume_liters', '>', 0.001);

            // Apply station access control to FIFO visualization data
            if (auth()->user()->role !== 'admin') {
                $fifo_visualization_query->where('t.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $fifo_visualization_query->where('t.station_id', $station_id);
            }

            $fifo_layers_3d = $fifo_visualization_query
                ->orderBy('s.name')
                ->orderBy('t.tank_number')
                ->orderBy('fl.layer_sequence')
                ->get();

            // Get critical operational stats for 3D dashboard overlay
            $critical_stats_query = DB::table('tanks as t')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select([
                    't.id as tank_id',
                    't.tank_number',
                    't.fuel_type',
                    's.name as station_name',
                    's.id as station_id',
                    DB::raw('ROUND((t.current_volume_liters / t.capacity_liters) * 100, 2) as fill_percentage'),
                    DB::raw('t.current_volume_liters'),
                    DB::raw('t.capacity_liters'),
                    // Critical alerts for 3D visualization
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open" AND severity = "critical") as critical_alerts'),
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open" AND severity = "high") as high_alerts'),
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open") as total_open_alerts'),
                    // Variance indicators for 3D color coding
                    DB::raw('(SELECT ABS(dr.variance_percentage) FROM daily_reconciliations dr WHERE dr.tank_id = t.id ORDER BY dr.reconciliation_date DESC LIMIT 1) as latest_variance_percentage'),
                    DB::raw('(SELECT dr.reconciliation_date FROM daily_reconciliations dr WHERE dr.tank_id = t.id ORDER BY dr.reconciliation_date DESC LIMIT 1) as latest_reconciliation_date'),
                    // Business health indicators
                    DB::raw('(SELECT COUNT(*) FROM deliveries WHERE tank_id = t.id AND delivery_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as deliveries_7days'),
                    DB::raw('(SELECT COUNT(*) FROM daily_reconciliations WHERE tank_id = t.id AND reconciliation_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as reconciliations_7days'),
                    // 3D visualization threat levels
                    DB::raw('CASE
                        WHEN t.current_volume_liters <= (t.capacity_liters * 0.05) THEN 5
                        WHEN t.current_volume_liters <= (t.capacity_liters * 0.10) THEN 4
                        WHEN t.current_volume_liters <= (t.capacity_liters * 0.20) THEN 3
                        WHEN t.current_volume_liters >= (t.capacity_liters * 0.95) THEN 3
                        WHEN t.current_volume_liters >= (t.capacity_liters * 0.90) THEN 2
                        ELSE 1
                    END as threat_level'),
                    DB::raw('CASE
                        WHEN (SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open" AND severity = "critical") > 0 THEN "CRITICAL"
                        WHEN (SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open" AND severity = "high") > 0 THEN "HIGH"
                        WHEN (SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open") > 0 THEN "MEDIUM"
                        ELSE "NORMAL"
                    END as alert_status'),
                    // Financial health for 3D visualization depth
                    DB::raw('(SELECT sp.price_per_liter_ugx FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1 ORDER BY sp.effective_from_date DESC LIMIT 1) as current_selling_price'),
                    DB::raw('(SELECT AVG(fl.cost_per_liter_ugx) FROM fifo_layers fl WHERE fl.tank_id = t.id AND fl.is_exhausted = 0 AND fl.remaining_volume_liters > 0.001) as avg_inventory_cost'),
                    // 3D positioning data (station layout coordinates - can be customized)
                    DB::raw('(ROW_NUMBER() OVER (PARTITION BY t.station_id ORDER BY t.tank_number) - 1) * 100 as tank_x_position'),
                    DB::raw('0 as tank_y_position'),
                    DB::raw('ROW_NUMBER() OVER (ORDER BY s.name, t.tank_number) * 50 as tank_z_position')
                ]);

            // Apply station access control to critical stats
            if (auth()->user()->role !== 'admin') {
                $critical_stats_query->where('t.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $critical_stats_query->where('t.station_id', $station_id);
            }

            $critical_stats_3d = $critical_stats_query
                ->orderBy('s.name')
                ->orderBy('t.tank_number')
                ->get();

            // Group FIFO layers by tank for easier 3D processing
            $fifo_layers_by_tank = $fifo_layers_3d->groupBy('tank_id');

            // Calculate 3D visualization summary metrics
            $visualization_metrics = [
                'total_tanks_with_fifo' => $fifo_layers_by_tank->count(),
                'total_fifo_layers' => $fifo_layers_3d->count(),
                'total_fifo_volume' => round($fifo_layers_3d->sum('remaining_volume_liters'), 3),
                'total_fifo_value' => round($fifo_layers_3d->sum('remaining_value_ugx'), 4),
                'tanks_with_critical_alerts' => $critical_stats_3d->where('critical_alerts', '>', 0)->count(),
                'tanks_with_high_alerts' => $critical_stats_3d->where('high_alerts', '>', 0)->count(),
                'avg_fill_percentage' => round($critical_stats_3d->avg('fill_percentage'), 2),
                'threat_level_distribution' => [
                    'level_5_critical' => $critical_stats_3d->where('threat_level', 5)->count(),
                    'level_4_high' => $critical_stats_3d->where('threat_level', 4)->count(),
                    'level_3_medium' => $critical_stats_3d->where('threat_level', 3)->count(),
                    'level_2_low' => $critical_stats_3d->where('threat_level', 2)->count(),
                    'level_1_normal' => $critical_stats_3d->where('threat_level', 1)->count()
                ],
                'fuel_type_3d_distribution' => $critical_stats_3d->groupBy('fuel_type')->map(function($tanks) {
                    return [
                        'count' => $tanks->count(),
                        'total_volume' => round($tanks->sum('current_volume_liters'), 3),
                        'avg_fill_percentage' => round($tanks->avg('fill_percentage'), 2),
                        'total_alerts' => $tanks->sum('total_open_alerts')
                    ];
                }),
                'station_3d_distribution' => $critical_stats_3d->groupBy('station_id')->map(function($tanks, $station_id) {
                    $station_name = $tanks->first()->station_name;
                    return [
                        'station_name' => $station_name,
                        'tank_count' => $tanks->count(),
                        'total_volume' => round($tanks->sum('current_volume_liters'), 3),
                        'total_capacity' => round($tanks->sum('capacity_liters'), 3),
                        'avg_fill_percentage' => round($tanks->avg('fill_percentage'), 2),
                        'total_alerts' => $tanks->sum('total_open_alerts'),
                        'threat_distribution' => [
                            'critical' => $tanks->where('threat_level', 5)->count(),
                            'high' => $tanks->where('threat_level', 4)->count(),
                            'medium' => $tanks->where('threat_level', 3)->count(),
                            'low' => $tanks->where('threat_level', 2)->count(),
                            'normal' => $tanks->where('threat_level', 1)->count()
                        ]
                    ];
                })
            ];

            // =================== END 3D VISUALIZATION DATA ===================

            // EXACT DATABASE ENUM VALUES - ALL SHELL UGANDA FUEL TYPES
            $fuel_types = self::FUEL_TYPES;
            $fuel_categories = self::FUEL_CATEGORIES;
            $stock_statuses = ['Critical', 'Low', 'Normal', 'High'];

            return view('tanks.index', compact(
                'tanks',
                'stats',
                'fuel_breakdown',
                'station_breakdown',
                'accessible_stations',
                'fuel_types',
                'fuel_categories',
                'stock_statuses',
                'search',
                'fuel_type',
                'fuel_category',
                'status',
                'station_id',
                // 3D VISUALIZATION DATA
                'fifo_layers_3d',
                'fifo_layers_by_tank',
                'critical_stats_3d',
                'visualization_metrics'
            ));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }


    /**
     * Show integrated tank-pricing creation wizard
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function create(Request $request)
    {
        try {
            // Get user's accessible stations
            $accessible_stations = $this->getUserAccessibleStations();

            $station_id = $request->get('station_id');

            if (!$station_id) {
                return view('tanks.create', compact('accessible_stations'));
            }

            // Validate station access
            if (!$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            $selected_station = $accessible_stations->firstWhere('id', $station_id);

            // Get existing tank numbers for validation - EXACT CONSTRAINT CHECK
            $existing_tank_numbers = DB::table('tanks')
                ->where('station_id', $station_id)
                ->pluck('tank_number')
                ->map(function($number) { return strtoupper($number); });

            // Get latest delivery costs for pricing guidance - LAST 30 DAYS - ALL FUEL TYPES
            $latest_costs = DB::table('deliveries as d')
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->select([
                    't.fuel_type',
                    DB::raw('AVG(d.cost_per_liter_ugx) as avg_cost'),
                    DB::raw('MIN(d.cost_per_liter_ugx) as min_cost'),
                    DB::raw('MAX(d.cost_per_liter_ugx) as max_cost'),
                    DB::raw('COUNT(*) as delivery_count'),
                    DB::raw('MAX(d.delivery_date) as latest_date')
                ])
                ->where('t.station_id', $station_id)
                ->where('d.delivery_date', '>=', now()->subDays(30))
                ->groupBy('t.fuel_type')
                ->get()
                ->keyBy('fuel_type');

            // Get current pricing for reference - ACTIVE PRICES ONLY - ALL FUEL TYPES
            $current_prices = DB::table('selling_prices')
                ->where('station_id', $station_id)
                ->where('is_active', 1)
                ->orderBy('effective_from_date', 'desc')
                ->get()
                ->keyBy('fuel_type');

            // EXACT DATABASE ENUM VALUES - ALL SHELL UGANDA FUEL TYPES
            $fuel_types = self::FUEL_TYPES;
            $fuel_categories = self::FUEL_CATEGORIES;

            return view('tanks.create', compact(
                'accessible_stations',
                'selected_station',
                'existing_tank_numbers',
                'latest_costs',
                'current_prices',
                'fuel_types',
                'fuel_categories'
            ));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Store tank with integrated pricing - USES ACTUAL SERVICE METHODS ONLY
     * 🛡️ USES REAL SERVICE METHODS FOR FIFO INTEGRITY
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Get user's accessible stations
            $accessible_stations = $this->getUserAccessibleStations();
            $station_id = $request->input('station_id');

            // Validate station access
            if (!$station_id || !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station')->withInput();
            }

            // BASIC INPUT VALIDATION ONLY
            $validator = Validator::make($request->all(), [
                'station_id' => 'required|exists:stations,id',
                'tank_number' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Z0-9\-\_]+$/',
                    Rule::unique('tanks')->where(function ($query) use ($station_id) {
                        return $query->where('station_id', $station_id);
                    })
                ],
                'fuel_type' => ['required', Rule::in(self::FUEL_TYPES)],
                'capacity_liters' => [
                    'required',
                    'numeric',
                    'min:1000',
                    'max:999999999.999',
                    'regex:/^\d+(\.\d{1,3})?$/'
                ],
                'current_volume_liters' => [
                    'required',
                    'numeric',
                    'min:0',
                    'regex:/^\d+(\.\d{1,3})?$/'
                ],
                'initial_cost_per_liter' => [
                    'required_if:current_volume_liters,>,0',
                    'nullable',
                    'numeric',
                    'min:0.0001',
                    'max:99999.9999',
                    'regex:/^\d+(\.\d{1,4})?$/'
                ],
                'selling_price_per_liter' => [
                    'required',
                    'numeric',
                    'min:0.0001',
                    'max:99999.9999',
                    'regex:/^\d+(\.\d{1,4})?$/'
                ],
                'price_effective_date' => [
                    'required',
                    'date',
                    'after_or_equal:today'
                ]
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // 1. Create tank record (EXACT DATABASE SCHEMA)
            $tank_id = DB::table('tanks')->insertGetId([
                'station_id' => $station_id,
                'tank_number' => strtoupper(trim($request->tank_number)),
                'fuel_type' => $request->fuel_type,
                'capacity_liters' => round($request->capacity_liters, 3),
                'current_volume_liters' => round($request->current_volume_liters, 3),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 2. Create initial FIFO layer if tank has volume - MANUAL CREATION TO AVOID DOUBLE VOLUME
            if ($request->current_volume_liters > 0.001) {
                // Create delivery record for tracking
                $deliveryId = DB::table('deliveries')->insertGetId([
                    'tank_id' => $tank_id,
                    'user_id' => auth()->id(),
                    'delivery_reference' => 'INIT-' . $tank_id . '-' . time(),
                    'volume_liters' => round($request->current_volume_liters, 3),
                    'cost_per_liter_ugx' => round($request->initial_cost_per_liter, 4),
                    'total_cost_ugx' => round($request->current_volume_liters * $request->initial_cost_per_liter, 4),
                    'delivery_date' => now()->toDateString(),
                    'delivery_time' => now()->toTimeString(),
                    'supplier_name' => 'Initial Stock',
                    'invoice_number' => 'INIT-' . $tank_id,
                    'created_at' => now()
                ]);

                // Create initial FIFO layer manually (tank already has the volume)
                $originalValue = round($request->current_volume_liters * $request->initial_cost_per_liter, 4);

                DB::table('fifo_layers')->insert([
                    'tank_id' => $tank_id,
                    'delivery_id' => $deliveryId,
                    'layer_sequence' => 1,
                    'original_volume_liters' => round($request->current_volume_liters, 3),
                    'remaining_volume_liters' => round($request->current_volume_liters, 3),
                    'cost_per_liter_ugx' => round($request->initial_cost_per_liter, 4),
                    'delivery_date' => now()->toDateString(),
                    'is_exhausted' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'original_value_ugx' => $originalValue,
                    'remaining_value_ugx' => $originalValue,
                    'consumed_value_ugx' => 0.0000,
                    'market_value_per_liter_ugx' => null,
                    'lcm_adjustment_ugx' => null,
                    'layer_status' => 'ACTIVE',
                    'valuation_last_updated' => now()
                ]);
            }

            // 3. Create selling price using ACTUAL SERVICE METHOD
            $priceData = [
                'station_id' => $station_id,
                'fuel_type' => $request->fuel_type,
                'price_per_liter_ugx' => round($request->selling_price_per_liter, 4),
                'effective_from_date' => $request->price_effective_date,
                'set_by_user_id' => auth()->id()
            ];

            // 🚨 USE ACTUAL SERVICE METHOD FOR PRICING
            $priceId = $this->fuelService->createSellingPrice($priceData);

            // 4. Create stock alert thresholds using ACTUAL SERVICE METHOD
            $thresholds = [
                'low_stock_percentage' => 20.00,
                'critical_stock_percentage' => 10.00,
                'reorder_point_liters' => round($request->capacity_liters * 0.15, 3)
            ];

            // 🚨 USE ACTUAL SERVICE METHOD FOR STOCK THRESHOLDS
            $this->fuelService->createStockAlertThresholds($tank_id, $thresholds);

            DB::commit();

            // Check if request expects JSON (AJAX)
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tank created successfully with integrated pricing and FIFO automation.',
                    'tank_id' => $tank_id,
                    'redirect_url' => route('tanks.index', ['station_id' => $station_id])
                ], 201);
            }

            return redirect()->route('tanks.index', ['station_id' => $station_id])
                ->with('success', 'Tank created successfully with integrated pricing and FIFO automation.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Check if request expects JSON (AJAX)
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Show tank details with comprehensive data and access control
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function show($tank_id)
    {
        try {
            // Validate tank access
            $accessible_stations = $this->getUserAccessibleStations();

            // Get tank with comprehensive details - EXACT DATABASE SCHEMA
            $tank = DB::table('tanks as t')
                ->select([
                    't.*',
                    's.name as station_name',
                    's.location as station_location',
                    's.currency_code',
                    's.timezone',
                    DB::raw('ROUND((t.current_volume_liters / t.capacity_liters) * 100, 2) as fill_percentage'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id) as total_meters'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id AND is_active = 1) as active_meters'),
                    DB::raw('(SELECT COUNT(*) FROM deliveries WHERE tank_id = t.id) as total_deliveries'),
                    DB::raw('(SELECT COALESCE(SUM(volume_liters), 0) FROM deliveries WHERE tank_id = t.id AND delivery_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as volume_delivered_30days'),
                    DB::raw('(SELECT COUNT(*) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0) as active_fifo_layers'),
                    DB::raw('(SELECT COALESCE(SUM(remaining_volume_liters), 0) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0 AND remaining_volume_liters > 0.001) as fifo_total_volume'),
                    DB::raw('(SELECT COALESCE(SUM(remaining_value_ugx), 0) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0 AND remaining_volume_liters > 0.001) as fifo_total_value'),
                    DB::raw('(SELECT COUNT(*) FROM daily_reconciliations WHERE tank_id = t.id AND reconciliation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as reconciliations_30days'),
                    DB::raw('(SELECT price_per_liter_ugx FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1 ORDER BY sp.effective_from_date DESC LIMIT 1) as current_selling_price'),
                    DB::raw('(SELECT effective_from_date FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1 ORDER BY sp.effective_from_date DESC LIMIT 1) as price_effective_date'),
                    DB::raw('CASE
                        WHEN (SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) = 0
                        THEN "INCOMPLETE"
                        WHEN (SELECT COUNT(*) FROM stock_alert_thresholds sat WHERE sat.tank_id = t.id AND sat.is_active = 1) = 0
                        THEN "INCOMPLETE"
                        ELSE "OPERATIONAL"
                    END as business_status')
                ])
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('t.id', $tank_id);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $tank->where('t.station_id', auth()->user()->station_id);
            }

            $tank = $tank->first();

            if (!$tank) {
                return back()->with('error', 'Tank not found or access denied');
            }

            // Validate station access
            if (!$accessible_stations->contains('id', $tank->station_id)) {
                return back()->with('error', 'Access denied to tank station');
            }

            // Get current FIFO layers - DIRECT DATABASE QUERY (READ-ONLY)
            $fifo_layers = DB::table('fifo_layers as fl')
                ->select([
                    'fl.*',
                    DB::raw('CASE WHEN fl.delivery_id IS NULL THEN "Initial Stock" ELSE d.delivery_reference END as source_reference'),
                    DB::raw('(fl.remaining_volume_liters * fl.cost_per_liter_ugx) as calculated_remaining_value_ugx'),
                    DB::raw('DATEDIFF(CURDATE(), fl.delivery_date) as age_days')
                ])
                ->leftJoin('deliveries as d', 'fl.delivery_id', '=', 'd.id')
                ->where('fl.tank_id', $tank_id)
                ->where('fl.is_exhausted', false)
                ->where('fl.remaining_volume_liters', '>', 0.001)
                ->orderBy('fl.layer_sequence')
                ->get();

            // Basic FIFO integrity check (READ-ONLY)
            $tank_volume = $tank->current_volume_liters;
            $fifo_total_volume = $fifo_layers->sum('remaining_volume_liters');
            $volume_difference = abs($tank_volume - $fifo_total_volume);
            $fifo_integrity_status = ($volume_difference <= 0.1) ? 'VALID' : 'INVALID';

            // Get meters for this tank - EXACT DATABASE SCHEMA
            $meters = DB::table('meters as m')
                ->select([
                    'm.*',
                    DB::raw('(SELECT mr.closing_reading_liters FROM meter_readings mr WHERE mr.meter_id = m.id ORDER BY mr.reading_date DESC LIMIT 1) as last_reading'),
                    DB::raw('(SELECT mr.reading_date FROM meter_readings mr WHERE mr.meter_id = m.id ORDER BY mr.reading_date DESC LIMIT 1) as last_reading_date'),
                    DB::raw('(SELECT COUNT(*) FROM meter_readings WHERE meter_id = m.id) as total_readings')
                ])
                ->where('m.tank_id', $tank_id)
                ->orderBy('m.meter_number')
                ->get();

            // Get recent deliveries (last 10) - EXACT DATABASE SCHEMA
            $recent_deliveries = DB::table('deliveries as d')
                ->select([
                    'd.*',
                    'u.first_name',
                    'u.last_name',
                    'u.employee_id'
                ])
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->where('d.tank_id', $tank_id)
                ->orderBy('d.delivery_date', 'desc')
                ->orderBy('d.delivery_time', 'desc')
                ->limit(10)
                ->get();

            // Get recent reconciliations (last 10) - EXACT DATABASE SCHEMA
            $recent_reconciliations = DB::table('daily_reconciliations as dr')
                ->select([
                    'dr.*',
                    'u.first_name',
                    'u.last_name',
                    'u.employee_id'
                ])
                ->join('users as u', 'dr.reconciled_by_user_id', '=', 'u.id')
                ->where('dr.tank_id', $tank_id)
                ->orderBy('dr.reconciliation_date', 'desc')
                ->limit(10)
                ->get();

            // Get stock alert thresholds - EXACT DATABASE SCHEMA
            $stock_thresholds = DB::table('stock_alert_thresholds')
                ->where('tank_id', $tank_id)
                ->where('is_active', true)
                ->first();

            // Get recent notifications - EXACT DATABASE SCHEMA
            $recent_notifications = DB::table('notifications')
                ->where('tank_id', $tank_id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get fuel category for display
            $fuel_category = $this->getFuelCategory($tank->fuel_type);

            return view('tanks.show', compact(
                'tank',
                'fifo_layers',
                'fifo_integrity_status',
                'meters',
                'recent_deliveries',
                'recent_reconciliations',
                'stock_thresholds',
                'recent_notifications',
                'fuel_category'
            ));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit tank form with RESTRICTED EDITING to prevent business logic violations
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function edit($tank_id)
    {
        try {
            // Validate tank access
            $accessible_stations = $this->getUserAccessibleStations();

            // Get tank and station with business status - EXACT DATABASE SCHEMA
            $tank = DB::table('tanks as t')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select([
                    't.*',
                    's.name as station_name',
                    's.location as station_location',
                    's.currency_code',
                    's.timezone',
                    DB::raw('(SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) as has_pricing')
                ])
                ->where('t.id', $tank_id);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $tank->where('t.station_id', auth()->user()->station_id);
            }

            $tank = $tank->first();

            if (!$tank) {
                return back()->with('error', 'Tank not found or access denied');
            }

            // Validate station access
            if (!$accessible_stations->contains('id', $tank->station_id)) {
                return back()->with('error', 'Access denied to tank station');
            }

            // Check for dependencies that prevent certain changes
            $has_deliveries = DB::table('deliveries')->where('tank_id', $tank_id)->exists();
            $has_reconciliations = DB::table('daily_reconciliations')->where('tank_id', $tank_id)->exists();
            $has_meters = DB::table('meters')->where('tank_id', $tank_id)->exists();
            $has_fifo_layers = DB::table('fifo_layers')->where('tank_id', $tank_id)->where('is_exhausted', false)->exists();

            // Get existing tank numbers for validation (excluding current tank)
            $existing_tank_numbers = DB::table('tanks')
                ->where('station_id', $tank->station_id)
                ->where('id', '!=', $tank_id)
                ->pluck('tank_number')
                ->map(function($number) { return strtoupper($number); });

            // EXACT DATABASE ENUM VALUES - ALL SHELL UGANDA FUEL TYPES
            $fuel_types = self::FUEL_TYPES;
            $fuel_categories = self::FUEL_CATEGORIES;

            // Get current fuel category
            $current_fuel_category = $this->getFuelCategory($tank->fuel_type);

            return view('tanks.edit', compact(
                'tank',
                'accessible_stations',
                'existing_tank_numbers',
                'fuel_types',
                'fuel_categories',
                'current_fuel_category',
                'has_deliveries',
                'has_reconciliations',
                'has_meters',
                'has_fifo_layers'
            ));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update tank - BASIC UPDATES ONLY, NO BUSINESS LOGIC
     * 🛡️ SAFE UPDATES ONLY - NO FIFO CORRUPTION RISK
     */
    public function update(Request $request, $tank_id)
    {
        DB::beginTransaction();

        try {
            // Validate tank access
            $accessible_stations = $this->getUserAccessibleStations();

            // Get existing tank - EXACT DATABASE SCHEMA
            $existing_tank = DB::table('tanks as t')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select('t.*', 's.name as station_name', 's.currency_code', 's.timezone')
                ->where('t.id', $tank_id);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $existing_tank->where('t.station_id', auth()->user()->station_id);
            }

            $existing_tank = $existing_tank->first();

            if (!$existing_tank) {
                return back()->with('error', 'Tank not found or access denied');
            }

            // Validate station access
            if (!$accessible_stations->contains('id', $existing_tank->station_id)) {
                return back()->with('error', 'Access denied to tank station');
            }

            // Check for dependencies that prevent certain changes
            $has_deliveries = DB::table('deliveries')->where('tank_id', $tank_id)->exists();
            $has_reconciliations = DB::table('daily_reconciliations')->where('tank_id', $tank_id)->exists();
            $has_fifo_layers = DB::table('fifo_layers')->where('tank_id', $tank_id)->where('is_exhausted', false)->exists();

            // BASIC INPUT VALIDATION ONLY
            $validator = Validator::make($request->all(), [
                'tank_number' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Z0-9\-\_]+$/',
                    Rule::unique('tanks')->where(function ($query) use ($existing_tank) {
                        return $query->where('station_id', $existing_tank->station_id);
                    })->ignore($tank_id)
                ],
                'fuel_type' => ['required', Rule::in(self::FUEL_TYPES)],
                'capacity_liters' => [
                    'required',
                    'numeric',
                    'min:1000',
                    'max:999999999.999',
                    'regex:/^\d+(\.\d{1,3})?$/'
                ]
            ]);

            // CRITICAL BUSINESS RULE VALIDATIONS - BASIC ONLY
            $validator->after(function ($validator) use ($request, $existing_tank, $has_deliveries, $has_reconciliations, $has_fifo_layers, $tank_id) {
                // RULE 1: Prevent fuel type change if there are operational dependencies
                if ($request->fuel_type !== $existing_tank->fuel_type && ($has_deliveries || $has_reconciliations)) {
                    $validator->errors()->add('fuel_type', 'Cannot change fuel type: Tank has existing deliveries or reconciliations.');
                }

                // RULE 2: Prevent capacity reduction below current volume
                if ($request->capacity_liters < $existing_tank->current_volume_liters) {
                    $validator->errors()->add('capacity_liters', 'Cannot reduce capacity below current volume (' . number_format($existing_tank->current_volume_liters, 3) . 'L).');
                }

                // RULE 3: Validate capacity against FIFO layers
                if ($has_fifo_layers) {
                    $total_fifo_volume = DB::table('fifo_layers')
                        ->where('tank_id', $tank_id)
                        ->where('is_exhausted', false)
                        ->where('remaining_volume_liters', '>', 0.001)
                        ->sum('remaining_volume_liters');

                    if ($request->capacity_liters < $total_fifo_volume) {
                        $validator->errors()->add('capacity_liters', 'Cannot reduce capacity below total FIFO inventory (' . number_format($total_fifo_volume, 3) . 'L).');
                    }
                }
            });

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Prepare safe update data (only non-critical fields)
            $update_data = [
                'tank_number' => strtoupper(trim($request->tank_number)),
                'capacity_liters' => round($request->capacity_liters, 3),
                'updated_at' => now()
            ];

            // Only allow fuel type change if no dependencies
            if (!$has_deliveries && !$has_reconciliations && !$has_fifo_layers) {
                $update_data['fuel_type'] = $request->fuel_type;
            }

            // Update tank - EXACT DATABASE SCHEMA
            $updateResult = DB::table('tanks')->where('id', $tank_id)->update($update_data);

            if ($updateResult === 0) {
                throw new \Exception("Failed to update tank - no rows affected");
            }

            // Update stock alert thresholds if capacity changed
            if ($request->capacity_liters != $existing_tank->capacity_liters) {
                DB::table('stock_alert_thresholds')
                    ->where('tank_id', $tank_id)
                    ->where('is_active', true)
                    ->update([
                        'reorder_point_liters' => round($request->capacity_liters * 0.15, 3),
                        'updated_at' => now()
                    ]);
            }

            DB::commit();

            return redirect()->route('tanks.show', $tank_id)
                ->with('success', 'Tank updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Get tank dashboard data for AJAX with access control
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function getDashboardData($tank_id)
    {
        try {
            // Validate tank access
            $accessible_stations = $this->getUserAccessibleStations();

            // Real-time tank metrics with access control - EXACT DATABASE SCHEMA
            $query = DB::table('tanks as t')
                ->select([
                    't.id',
                    't.tank_number',
                    't.fuel_type',
                    't.capacity_liters',
                    't.current_volume_liters',
                    's.name as station_name',
                    's.location as station_location',
                    's.currency_code',
                    's.timezone',
                    DB::raw('ROUND((t.current_volume_liters / t.capacity_liters) * 100, 2) as fill_percentage'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id AND is_active = 1) as active_meters'),
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open") as open_notifications'),
                    DB::raw('(SELECT COALESCE(SUM(dr.total_sales_ugx), 0) FROM daily_reconciliations dr WHERE dr.tank_id = t.id AND dr.reconciliation_date = CURDATE()) as today_sales'),
                    DB::raw('(SELECT COALESCE(SUM(dr.total_dispensed_liters), 0) FROM daily_reconciliations dr WHERE dr.tank_id = t.id AND dr.reconciliation_date = CURDATE()) as today_dispensed'),
                    DB::raw('(SELECT price_per_liter_ugx FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1 ORDER BY sp.effective_from_date DESC LIMIT 1) as current_selling_price')
                ])
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('t.id', $tank_id);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $query->where('t.station_id', auth()->user()->station_id);
            }

            $metrics = $query->first();

            if (!$metrics) {
                return response()->json(['error' => 'Tank not found or access denied'], 404);
            }

            // Validate station access
            $tank_station_id = DB::table('tanks')->where('id', $tank_id)->value('station_id');
            if (!$accessible_stations->contains('id', $tank_station_id)) {
                return response()->json(['error' => 'Access denied to tank station'], 403);
            }

            // FIFO summary - EXACT DATABASE SCHEMA (READ-ONLY)
            $fifo_summary = DB::table('fifo_layers')
                ->where('tank_id', $tank_id)
                ->where('is_exhausted', false)
                ->where('remaining_volume_liters', '>', 0.001)
                ->select([
                    DB::raw('COUNT(*) as active_layers'),
                    DB::raw('SUM(remaining_volume_liters) as total_volume'),
                    DB::raw('SUM(remaining_value_ugx) as total_value'),
                    DB::raw('AVG(cost_per_liter_ugx) as avg_cost_per_liter'),
                    DB::raw('MIN(delivery_date) as oldest_delivery_date'),
                    DB::raw('MAX(delivery_date) as newest_delivery_date')
                ])
                ->first();

            // Basic FIFO integrity check (READ-ONLY)
            $tank_volume = $metrics->current_volume_liters;
            $fifo_total_volume = $fifo_summary->total_volume ?? 0;
            $volume_difference = abs($tank_volume - $fifo_total_volume);
            $fifo_integrity_status = ($volume_difference <= 0.1) ? 'VALID' : 'INVALID';

            // Get fuel category
            $fuel_category = $this->getFuelCategory($metrics->fuel_type);

            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'fifo_summary' => $fifo_summary,
                'fifo_integrity_status' => $fifo_integrity_status,
                'fuel_category' => $fuel_category
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete tank with basic dependency validation and access control
     * 🔒 STATION-SCOPED ACCESS CONTROL + BASIC SAFETY CHECKS
     */
    public function destroy($tank_id)
    {
        DB::beginTransaction();

        try {
            // Validate tank access
            $accessible_stations = $this->getUserAccessibleStations();

            $tank_query = DB::table('tanks')
                ->where('id', $tank_id);

            // Apply station access control for non-admin users
            if (auth()->user()->role !== 'admin') {
                $tank_query->where('station_id', auth()->user()->station_id);
            }

            $tank = $tank_query->first();

            if (!$tank) {
                return response()->json(['error' => 'Tank not found or access denied'], 404);
            }

            // Validate station access
            if (!$accessible_stations->contains('id', $tank->station_id)) {
                return response()->json(['error' => 'Access denied to tank station'], 403);
            }

            // Check for basic dependencies that prevent deletion
            $dependencies = [];

            // Check deliveries
            $delivery_count = DB::table('deliveries')->where('tank_id', $tank_id)->count();
            if ($delivery_count > 0) {
                $dependencies[] = "{$delivery_count} deliveries";
            }

            // Check reconciliations
            $reconciliation_count = DB::table('daily_reconciliations')->where('tank_id', $tank_id)->count();
            if ($reconciliation_count > 0) {
                $dependencies[] = "{$reconciliation_count} reconciliations";
            }

            // Check meters
            $meter_count = DB::table('meters')->where('tank_id', $tank_id)->count();
            if ($meter_count > 0) {
                $dependencies[] = "{$meter_count} meters";
            }

            // Check FIFO layers
            $fifo_count = DB::table('fifo_layers')->where('tank_id', $tank_id)->count();
            if ($fifo_count > 0) {
                $dependencies[] = "{$fifo_count} inventory layers";
            }

            if (!empty($dependencies)) {
                return response()->json([
                    'error' => 'Cannot delete tank',
                    'message' => 'Tank has existing data that would be lost:',
                    'dependencies' => $dependencies
                ], 400);
            }

            // Additional business rule checks
            if ($tank->current_volume_liters > 0.001) {
                return response()->json([
                    'error' => 'Cannot delete tank with inventory',
                    'message' => 'Tank contains ' . number_format($tank->current_volume_liters, 3) . 'L of fuel. Empty tank before deletion.',
                    'volume' => $tank->current_volume_liters
                ], 400);
            }

            // Safe to delete - CASCADE will handle related records
            $deleteResult = DB::table('tanks')->where('id', $tank_id)->delete();

            if ($deleteResult === 0) {
                throw new \Exception("Failed to delete tank - no rows affected");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tank deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get tank reconciliation history with access control
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function getReconciliationHistory(Request $request, $tank_id)
    {
        try {
            // Validate tank access
            $accessible_stations = $this->getUserAccessibleStations();
            $tank_station_id = DB::table('tanks')->where('id', $tank_id)->value('station_id');

            if (!$tank_station_id || !$accessible_stations->contains('id', $tank_station_id)) {
                return response()->json(['error' => 'Access denied to tank'], 403);
            }

            // Apply station access control for non-admin users
            if (auth()->user()->role !== 'admin') {
                $tank_exists = DB::table('tanks')
                    ->where('id', $tank_id)
                    ->where('station_id', auth()->user()->station_id)
                    ->exists();

                if (!$tank_exists) {
                    return response()->json(['error' => 'Tank not found or access denied'], 404);
                }
            }

            $days = $request->get('days', 30);
            $page = $request->get('page', 1);
            $per_page = 15;

            // Get reconciliation history - EXACT DATABASE SCHEMA
            $query = DB::table('daily_reconciliations as dr')
                ->select([
                    'dr.*',
                    'u.first_name',
                    'u.last_name',
                    'u.employee_id',
                    DB::raw('ABS(dr.variance_percentage) as abs_variance_percentage'),
                    DB::raw('CASE
                        WHEN ABS(dr.variance_percentage) >= 5.0 THEN "CRITICAL"
                        WHEN ABS(dr.variance_percentage) >= 2.0 THEN "HIGH"
                        WHEN ABS(dr.variance_percentage) >= 1.0 THEN "MEDIUM"
                        ELSE "NORMAL"
                    END as variance_severity')
                ])
                ->join('users as u', 'dr.reconciled_by_user_id', '=', 'u.id')
                ->where('dr.tank_id', $tank_id)
                ->where('dr.reconciliation_date', '>=', now()->subDays($days))
                ->orderBy('dr.reconciliation_date', 'desc');

            $reconciliations = $query->paginate($per_page, ['*'], 'page', $page);

            return response()->json($reconciliations);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get tank FIFO status with access control - READ-ONLY
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function getFifoStatus($tank_id)
    {
        try {
            // Validate tank access
            $accessible_stations = $this->getUserAccessibleStations();
            $tank_station_id = DB::table('tanks')->where('id', $tank_id)->value('station_id');

            if (!$tank_station_id || !$accessible_stations->contains('id', $tank_station_id)) {
                return response()->json(['error' => 'Access denied to tank'], 403);
            }

            // Apply station access control for non-admin users
            if (auth()->user()->role !== 'admin') {
                $tank_exists = DB::table('tanks')
                    ->where('id', $tank_id)
                    ->where('station_id', auth()->user()->station_id)
                    ->exists();

                if (!$tank_exists) {
                    return response()->json(['error' => 'Tank not found or access denied'], 404);
                }
            }

            // Get detailed FIFO layers - EXACT DATABASE SCHEMA (READ-ONLY)
            $fifo_layers = DB::table('fifo_layers as fl')
                ->select([
                    'fl.*',
                    DB::raw('CASE WHEN fl.delivery_id IS NULL THEN "Initial Stock" ELSE d.delivery_reference END as source_reference'),
                    DB::raw('(fl.remaining_volume_liters * fl.cost_per_liter_ugx) as calculated_remaining_value_ugx'),
                    DB::raw('DATEDIFF(CURDATE(), fl.delivery_date) as age_days'),
                    DB::raw('CASE
                        WHEN fl.layer_status = "ACTIVE" AND fl.remaining_volume_liters > 0.001 THEN "AVAILABLE"
                        WHEN fl.layer_status = "DEPLETED" OR fl.is_exhausted = 1 THEN "EXHAUSTED"
                        WHEN fl.layer_status = "ADJUSTED" THEN "ADJUSTED"
                        WHEN fl.layer_status = "WRITTEN_DOWN" THEN "WRITTEN_DOWN"
                        ELSE "UNKNOWN"
                    END as status_display')
                ])
                ->leftJoin('deliveries as d', 'fl.delivery_id', '=', 'd.id')
                ->where('fl.tank_id', $tank_id)
                ->where('fl.is_exhausted', false)
                ->where('fl.remaining_volume_liters', '>', 0.001)
                ->orderBy('fl.layer_sequence')
                ->get();

            // Calculate FIFO statistics - EXACT CALCULATIONS
            $fifo_stats = [
                'total_layers' => $fifo_layers->count(),
                'total_volume' => round($fifo_layers->sum('remaining_volume_liters'), 3),
                'total_value' => round($fifo_layers->sum('calculated_remaining_value_ugx'), 4),
                'avg_cost_per_liter' => $fifo_layers->count() > 0 ? round($fifo_layers->avg('cost_per_liter_ugx'), 4) : 0,
                'oldest_layer_age' => $fifo_layers->count() > 0 ? $fifo_layers->max('age_days') : 0,
                'newest_layer_age' => $fifo_layers->count() > 0 ? $fifo_layers->min('age_days') : 0
            ];

            // Basic FIFO integrity check (READ-ONLY)
            $tank_volume = DB::table('tanks')->where('id', $tank_id)->value('current_volume_liters');
            $fifo_total_volume = $fifo_stats['total_volume'];
            $volume_difference = abs($tank_volume - $fifo_total_volume);

            $fifo_integrity_check = [
                'status' => ($volume_difference <= 0.1) ? 'VALID' : 'INVALID',
                'message' => ($volume_difference <= 0.1) ? 'FIFO volume matches tank volume' : 'FIFO volume mismatch detected',
                'tank_volume_match' => ($volume_difference <= 0.1),
                'volume_difference' => round($volume_difference, 3)
            ];

            return response()->json([
                'success' => true,
                'fifo_layers' => $fifo_layers,
                'fifo_stats' => $fifo_stats,
                'integrity_check' => $fifo_integrity_check
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =================== PRIVATE HELPER METHODS ===================

    /**
     * Get user's accessible stations based on role
     * 🔒 CORE ACCESS CONTROL METHOD - EXACT DATABASE SCHEMA
     */
    private function getUserAccessibleStations()
    {
        $user = auth()->user();

        if (!$user) {
            return collect([]);
        }

        // Admin gets all stations
        if ($user->role === 'admin') {
            return DB::table('stations')
                ->select('id', 'name', 'location', 'currency_code', 'timezone')
                ->orderBy('name')
                ->get();
        }

        // Non-admin users only get their assigned station
        return DB::table('stations')
            ->select('id', 'name', 'location', 'currency_code', 'timezone')
            ->where('id', $user->station_id)
            ->get();
    }

    /**
     * Get fuel category for a fuel type
     * 📊 FUEL CATEGORIZATION FOR UI DISPLAY
     */
    private function getFuelCategory($fuel_type)
    {
        foreach (self::FUEL_CATEGORIES as $category => $fuels) {
            if (in_array($fuel_type, $fuels)) {
                return $category;
            }
        }
        return 'Unknown';
    }
}
