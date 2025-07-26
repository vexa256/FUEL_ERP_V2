<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DeliveriesController  extends Controller
{
    /**
     * Display deliveries dashboard with strict station access control
     * 🔒 ENFORCES STATION-LEVEL ACCESS CONTROL - REAL FIELDS ONLY
     */
    public function index(Request $request)
    {
        try {
            // Get user's accessible stations based on role
            $accessible_stations = $this->getUserAccessibleStations();

            $search = $request->get('search');
            $tank_id = $request->get('tank_id');
            $station_id = $request->get('station_id');
            $date_from = $request->get('date_from');
            $date_to = $request->get('date_to');

            // Validate station access if specified
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            // Build deliveries query - ONLY REAL SCHEMA FIELDS
            $query = DB::table('deliveries as d')
                ->select([
                    'd.id',
                    'd.tank_id',
                    'd.delivery_reference',
                    'd.volume_liters',
                    'd.cost_per_liter_ugx',
                    'd.total_cost_ugx',
                    'd.delivery_date',
                    'd.delivery_time',
                    'd.supplier_name',
                    'd.invoice_number',
                    'd.created_at',
                    't.tank_number',
                    't.fuel_type',
                    's.name as station_name',
                    's.currency_code',
                    'u.first_name',
                    'u.last_name'
                ])
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->join('users as u', 'd.user_id', '=', 'u.id');

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $query->where('s.id', auth()->user()->station_id);
            }

            // Apply filters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('d.delivery_reference', 'like', "%{$search}%")
                        ->orWhere('d.supplier_name', 'like', "%{$search}%")
                        ->orWhere('d.invoice_number', 'like', "%{$search}%")
                        ->orWhere('t.tank_number', 'like', "%{$search}%");
                });
            }

            if ($tank_id) {
                $query->where('d.tank_id', $tank_id);
            }

            if ($station_id) {
                $query->where('s.id', $station_id);
            }

            if ($date_from) {
                $query->where('d.delivery_date', '>=', $date_from);
            }

            if ($date_to) {
                $query->where('d.delivery_date', '<=', $date_to);
            }

            $deliveries = $query->orderBy('d.delivery_date', 'desc')
                ->orderBy('d.delivery_time', 'desc')
                ->paginate(15)
                ->withQueryString();

            // Get summary statistics - REAL AGGREGATIONS ONLY
            $stats_query = DB::table('deliveries as d')
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id');

            if (auth()->user()->role !== 'admin') {
                $stats_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $stats_query->where('s.id', $station_id);
            }

            if ($date_from) {
                $stats_query->where('d.delivery_date', '>=', $date_from);
            }

            if ($date_to) {
                $stats_query->where('d.delivery_date', '<=', $date_to);
            }

            $stats = $stats_query->select([
                DB::raw('COUNT(*) as total_deliveries'),
                DB::raw('SUM(d.volume_liters) as total_volume'),
                DB::raw('SUM(d.total_cost_ugx) as total_cost'),
                DB::raw('AVG(d.cost_per_liter_ugx) as avg_cost_per_liter'),
                DB::raw('COUNT(DISTINCT d.tank_id) as tanks_served')
            ])->first();

            // Get available tanks - REAL FIELDS ONLY
            $available_tanks_query = DB::table('tanks as t')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select('t.id', 't.tank_number', 't.fuel_type', 's.name as station_name');

            if (auth()->user()->role !== 'admin') {
                $available_tanks_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $available_tanks_query->where('s.id', $station_id);
            }

            $available_tanks = $available_tanks_query->orderBy('s.name')
                ->orderBy('t.tank_number')
                ->get();

            return view('deliveries.index', compact(
                'deliveries',
                'stats',
                'accessible_stations',
                'available_tanks',
                'search',
                'tank_id',
                'station_id',
                'date_from',
                'date_to'
            ));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show delivery creation wizard
     * 🔒 STATION-SCOPED ACCESS CONTROL - REAL FIELDS ONLY
     */
    public function create(Request $request)
    {
        try {
            // Get user's accessible stations
            $accessible_stations = $this->getUserAccessibleStations();

            $station_id = $request->get('station_id');

            if (!$station_id) {
                return view('deliveries.create', compact('accessible_stations'));
            }

            // Validate station access
            if (!$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            $selected_station = $accessible_stations->firstWhere('id', $station_id);

            // Get available tanks - REAL SCHEMA FIELDS ONLY
            $available_tanks = DB::table('tanks')
                ->select([
                    'id',
                    'tank_number',
                    'fuel_type',
                    'capacity_liters',
                    'current_volume_liters'
                ])
                ->where('station_id', $station_id)
                ->orderBy('tank_number')
                ->get();

            // Get recent deliveries - REAL FIELDS ONLY
            $recent_deliveries = DB::table('deliveries as d')
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->select([
                    'd.supplier_name',
                    'd.cost_per_liter_ugx',
                    't.fuel_type',
                    'd.delivery_date'
                ])
                ->where('t.station_id', $station_id)
                ->where('d.delivery_date', '>=', now()->subDays(30))
                ->orderBy('d.delivery_date', 'desc')
                ->limit(10)
                ->get();

            // Get unique suppliers
            $suppliers = DB::table('deliveries as d')
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->where('t.station_id', $station_id)
                ->whereNotNull('d.supplier_name')
                ->distinct()
                ->pluck('d.supplier_name');

            return view('deliveries.create', compact(
                'accessible_stations',
                'selected_station',
                'available_tanks',
                'recent_deliveries',
                'suppliers'
            ));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Store delivery with FIFO automation compliance
     * 🛡️ RESPECTS tr_delivery_create_fifo_layer TRIGGER
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

            // VALIDATION - EXACT SCHEMA CONSTRAINTS
            $validator = Validator::make($request->all(), [
                'station_id' => [
                    'required',
                    'exists:stations,id'
                ],
                'tank_id' => [
                    'required',
                    'exists:tanks,id'
                ],
                'delivery_reference' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:deliveries,delivery_reference'
                ],
                'volume_liters' => [
                    'required',
                    'numeric',
                    'min:0.001',
                    'max:999999999.999',
                    'regex:/^\d+(\.\d{1,3})?$/'
                ],
                'cost_per_liter_ugx' => [
                    'required',
                    'numeric',
                    'min:0.0001',
                    'max:99999.9999',
                    'regex:/^\d+(\.\d{1,4})?$/'
                ],
                'delivery_date' => [
                    'required',
                    'date',
                    'before_or_equal:today'
                ],
                'delivery_time' => [
                    'required',
                    'date_format:H:i'
                ],
                'supplier_name' => [
                    'nullable',
                    'string',
                    'max:255'
                ],
                'invoice_number' => [
                    'nullable',
                    'string',
                    'max:100'
                ]
            ]);
            if ($validator->fails()) {
                // Handle AJAX requests
                if (request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Handle traditional form requests
                return back()->withErrors($validator)->withInput();
            }
            // BUSINESS RULE VALIDATIONS - REAL FIELDS ONLY
            $validator->after(function ($validator) use ($request) {
                $tank_id = $request->tank_id;
                $volume = $request->volume_liters;

                // Validate tank belongs to station - REAL FK CHECK
                $tank = DB::table('tanks')->where('id', $tank_id)->first();
                if (!$tank || $tank->station_id != $request->station_id) {
                    $validator->errors()->add('tank_id', 'Tank does not belong to selected station');
                    return;
                }

                // CRITICAL: Capacity validation (prevents trigger failure)
                $projected_volume = $tank->current_volume_liters + $volume;
                if ($projected_volume > $tank->capacity_liters) {
                    $available = $tank->capacity_liters - $tank->current_volume_liters;
                    $validator->errors()->add(
                        'volume_liters',
                        'Delivery would exceed tank capacity. Available: ' . number_format($available, 3) . 'L'
                    );
                }
            });

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Calculate total with EXACT precision
            $total_cost = round($request->volume_liters * $request->cost_per_liter_ugx, 4);

            // Insert delivery - EXACT SCHEMA FIELDS ONLY
            $delivery_id = DB::table('deliveries')->insertGetId([
                'tank_id' => $request->tank_id,
                'user_id' => auth()->id(),
                'delivery_reference' => strtoupper(trim($request->delivery_reference)),
                'volume_liters' => round($request->volume_liters, 3),
                'cost_per_liter_ugx' => round($request->cost_per_liter_ugx, 4),
                'total_cost_ugx' => $total_cost,
                'delivery_date' => $request->delivery_date,
                'delivery_time' => $request->delivery_time,
                'supplier_name' => $request->supplier_name ? trim($request->supplier_name) : null,
                'invoice_number' => $request->invoice_number ? trim($request->invoice_number) : null,
                'created_at' => now()
            ]);

            // Verify trigger executed - CHECK REAL FIFO_LAYERS TABLE
            $fifo_created = DB::table('fifo_layers')
                ->where('delivery_id', $delivery_id)
                ->exists();

            if (!$fifo_created) {
                DB::rollBack();
                return back()->with('error', 'Delivery trigger failed to create FIFO layer')->withInput();
            }

            // Audit logging - REAL AUDIT_LOG SCHEMA
            $this->logAuditAction('deliveries', $delivery_id, 'INSERT', null, [
                'tank_id' => $request->tank_id,
                'volume_liters' => round($request->volume_liters, 3),
                'total_cost_ugx' => $total_cost
            ]);

            DB::commit();

            // Handle AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Delivery recorded successfully. FIFO automation completed.',
                    'redirect' => route('deliveries.show', $delivery_id)
                ]);
            }

            // Handle traditional form requests
            return redirect()->route('deliveries.show', $delivery_id)
                ->with('success', 'Delivery recorded successfully. FIFO automation completed.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Handle AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            // Handle traditional form requests
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Show delivery details
     * 🔒 STATION-SCOPED ACCESS CONTROL - REAL FIELDS ONLY
     */
    public function show($delivery_id)
    {
        try {
            // Validate delivery access
            $accessible_stations = $this->getUserAccessibleStations();

            // ONLY REAL SCHEMA FIELDS
            $delivery_query = DB::table('deliveries as d')
                ->select([
                    'd.id',
                    'd.tank_id',
                    'd.delivery_reference',
                    'd.volume_liters',
                    'd.cost_per_liter_ugx',
                    'd.total_cost_ugx',
                    'd.delivery_date',
                    'd.delivery_time',
                    'd.supplier_name',
                    'd.invoice_number',
                    'd.created_at',
                    't.tank_number',
                    't.fuel_type',
                    't.capacity_liters',
                    't.current_volume_liters',
                    's.id as station_id',
                    's.name as station_name',
                    's.location as station_location',
                    's.currency_code',
                    'u.first_name',
                    'u.last_name'
                ])
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->where('d.id', $delivery_id);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $delivery_query->where('s.id', auth()->user()->station_id);
            }

            $delivery = $delivery_query->first();

            if (!$delivery) {
                return back()->with('error', 'Delivery not found or access denied');
            }

            // Validate station access
            if (!$accessible_stations->contains('id', $delivery->station_id)) {
                return back()->with('error', 'Access denied to delivery station');
            }

            // Get FIFO layer created by trigger - REAL FIFO_LAYERS FIELDS
            $fifo_layer = DB::table('fifo_layers')
                ->select([
                    'id',
                    'layer_sequence',
                    'original_volume_liters',
                    'remaining_volume_liters',
                    'cost_per_liter_ugx',
                    'delivery_date',
                    'is_exhausted'
                ])
                ->where('delivery_id', $delivery_id)
                ->first();

            return view('deliveries.show', compact('delivery', 'fifo_layer'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get tank capacity for AJAX - REAL FIELDS ONLY
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function getTankCapacity($tank_id)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();

            // ONLY REAL TANK SCHEMA FIELDS
            $tank_query = DB::table('tanks as t')
                ->select([
                    't.id',
                    't.tank_number',
                    't.fuel_type',
                    't.capacity_liters',
                    't.current_volume_liters',
                    's.name as station_name'
                ])
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('t.id', $tank_id);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $tank_query->where('s.id', auth()->user()->station_id);
            }

            $tank = $tank_query->first();

            if (!$tank) {
                return response()->json(['error' => 'Tank not found or access denied'], 404);
            }

            // Validate station access
            $tank_station_id = DB::table('tanks')->where('id', $tank_id)->value('station_id');
            if (!$accessible_stations->contains('id', $tank_station_id)) {
                return response()->json(['error' => 'Access denied to tank station'], 403);
            }

            return response()->json($tank);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user's accessible stations - REAL SCHEMA ONLY
     * 🔒 CORE ACCESS CONTROL METHOD
     */
    private function getUserAccessibleStations()
    {
        $user = auth()->user();

        if (!$user) {
            return collect([]);
        }

        // Admin gets all stations - REAL STATIONS FIELDS
        if ($user->role === 'admin') {
            return DB::table('stations')
                ->select('id', 'name', 'location', 'currency_code')
                ->orderBy('name')
                ->get();
        }

        // Non-admin gets assigned station - REAL USER.STATION_ID FK
        return DB::table('stations')
            ->select('id', 'name', 'location', 'currency_code')
            ->where('id', $user->station_id)
            ->get();
    }

    /**
     * Log audit action - REAL AUDIT_LOG SCHEMA ONLY
     * 🔒 TAMPER-EVIDENT AUDIT TRAIL
     */
    private function logAuditAction($table_name, $record_id, $action, $old_values, $new_values)
    {
        try {
            // EXACT AUDIT_LOG SCHEMA FIELDS
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
