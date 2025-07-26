<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TankManagementController extends Controller
{
    /**
     * Display tank management dashboard with strict station access control
     * 🔒 ENFORCES STATION-LEVEL ACCESS CONTROL
     */
    public function index(Request $request)
    {
        try {
            // Get user's accessible stations based on role
            $accessible_stations = $this->getUserAccessibleStations();

            $search = $request->get('search');
            $fuel_type = $request->get('fuel_type');
            $status = $request->get('status');
            $station_id = $request->get('station_id');

            // Validate station access if specified
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            // Build tanks query with station filtering
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
                    DB::raw('ROUND((t.current_volume_liters / t.capacity_liters) * 100, 2) as fill_percentage'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id) as total_meters'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id AND is_active = 1) as active_meters'),
                    DB::raw('(SELECT COUNT(*) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0) as active_fifo_layers'),
                    DB::raw('(SELECT COALESCE(SUM(remaining_volume_liters), 0) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0) as fifo_total_volume'),
                    DB::raw('(SELECT COUNT(*) FROM deliveries d WHERE d.tank_id = t.id AND DATE(d.delivery_date) = CURDATE()) as today_deliveries'),
                    DB::raw('(SELECT COUNT(*) FROM daily_reconciliations dr WHERE dr.tank_id = t.id AND dr.reconciliation_date = CURDATE()) as today_reconciliations'),
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open") as open_notifications'),
                    DB::raw('(SELECT price_per_liter_ugx FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) as current_selling_price'),
                    DB::raw('CASE
                        WHEN t.current_volume_liters < (t.capacity_liters * 0.1) THEN "Critical"
                        WHEN t.current_volume_liters < (t.capacity_liters * 0.3) THEN "Low"
                        WHEN t.current_volume_liters > (t.capacity_liters * 0.9) THEN "High"
                        ELSE "Normal"
                    END as stock_status'),
                    DB::raw('CASE
                        WHEN (SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) = 0
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

            if ($fuel_type) {
                $query->where('t.fuel_type', $fuel_type);
            }

            if ($status) {
                $query->having('stock_status', $status);
            }

            if ($station_id) {
                $query->where('t.station_id', $station_id);
            }

            $tanks = $query->paginate(15)->withQueryString();

            // Get summary statistics with station filtering
            $stats_query = DB::table('tanks as t')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select([
                    DB::raw('COUNT(*) as total_tanks'),
                    DB::raw('COUNT(DISTINCT t.station_id) as total_stations'),
                    DB::raw('COUNT(DISTINCT t.fuel_type) as fuel_types_count'),
                    DB::raw('SUM(t.capacity_liters) as total_capacity'),
                    DB::raw('SUM(t.current_volume_liters) as total_current_volume'),
                    DB::raw('ROUND(AVG((t.current_volume_liters / t.capacity_liters) * 100), 2) as avg_fill_percentage'),
                    DB::raw('COUNT(CASE WHEN (SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) = 0 THEN 1 END) as incomplete_tanks')
                ]);

            // Apply station access control to stats
            if (auth()->user()->role !== 'admin') {
                $stats_query->where('t.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $stats_query->where('t.station_id', $station_id);
            }

            $stats = $stats_query->first();

            // Get fuel type breakdown with station filtering
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
                        DB::raw('COUNT(*) as tank_count'),
                        DB::raw('SUM(t.capacity_liters) as total_capacity'),
                        DB::raw('SUM(t.current_volume_liters) as current_volume'),
                        DB::raw('ROUND(AVG((t.current_volume_liters / t.capacity_liters) * 100), 2) as avg_fill_percentage'),
                        DB::raw('COUNT(CASE WHEN (SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) = 0 THEN 1 END) as incomplete_tanks')
                    ])
                    ->groupBy('t.station_id', 's.name', 's.location')
                    ->orderBy('s.name')
                    ->get();
            }

            $fuel_types = DB::table('tanks')
                ->distinct()
                ->pluck('fuel_type');

            return view('tanks.index', compact(
                'tanks',
                'stats',
                'fuel_breakdown',
                'station_breakdown',
                'accessible_stations',
                'fuel_types',
                'search',
                'fuel_type',
                'status',
                'station_id'
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

            // Get existing tank numbers for validation
            $existing_tank_numbers = DB::table('tanks')
                ->where('station_id', $station_id)
                ->pluck('tank_number');

            // Get latest delivery costs for pricing guidance
            $latest_costs = DB::table('deliveries as d')
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->select([
                    't.fuel_type',
                    DB::raw('AVG(d.cost_per_liter_ugx) as avg_cost'),
                    DB::raw('MAX(d.delivery_date) as latest_date')
                ])
                ->where('t.station_id', $station_id)
                ->where('d.delivery_date', '>=', now()->subDays(30))
                ->groupBy('t.fuel_type')
                ->get()
                ->keyBy('fuel_type');

            // Get current pricing for reference
            $current_prices = DB::table('selling_prices')
                ->where('station_id', $station_id)
                ->where('is_active', 1)
                ->get()
                ->keyBy('fuel_type');

            // Get business validation thresholds
            $validation_thresholds = $this->getBusinessValidationThresholds();

            $fuel_types = ['petrol', 'diesel', 'kerosene'];

            return view('tanks.create', compact(
                'accessible_stations',
                'selected_station',
                'existing_tank_numbers',
                'latest_costs',
                'current_prices',
                'validation_thresholds',
                'fuel_types'
            ));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Store tank with integrated pricing - BUSINESS COMPLETE ENTITY CREATION
     * 🛡️ RESPECTS ALL DATABASE AUTOMATIONS AND TRIGGERS
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

            // Get station details for validation
            $station = $accessible_stations->firstWhere('id', $station_id);

            // COMPREHENSIVE BUSINESS VALIDATION
            $validator = Validator::make($request->all(), [
                'station_id' => [
                    'required',
                    'exists:stations,id'
                ],
                'tank_number' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Z0-9\-\_]+$/',
                    Rule::unique('tanks')->where(function ($query) use ($station_id) {
                        return $query->where('station_id', $station_id);
                    })
                ],
                'fuel_type' => [
                    'required',
                    'in:petrol,diesel,kerosene'
                ],
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
                    'required',
                    'numeric',
                    'min:1',
                    'max:99999.9999',
                    'regex:/^\d+(\.\d{1,4})?$/'
                ],
                'selling_price_per_liter' => [
                    'required',
                    'numeric',
                    'min:1',
                    'max:99999.9999',
                    'regex:/^\d+(\.\d{1,4})?$/'
                ],
                'price_effective_date' => [
                    'required',
                    'date',
                    'after_or_equal:today'
                ]
            ], [
                'tank_number.regex' => 'Tank number can only contain uppercase letters, numbers, hyphens, and underscores',
                'tank_number.unique' => 'Tank number already exists for this station',
                'capacity_liters.min' => 'Tank capacity must be at least 1,000 liters',
                'capacity_liters.regex' => 'Capacity can have maximum 3 decimal places',
                'current_volume_liters.regex' => 'Volume can have maximum 3 decimal places',
                'initial_cost_per_liter.regex' => 'Cost can have maximum 4 decimal places',
                'selling_price_per_liter.regex' => 'Price can have maximum 4 decimal places'
            ]);

            // CRITICAL BUSINESS RULE VALIDATIONS
            $validator->after(function ($validator) use ($request) {
                $capacity = $request->capacity_liters;
                $current_volume = $request->current_volume_liters;
                $cost_price = $request->initial_cost_per_liter;
                $selling_price = $request->selling_price_per_liter;

                // Volume cannot exceed capacity
                if ($current_volume > $capacity) {
                    $validator->errors()->add('current_volume_liters', 'Current volume cannot exceed tank capacity');
                }

                // Margin validation (minimum 5% margin)
                if ($selling_price <= $cost_price) {
                    $validator->errors()->add('selling_price_per_liter', 'Selling price must be higher than cost price');
                } else {
                    $margin_percentage = (($selling_price - $cost_price) / $selling_price) * 100;
                    if ($margin_percentage < 5) {
                        $validator->errors()->add('selling_price_per_liter', 'Minimum 5% margin required. Current margin: ' . round($margin_percentage, 2) . '%');
                    }
                }

                // Price change validation against existing prices
                $existing_price = DB::table('selling_prices')
                    ->where('station_id', $request->station_id)
                    ->where('fuel_type', $request->fuel_type)
                    ->where('is_active', 1)
                    ->value('price_per_liter_ugx');

                if ($existing_price) {
                    $change_percentage = abs(($selling_price - $existing_price) / $existing_price * 100);
                    if ($change_percentage > 20) {
                        $validator->errors()->add('selling_price_per_liter', 'Price change exceeds 20% limit. Current: ' . $existing_price . ', Proposed: ' . $selling_price);
                    }
                }
            });

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // BUSINESS ENTITY CREATION - FOLLOWS DATABASE AUTOMATION CHAIN

            // 1. Create tank record
            $tank_id = DB::table('tanks')->insertGetId([
                'station_id' => $station_id,
                'tank_number' => strtoupper(trim($request->tank_number)),
                'fuel_type' => $request->fuel_type,
                'capacity_liters' => round($request->capacity_liters, 3),
                'current_volume_liters' => round($request->current_volume_liters, 3),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 2. Create initial FIFO layer if tank has volume (RESPECTS FIFO AUTOMATION)
            if ($request->current_volume_liters > 0) {
                DB::table('fifo_layers')->insert([
                    'tank_id' => $tank_id,
                    'delivery_id' => null, // Initial stock
                    'layer_sequence' => 1,
                    'original_volume_liters' => round($request->current_volume_liters, 3),
                    'remaining_volume_liters' => round($request->current_volume_liters, 3),
                    'cost_per_liter_ugx' => round($request->initial_cost_per_liter, 4),
                    'delivery_date' => now()->toDateString(),
                    'is_exhausted' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // 3. Deactivate existing selling price if exists (MAINTAINS PRICE HISTORY)
            $existing_price = DB::table('selling_prices')
                ->where('station_id', $station_id)
                ->where('fuel_type', $request->fuel_type)
                ->where('is_active', 1)
                ->first();

            if ($existing_price) {
                DB::table('selling_prices')
                    ->where('id', $existing_price->id)
                    ->update([
                        'is_active' => 0,
                        'effective_to_date' => $request->price_effective_date,
                        // 'updated_at' => now()
                    ]);
            }

            // 4. Create selling price (TRIGGERS tr_selling_prices_hash_chain)
            $price_id = DB::table('selling_prices')->insertGetId([
                'station_id' => $station_id,
                'fuel_type' => $request->fuel_type,
                'price_per_liter_ugx' => round($request->selling_price_per_liter, 4),
                'effective_from_date' => $request->price_effective_date,
                'effective_to_date' => null,
                'is_active' => 1,
                'set_by_user_id' => auth()->id(),
                'created_at' => now()
            ]);

            // 5. Create stock alert thresholds (SUPPORTS AUTOMATION MONITORING)
            DB::table('stock_alert_thresholds')->insert([
                'tank_id' => $tank_id,
                'low_stock_percentage' => 20.00,
                'critical_stock_percentage' => 10.00,
                'reorder_point_liters' => round($request->capacity_liters * 0.15, 3),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 6. Audit logging (MAINTAINS FULL AUDIT TRAIL)
            $this->logAuditAction('tanks', $tank_id, 'INSERT', null, [
                'station_id' => $station_id,
                'tank_number' => strtoupper(trim($request->tank_number)),
                'fuel_type' => $request->fuel_type,
                'capacity_liters' => round($request->capacity_liters, 3),
                'current_volume_liters' => round($request->current_volume_liters, 3),
                'integrated_pricing' => true
            ]);

            $this->logAuditAction('selling_prices', $price_id, 'INSERT', null, [
                'tank_creation_integrated' => true,
                'station_id' => $station_id,
                'fuel_type' => $request->fuel_type,
                'price_per_liter_ugx' => round($request->selling_price_per_liter, 4)
            ]);

            DB::commit();

            // Check if request expects JSON (AJAX)
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tank created successfully with integrated pricing. Business entity is complete and operational.',
                    'redirect_url' => route('tanks.index', ['station_id' => $request->station_id])
                ], 201);
            }

            // Traditional form submission - redirect with flash message
            return redirect()->route('tanks.index', ['station_id' => $request->station_id])
                ->with('success', 'Tank created successfully with integrated pricing. Business entity is complete and operational.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Check if request expects JSON (AJAX)
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'error' => false,
                    'message' => $e->getMessage(),
                    'errors' => [ $e->getMessage()]
                ], 422);
            }

            // Traditional form submission - redirect back with error
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

            // Get tank with comprehensive details
            $tank = DB::table('tanks as t')
                ->select([
                    't.*',
                    's.name as station_name',
                    's.location as station_location',
                    's.currency_code',
                    DB::raw('ROUND((t.current_volume_liters / t.capacity_liters) * 100, 2) as fill_percentage'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id) as total_meters'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id AND is_active = 1) as active_meters'),
                    DB::raw('(SELECT COUNT(*) FROM deliveries WHERE tank_id = t.id) as total_deliveries'),
                    DB::raw('(SELECT COALESCE(SUM(volume_liters), 0) FROM deliveries WHERE tank_id = t.id AND delivery_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as volume_delivered_30days'),
                    DB::raw('(SELECT COUNT(*) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0) as active_fifo_layers'),
                    DB::raw('(SELECT COALESCE(SUM(remaining_volume_liters), 0) FROM fifo_layers WHERE tank_id = t.id AND is_exhausted = 0) as fifo_total_volume'),
                    DB::raw('(SELECT COUNT(*) FROM daily_reconciliations WHERE tank_id = t.id AND reconciliation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as reconciliations_30days'),
                    DB::raw('(SELECT price_per_liter_ugx FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) as current_selling_price'),
                    DB::raw('CASE
                        WHEN (SELECT COUNT(*) FROM selling_prices sp WHERE sp.station_id = t.station_id AND sp.fuel_type = t.fuel_type AND sp.is_active = 1) = 0
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

            // Get current FIFO layers
            $fifo_layers = DB::table('fifo_layers as fl')
                ->select([
                    'fl.*',
                    DB::raw('CASE WHEN fl.delivery_id IS NULL THEN "Initial Stock" ELSE d.delivery_reference END as source_reference'),
                    DB::raw('(fl.remaining_volume_liters * fl.cost_per_liter_ugx) as remaining_value_ugx')
                ])
                ->leftJoin('deliveries as d', 'fl.delivery_id', '=', 'd.id')
                ->where('fl.tank_id', $tank_id)
                ->where('fl.is_exhausted', false)
                ->where('fl.remaining_volume_liters', '>', 0)
                ->orderBy('fl.layer_sequence')
                ->get();

            // Get meters for this tank
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

            // Get recent deliveries (last 10)
            $recent_deliveries = DB::table('deliveries as d')
                ->select([
                    'd.*',
                    'u.first_name',
                    'u.last_name'
                ])
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->where('d.tank_id', $tank_id)
                ->orderBy('d.delivery_date', 'desc')
                ->orderBy('d.delivery_time', 'desc')
                ->limit(10)
                ->get();

            // Get recent reconciliations (last 10)
            $recent_reconciliations = DB::table('daily_reconciliations as dr')
                ->select([
                    'dr.*',
                    'u.first_name',
                    'u.last_name'
                ])
                ->join('users as u', 'dr.reconciled_by_user_id', '=', 'u.id')
                ->where('dr.tank_id', $tank_id)
                ->orderBy('dr.reconciliation_date', 'desc')
                ->limit(10)
                ->get();

            // Get stock alert thresholds
            $stock_thresholds = DB::table('stock_alert_thresholds')
                ->where('tank_id', $tank_id)
                ->where('is_active', true)
                ->first();

            // Get recent notifications
            $recent_notifications = DB::table('notifications')
                ->where('tank_id', $tank_id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return view('tanks.show', compact(
                'tank',
                'fifo_layers',
                'meters',
                'recent_deliveries',
                'recent_reconciliations',
                'stock_thresholds',
                'recent_notifications'
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

            // Get tank and station with business status
            $tank = DB::table('tanks as t')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select([
                    't.*',
                    's.name as station_name',
                    's.location as station_location',
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
                ->pluck('tank_number');

            $fuel_types = ['petrol', 'diesel', 'kerosene'];

            return view('tanks.edit', compact(
                'tank',
                'accessible_stations',
                'existing_tank_numbers',
                'fuel_types',
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
     * Update tank with STRICT BUSINESS RULE ENFORCEMENT
     * 🛡️ PREVENTS BUSINESS LOGIC VIOLATIONS AND DATABASE AUTOMATION CORRUPTION
     */
    public function update(Request $request, $tank_id)
    {
        DB::beginTransaction();

        try {
            // Validate tank access
            $accessible_stations = $this->getUserAccessibleStations();

            // Get existing tank
            $existing_tank = DB::table('tanks as t')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select('t.*', 's.name as station_name')
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

            // Check for critical dependencies that prevent changes
            $has_deliveries = DB::table('deliveries')->where('tank_id', $tank_id)->exists();
            $has_reconciliations = DB::table('daily_reconciliations')->where('tank_id', $tank_id)->exists();
            $has_fifo_layers = DB::table('fifo_layers')->where('tank_id', $tank_id)->where('is_exhausted', false)->exists();

            // STRICT VALIDATION - BUSINESS RULE ENFORCEMENT
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
                'fuel_type' => [
                    'required',
                    'in:petrol,diesel,kerosene'
                ],
                'capacity_liters' => [
                    'required',
                    'numeric',
                    'min:1000',
                    'max:999999999.999',
                    'regex:/^\d+(\.\d{1,3})?$/'
                ]
            ], [
                'tank_number.regex' => 'Tank number can only contain uppercase letters, numbers, hyphens, and underscores',
                'tank_number.unique' => 'Tank number already exists for this station',
                'capacity_liters.min' => 'Tank capacity must be at least 1,000 liters'
            ]);

            // CRITICAL BUSINESS RULE VALIDATIONS
            $validator->after(function ($validator) use ($request, $existing_tank, $has_deliveries, $has_reconciliations, $has_fifo_layers, $tank_id) {

                // RULE 1: Prevent fuel type change if there are operational dependencies
                if ($request->fuel_type !== $existing_tank->fuel_type && ($has_deliveries || $has_reconciliations)) {
                    $validator->errors()->add('fuel_type', 'Cannot change fuel type: Tank has existing deliveries or reconciliations. This would break FIFO automation and historical accuracy.');
                }

                // RULE 2: Prevent capacity reduction below current volume
                if ($request->capacity_liters < $existing_tank->current_volume_liters) {
                    $validator->errors()->add('capacity_liters', 'Cannot reduce capacity below current volume (' . number_format($existing_tank->current_volume_liters, 3) . 'L). This would violate physical constraints.');
                }

                // RULE 3: Validate capacity against FIFO layers
                if ($has_fifo_layers) {
                    $total_fifo_volume = DB::table('fifo_layers')
                        ->where('tank_id', $tank_id)
                        ->where('is_exhausted', false)
                        ->sum('remaining_volume_liters');

                    if ($request->capacity_liters < $total_fifo_volume) {
                        $validator->errors()->add('capacity_liters', 'Cannot reduce capacity below total FIFO inventory (' . number_format($total_fifo_volume, 3) . 'L). This would corrupt inventory automation.');
                    }
                }

                // RULE 4: Fuel type change impact on pricing
                if ($request->fuel_type !== $existing_tank->fuel_type) {
                    $has_pricing = DB::table('selling_prices')
                        ->where('station_id', $existing_tank->station_id)
                        ->where('fuel_type', $existing_tank->fuel_type)
                        ->where('is_active', 1)
                        ->exists();

                    if ($has_pricing) {
                        $validator->errors()->add('fuel_type', 'Cannot change fuel type: Active pricing exists for current fuel type. This would break price-tank relationship and sales calculations.');
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

            // Update tank
            DB::table('tanks')->where('id', $tank_id)->update($update_data);

            // Update stock alert thresholds if capacity changed
            if ($request->capacity_liters != $existing_tank->capacity_liters) {
                DB::table('stock_alert_thresholds')
                    ->where('tank_id', $tank_id)
                    ->update([
                        'reorder_point_liters' => round($request->capacity_liters * 0.15, 3),
                        'updated_at' => now()
                    ]);
            }

            // Audit logging
            $this->logAuditAction('tanks', $tank_id, 'UPDATE', (array)$existing_tank, $update_data);

            DB::commit();

            return redirect()->route('tanks.show', $tank_id)
                ->with('success', 'Tank updated successfully with business rule compliance.');
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

            // Real-time tank metrics with access control
            $query = DB::table('tanks as t')
                ->select([
                    't.tank_number',
                    't.fuel_type',
                    't.capacity_liters',
                    't.current_volume_liters',
                    's.name as station_name',
                    's.location as station_location',
                    DB::raw('ROUND((t.current_volume_liters / t.capacity_liters) * 100, 2) as fill_percentage'),
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = t.id AND is_active = 1) as active_meters'),
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE tank_id = t.id AND status = "open") as open_notifications'),
                    DB::raw('(SELECT COALESCE(SUM(dr.total_sales_ugx), 0) FROM daily_reconciliations dr WHERE dr.tank_id = t.id AND dr.reconciliation_date = CURDATE()) as today_sales'),
                    DB::raw('(SELECT COALESCE(SUM(dr.total_dispensed_liters), 0) FROM daily_reconciliations dr WHERE dr.tank_id = t.id AND dr.reconciliation_date = CURDATE()) as today_dispensed')
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

            // FIFO layers summary
            $fifo_summary = DB::table('fifo_layers')
                ->where('tank_id', $tank_id)
                ->where('is_exhausted', false)
                ->select([
                    DB::raw('COUNT(*) as active_layers'),
                    DB::raw('SUM(remaining_volume_liters) as total_volume'),
                    DB::raw('AVG(cost_per_liter_ugx) as avg_cost_per_liter'),
                    DB::raw('MIN(delivery_date) as oldest_delivery_date')
                ])
                ->first();

            return response()->json([
                'metrics' => $metrics,
                'fifo_summary' => $fifo_summary
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update stock alert thresholds with validation
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function updateStockThresholds(Request $request, $tank_id)
    {
        DB::beginTransaction();

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

            // Validate thresholds
            $validator = Validator::make($request->all(), [
                'low_stock_percentage' => 'required|numeric|min:5|max:50',
                'critical_stock_percentage' => 'required|numeric|min:1|max:25',
                'reorder_point_liters' => 'required|numeric|min:100'
            ]);

            $validator->after(function ($validator) use ($request) {
                if ($request->critical_stock_percentage >= $request->low_stock_percentage) {
                    $validator->errors()->add('critical_stock_percentage', 'Critical threshold must be less than low stock threshold');
                }
            });

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Update thresholds
            DB::table('stock_alert_thresholds')
                ->where('tank_id', $tank_id)
                ->update([
                    'low_stock_percentage' => $request->low_stock_percentage,
                    'critical_stock_percentage' => $request->critical_stock_percentage,
                    'reorder_point_liters' => round($request->reorder_point_liters, 3),
                    'updated_at' => now()
                ]);

            // Audit logging
            $this->logAuditAction('stock_alert_thresholds', $tank_id, 'UPDATE', null, [
                'low_stock_percentage' => $request->low_stock_percentage,
                'critical_stock_percentage' => $request->critical_stock_percentage,
                'reorder_point_liters' => round($request->reorder_point_liters, 3)
            ]);

            DB::commit();

            return response()->json(['success' => 'Stock thresholds updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete tank with comprehensive dependency validation and access control
     * 🔒 STATION-SCOPED ACCESS CONTROL
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

            // Check for dependencies that prevent deletion
            $dependencies = $this->checkTankDependencies($tank_id);

            if (!empty($dependencies)) {
                return response()->json([
                    'error' => 'Cannot delete tank',
                    'message' => 'Tank has existing data that would be lost:',
                    'dependencies' => $dependencies
                ], 400);
            }

            // Additional business rule checks
            $current_volume = $tank->current_volume_liters;
            if ($current_volume > 0.001) {
                return response()->json([
                    'error' => 'Cannot delete tank with inventory',
                    'message' => 'Tank contains ' . number_format($current_volume, 3) . 'L of fuel. Empty tank before deletion.',
                    'volume' => $current_volume
                ], 400);
            }

            // Check for active pricing
            $has_active_pricing = DB::table('selling_prices')
                ->where('station_id', $tank->station_id)
                ->where('fuel_type', $tank->fuel_type)
                ->where('is_active', 1)
                ->exists();

            if ($has_active_pricing) {
                return response()->json([
                    'error' => 'Cannot delete tank with active pricing',
                    'message' => 'Deactivate or transfer pricing before tank deletion.',
                ], 400);
            }

            // Safe to delete - CASCADE will handle related records
            DB::table('tanks')->where('id', $tank_id)->delete();

            // Audit logging
            $this->logAuditAction('tanks', $tank_id, 'DELETE', (array)$tank, null);

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

            // Get reconciliation history
            $reconciliations = DB::table('daily_reconciliations as dr')
                ->select([
                    'dr.*',
                    'u.first_name',
                    'u.last_name',
                    DB::raw('ABS(dr.variance_percentage) as abs_variance_percentage')
                ])
                ->join('users as u', 'dr.reconciled_by_user_id', '=', 'u.id')
                ->where('dr.tank_id', $tank_id)
                ->where('dr.reconciliation_date', '>=', now()->subDays($days))
                ->orderBy('dr.reconciliation_date', 'desc')
                ->paginate($per_page, ['*'], 'page', $page);

            return response()->json($reconciliations);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get tank FIFO status with access control
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

            // Get detailed FIFO layers
            $fifo_layers = DB::table('fifo_layers as fl')
                ->select([
                    'fl.*',
                    DB::raw('CASE WHEN fl.delivery_id IS NULL THEN "Initial Stock" ELSE d.delivery_reference END as source_reference'),
                    DB::raw('(fl.remaining_volume_liters * fl.cost_per_liter_ugx) as remaining_value_ugx'),
                    DB::raw('DATEDIFF(CURDATE(), fl.delivery_date) as age_days')
                ])
                ->leftJoin('deliveries as d', 'fl.delivery_id', '=', 'd.id')
                ->where('fl.tank_id', $tank_id)
                ->where('fl.is_exhausted', false)
                ->where('fl.remaining_volume_liters', '>', 0)
                ->orderBy('fl.layer_sequence')
                ->get();

            // Calculate FIFO statistics
            $fifo_stats = [
                'total_layers' => $fifo_layers->count(),
                'total_volume' => $fifo_layers->sum('remaining_volume_liters'),
                'total_value' => $fifo_layers->sum('remaining_value_ugx'),
                'avg_cost_per_liter' => $fifo_layers->count() > 0 ? $fifo_layers->avg('cost_per_liter_ugx') : 0,
                'oldest_layer_age' => $fifo_layers->count() > 0 ? $fifo_layers->max('age_days') : 0
            ];

            return response()->json([
                'fifo_layers' => $fifo_layers,
                'fifo_stats' => $fifo_stats
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export tank data with access control
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function exportTankData(Request $request, $tank_id)
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

            $export_type = $request->get('type', 'reconciliations');
            $days = $request->get('days', 30);

            $data = [];
            $filename = '';

            switch ($export_type) {
                case 'reconciliations':
                    $data = DB::table('daily_reconciliations as dr')
                        ->select([
                            'dr.reconciliation_date',
                            'dr.opening_stock_liters',
                            'dr.total_delivered_liters',
                            'dr.total_dispensed_liters',
                            'dr.actual_closing_stock_liters',
                            'dr.volume_variance_liters',
                            'dr.variance_percentage',
                            'dr.total_sales_ugx',
                            'dr.total_cogs_ugx',
                            'dr.gross_profit_ugx'
                        ])
                        ->where('dr.tank_id', $tank_id)
                        ->where('dr.reconciliation_date', '>=', now()->subDays($days))
                        ->orderBy('dr.reconciliation_date', 'desc')
                        ->get();
                    $filename = "tank_{$tank_id}_reconciliations_{$days}days.json";
                    break;

                case 'deliveries':
                    $data = DB::table('deliveries as d')
                        ->select([
                            'd.delivery_date',
                            'd.delivery_time',
                            'd.delivery_reference',
                            'd.volume_liters',
                            'd.cost_per_liter_ugx',
                            'd.total_cost_ugx',
                            'd.supplier_name',
                            'd.invoice_number'
                        ])
                        ->where('d.tank_id', $tank_id)
                        ->where('d.delivery_date', '>=', now()->subDays($days))
                        ->orderBy('d.delivery_date', 'desc')
                        ->get();
                    $filename = "tank_{$tank_id}_deliveries_{$days}days.json";
                    break;

                case 'fifo':
                    $data = DB::table('fifo_layers as fl')
                        ->select([
                            'fl.layer_sequence',
                            'fl.original_volume_liters',
                            'fl.remaining_volume_liters',
                            'fl.cost_per_liter_ugx',
                            'fl.delivery_date',
                            'fl.is_exhausted'
                        ])
                        ->where('fl.tank_id', $tank_id)
                        ->orderBy('fl.layer_sequence')
                        ->get();
                    $filename = "tank_{$tank_id}_fifo_layers.json";
                    break;

                default:
                    return response()->json(['error' => 'Invalid export type'], 400);
            }

            // Audit logging
            $this->logAuditAction('tank_export', $tank_id, 'EXPORT', null, [
                'export_type' => $export_type,
                'days' => $days,
                'record_count' => count($data)
            ]);

            return response()->json($data)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check for dependencies that prevent tank deletion
     * 🔍 COMPREHENSIVE DEPENDENCY VALIDATION
     */
    private function checkTankDependencies($tank_id)
    {
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

        // Check daily readings
        $reading_count = DB::table('daily_readings')->where('tank_id', $tank_id)->count();
        if ($reading_count > 0) {
            $dependencies[] = "{$reading_count} daily readings";
        }

        // Check notifications
        $notification_count = DB::table('notifications')->where('tank_id', $tank_id)->count();
        if ($notification_count > 0) {
            $dependencies[] = "{$notification_count} notifications";
        }

        return $dependencies;
    }

    /**
     * Get user's accessible stations based on role
     * 🔒 CORE ACCESS CONTROL METHOD
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
                ->select('id', 'name', 'location', 'currency_code')
                ->orderBy('name')
                ->get();
        }

        // Non-admin users only get their assigned station
        return DB::table('stations')
            ->select('id', 'name', 'location', 'currency_code')
            ->where('id', $user->station_id)
            ->get();
    }

    /**
     * Get business validation thresholds
     * 📊 BUSINESS RULE CONFIGURATION
     */
    private function getBusinessValidationThresholds()
    {
        return [
            'minimum_margin_pct' => 5.0,
            'maximum_price_change_pct' => 20.0,
            'minimum_capacity_liters' => 1000,
            'maximum_volume_precision' => 3,
            'maximum_price_precision' => 4,
            'low_stock_default_pct' => 20.0,
            'critical_stock_default_pct' => 10.0,
            'reorder_point_multiplier' => 0.15
        ];
    }

    /**
     * Log audit action - APPEND ONLY for security
     * 🔒 TAMPER-EVIDENT AUDIT TRAIL
     */
    private function logAuditAction($table_name, $record_id, $action, $old_values, $new_values)
    {
        try {
            DB::table('audit_log')->insert([
                'table_name' => $table_name,
                'record_id' => $record_id,
                'action' => $action,
                'old_values' => $old_values ? json_encode($old_values) : null,
                'new_values' => $new_values ? json_encode($new_values) : null,
                'user_id' => auth()->id() ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            \Log::error('Audit log failed: ' . $e->getMessage());
        }
    }
}
