<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\FuelERP_CriticalPrecisionService;
use Exception;

class DeliveriesController extends Controller
{
    protected $fuelService;

    public function __construct(FuelERP_CriticalPrecisionService $fuelService)
    {
        $this->fuelService = $fuelService;
    }

    /**
     * Display deliveries dashboard with overflow integration
     */
    public function index(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();
            $search = $request->get('search');
            $tank_id = $request->get('tank_id');
            $station_id = $request->get('station_id');
            $date_from = $request->get('date_from');
            $date_to = $request->get('date_to');
            $show_overflow = $request->get('show_overflow', false);

            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            $deliveries_query = DB::table('deliveries as d')
                ->select([
                    'd.id', 'd.tank_id', 'd.delivery_reference', 'd.volume_liters',
                    'd.cost_per_liter_ugx', 'd.total_cost_ugx', 'd.delivery_date',
                    'd.delivery_time', 'd.supplier_name', 'd.invoice_number', 'd.created_at',
                    't.tank_number', 't.fuel_type', 's.name as station_name',
                    's.currency_code', 'u.first_name', 'u.last_name',
                    DB::raw('NULL as overflow_volume'), DB::raw('NULL as storage_reason'),
                    DB::raw('"delivery" as record_type')
                ])
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->join('users as u', 'd.user_id', '=', 'u.id');

            $overflow_query = null;
            if ($show_overflow) {
                $overflow_query = DB::table('delivery_overflow_storage as dos')
                    ->select([
                        'dos.id', 'dos.tank_id', 'dos.delivery_reference',
                        'dos.remaining_volume_liters as volume_liters', 'dos.cost_per_liter_ugx',
                        'dos.remaining_value_ugx as total_cost_ugx', 'dos.overflow_date as delivery_date',
                        'dos.overflow_time as delivery_time', 'dos.supplier_name',
                        DB::raw('NULL as invoice_number'), 'dos.created_at',
                        't.tank_number', 't.fuel_type', 's.name as station_name',
                        's.currency_code', 'u.first_name', 'u.last_name',
                        'dos.overflow_volume_liters as overflow_volume', 'dos.storage_reason',
                        DB::raw('"overflow" as record_type')
                    ])
                    ->join('tanks as t', 'dos.tank_id', '=', 't.id')
                    ->join('stations as s', 't.station_id', '=', 's.id')
                    ->join('users as u', 'dos.created_by_user_id', '=', 'u.id')
                    ->where('dos.is_exhausted', false)
                    ->where('dos.remaining_volume_liters', '>', 0);
            }

            if (auth()->user()->role !== 'admin') {
                $deliveries_query->where('s.id', auth()->user()->station_id);
                if ($overflow_query) {
                    $overflow_query->where('s.id', auth()->user()->station_id);
                }
            }

            $this->applyFiltersToQuery($deliveries_query, $search, $tank_id, $station_id, $date_from, $date_to);
            if ($overflow_query) {
                $this->applyFiltersToOverflowQuery($overflow_query, $search, $tank_id, $station_id, $date_from, $date_to);
            }

            if ($show_overflow && $overflow_query) {
                $combined_query = $deliveries_query->unionAll($overflow_query);
                $records = DB::table(DB::raw("({$combined_query->toSql()}) as combined_records"))
                    ->mergeBindings($combined_query)
                    ->orderBy('delivery_date', 'desc')
                    ->orderBy('delivery_time', 'desc')
                    ->paginate(15)
                    ->withQueryString();
            } else {
                $records = $deliveries_query->orderBy('d.delivery_date', 'desc')
                    ->orderBy('d.delivery_time', 'desc')
                    ->paginate(15)
                    ->withQueryString();
            }

            $stats = $this->getEssentialStatistics($station_id, $date_from, $date_to);
            $available_tanks = $this->getAvailableTanks($station_id);
            $critical_overflow = $this->getCriticalOverflowData($station_id);

            return view('deliveries.index', compact(
                'records', 'stats', 'accessible_stations', 'available_tanks',
                'critical_overflow', 'search', 'tank_id', 'station_id',
                'date_from', 'date_to', 'show_overflow'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Error loading deliveries: ' . $e->getMessage());
        }
    }

    /**
     * Show delivery creation wizard
     */
    public function create(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();
            $station_id = $request->get('station_id');

            if (!$station_id) {
                return view('deliveries.create', compact('accessible_stations'));
            }

            if (!$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            $selected_station = $accessible_stations->firstWhere('id', $station_id);

            $available_tanks = DB::table('tanks as t')
                ->leftJoin('delivery_overflow_storage as dos', function($join) {
                    $join->on('t.id', '=', 'dos.tank_id')
                         ->where('dos.is_exhausted', false)
                         ->where('dos.remaining_volume_liters', '>', 0);
                })
                ->select([
                    't.id', 't.tank_number', 't.fuel_type', 't.capacity_liters', 't.current_volume_liters',
                    DB::raw('(t.capacity_liters - t.current_volume_liters) as available_space'),
                    DB::raw('COALESCE(SUM(dos.remaining_volume_liters), 0) as overflow_volume'),
                    DB::raw('COUNT(dos.id) as overflow_count')
                ])
                ->where('t.station_id', $station_id)
                ->groupBy('t.id', 't.tank_number', 't.fuel_type', 't.capacity_liters', 't.current_volume_liters')
                ->orderBy('t.tank_number')
                ->get();

            $recent_deliveries = DB::table('deliveries as d')
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->select(['d.supplier_name', 'd.cost_per_liter_ugx', 'd.volume_liters', 't.fuel_type', 'd.delivery_date'])
                ->where('t.station_id', $station_id)
                ->where('d.delivery_date', '>=', now()->subDays(30))
                ->orderBy('d.delivery_date', 'desc')
                ->limit(10)
                ->get();

            $suppliers = DB::table('deliveries as d')
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->where('t.station_id', $station_id)
                ->whereNotNull('d.supplier_name')
                ->distinct()
                ->pluck('d.supplier_name');

            return view('deliveries.create', compact(
                'accessible_stations', 'selected_station', 'available_tanks',
                'recent_deliveries', 'suppliers'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Error loading delivery creation form: ' . $e->getMessage());
        }
    }

    /**
     * Pre-validate delivery and check for RTT requirements
     */
    public function preValidateDelivery(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tank_id' => 'required|exists:tanks,id',
                'volume_liters' => 'required|numeric|min:0.001',
                'fuel_type' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $tank_id = $request->tank_id;
            $delivery_volume = (float) $request->volume_liters;
            $delivery_fuel_type = $request->fuel_type;

            $tank = DB::table('tanks')->where('id', $tank_id)->first();
            if (!$tank) {
                return response()->json(['error' => 'Tank not found'], 404);
            }

            if ($delivery_fuel_type && $delivery_fuel_type !== $tank->fuel_type) {
                return response()->json([
                    'success' => false, 'can_proceed' => false,
                    'message' => 'Fuel type mismatch. Tank accepts ' . $tank->fuel_type . ' but delivery is ' . $delivery_fuel_type
                ], 422);
            }

            $available_space = $tank->capacity_liters - $tank->current_volume_liters;

            $overflow_reserves = DB::table('delivery_overflow_storage')
                ->where('tank_id', $tank_id)
                ->where('fuel_type', $tank->fuel_type)
                ->where('is_exhausted', false)
                ->where('remaining_volume_liters', '>', 0)
                ->where('manual_hold', false)
                ->where('quality_approved', true)
                ->orderByRaw("FIELD(priority_level, 'CRITICAL', 'HIGH', 'NORMAL', 'LOW')")
                ->orderBy('overflow_date')
                ->get();

            $total_overflow_volume = $overflow_reserves->sum('remaining_volume_liters');
            $has_overflow_reserves = $total_overflow_volume > 0;

            $rtt_required = false;
            $rtt_message = '';
            $suggested_rtt_volume = 0;
            $can_proceed_without_rtt = true;

            if ($has_overflow_reserves) {
                if ($available_space > 0) {
                    $returnable_volume = min($available_space, $total_overflow_volume);
                    $suggested_rtt_volume = $returnable_volume;

                    if ($delivery_volume <= $available_space) {
                        $rtt_required = true;
                        $can_proceed_without_rtt = true;
                        $rtt_message = sprintf(
                            'Tank has %.3fL overflow reserves for %s fuel. Available space: %.3fL. Recommend RTT of %.3fL before delivery to optimize storage.',
                            $total_overflow_volume, $tank->fuel_type, $available_space, $returnable_volume
                        );
                    } else {
                        $space_needed = $delivery_volume - $available_space;
                        $rtt_required = true;
                        $can_proceed_without_rtt = false;
                        $rtt_message = sprintf(
                            'Delivery requires %.3fL but only %.3fL available. %.3fL overflow reserves exist. Must RTT %.3fL first, or delivery will create %.3fL new overflow.',
                            $delivery_volume, $available_space, $total_overflow_volume, min($returnable_volume, $space_needed), $space_needed
                        );
                    }
                } else {
                    $rtt_required = true;
                    $can_proceed_without_rtt = false;
                    $rtt_message = sprintf(
                        'Tank is full (%.3fL/%.3fL). %.3fL overflow reserves exist. Cannot accept delivery until overflow is processed.',
                        $tank->current_volume_liters, $tank->capacity_liters, $total_overflow_volume
                    );
                }
            } else {
                if ($delivery_volume > $available_space) {
                    $overflow_amount = $delivery_volume - $available_space;
                    $rtt_message = sprintf(
                        'Delivery of %.3fL exceeds available space (%.3fL). %.3fL will be stored as overflow.',
                        $delivery_volume, $available_space, $overflow_amount
                    );
                }
            }

            $rtt_options = [];
            if ($has_overflow_reserves) {
                foreach ($overflow_reserves as $reserve) {
                    $max_returnable = min($reserve->remaining_volume_liters, $available_space);
                    if ($max_returnable > 0) {
                        $rtt_options[] = [
                            'overflow_id' => $reserve->id,
                            'delivery_reference' => $reserve->delivery_reference,
                            'available_volume' => $reserve->remaining_volume_liters,
                            'max_returnable' => $max_returnable,
                            'cost_per_liter' => $reserve->cost_per_liter_ugx,
                            'overflow_date' => $reserve->overflow_date,
                            'priority_level' => $reserve->priority_level
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'can_proceed' => $can_proceed_without_rtt,
                'rtt_required' => $rtt_required,
                'rtt_message' => $rtt_message,
                'tank_info' => [
                    'tank_number' => $tank->tank_number,
                    'fuel_type' => $tank->fuel_type,
                    'capacity_liters' => $tank->capacity_liters,
                    'current_volume_liters' => $tank->current_volume_liters,
                    'available_space_liters' => $available_space
                ],
                'overflow_info' => [
                    'has_reserves' => $has_overflow_reserves,
                    'total_overflow_volume' => $total_overflow_volume,
                    'overflow_count' => $overflow_reserves->count(),
                    'suggested_rtt_volume' => $suggested_rtt_volume
                ],
                'rtt_options' => $rtt_options,
                'delivery_impact' => [
                    'will_overflow' => $delivery_volume > $available_space,
                    'overflow_amount' => max(0, $delivery_volume - $available_space),
                    'fits_completely' => $delivery_volume <= $available_space
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Pre-validation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store delivery with manual overflow handling
     */
    public function store(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();
            $station_id = $request->input('station_id');

            if (!$station_id || !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station')->withInput();
            }

            $validator = $this->buildDeliveryValidator($request);
            if ($validator->fails()) {
                if (request()->ajax()) {
                    return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            $businessValidation = $this->performBusinessValidation($request);
            if (!$businessValidation['valid']) {
                if (request()->ajax()) {
                    return response()->json(['success' => false, 'message' => $businessValidation['message']], 422);
                }
                return back()->with('error', $businessValidation['message'])->withInput();
            }

            $rtt_check = $this->checkRTTRequirements($request);

            if ($rtt_check['blocks_delivery']) {
                $error_message = $rtt_check['message'] . ' Please process RTT operations first.';

                if (request()->ajax()) {
                    return response()->json([
                        'success' => false, 'message' => $error_message,
                        'rtt_required' => true, 'rtt_data' => $rtt_check
                    ], 422);
                }
                return back()->with('error', $error_message)->withInput();
            }

            $delivery_result = $this->processDeliveryWithManualOverflow($request);
            $response_message = $delivery_result['message'];

            if ($rtt_check['recommends_rtt'] && !$rtt_check['blocks_delivery']) {
                $response_message .= ' Note: ' . $rtt_check['message'];
            }

            if (request()->ajax()) {
                return response()->json([
                    'success' => true, 'message' => $response_message,
                    'delivery_id' => $delivery_result['primary_delivery_id'],
                    'has_overflow' => $delivery_result['has_overflow'],
                    'overflow_volume' => $delivery_result['overflow_volume'] ?? 0,
                    'rtt_recommended' => $rtt_check['recommends_rtt'],
                    'redirect' => route('deliveries.show', $delivery_result['primary_delivery_id'])
                ]);
            }

            $flash_type = $rtt_check['recommends_rtt'] ? 'warning' : 'success';
            return redirect()->route('deliveries.show', $delivery_result['primary_delivery_id'])
                ->with($flash_type, $response_message);

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Delivery processing failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Delivery processing failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show delivery details
     */
    public function show($delivery_id)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();

            $delivery_query = DB::table('deliveries as d')
                ->select([
                    'd.id', 'd.tank_id', 'd.delivery_reference', 'd.volume_liters',
                    'd.cost_per_liter_ugx', 'd.total_cost_ugx', 'd.delivery_date',
                    'd.delivery_time', 'd.supplier_name', 'd.invoice_number', 'd.created_at',
                    't.tank_number', 't.fuel_type', 't.capacity_liters', 't.current_volume_liters',
                    's.id as station_id', 's.name as station_name', 's.location as station_location',
                    's.currency_code', 'u.first_name', 'u.last_name', 'u.employee_id'
                ])
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->where('d.id', $delivery_id);

            if (auth()->user()->role !== 'admin') {
                $delivery_query->where('s.id', auth()->user()->station_id);
            }

            $delivery = $delivery_query->first();

            if (!$delivery || !$accessible_stations->contains('id', $delivery->station_id)) {
                return back()->with('error', 'Delivery not found or access denied');
            }

            $fifo_layer = DB::table('fifo_layers')
                ->select([
                    'id', 'layer_sequence', 'original_volume_liters', 'remaining_volume_liters',
                    'cost_per_liter_ugx', 'delivery_date', 'is_exhausted', 'original_value_ugx',
                    'remaining_value_ugx', 'consumed_value_ugx', 'layer_status', 'created_at', 'updated_at'
                ])
                ->where('delivery_id', $delivery_id)
                ->first();

            $overflow_records = DB::table('delivery_overflow_storage')
                ->select([
                    'id', 'overflow_volume_liters','delivery_reference', 'remaining_volume_liters', 'cost_per_liter_ugx',
                    'total_overflow_value_ugx', 'remaining_value_ugx', 'storage_reason', 'priority_level',
                    'manual_hold', 'quality_approved', 'overflow_date', 'overflow_time', 'is_exhausted'
                ])
                ->where('original_delivery_id', $delivery_id)
                ->orderBy('overflow_date')
                ->get();

            $rtt_deliveries = DB::table('deliveries as d')
                ->select(['d.id', 'd.delivery_reference', 'd.volume_liters', 'd.delivery_date', 'd.delivery_time', 'd.supplier_name'])
                ->where('d.supplier_name', 'like', 'RTT-%')
                ->where('d.invoice_number', 'like', '%' . DB::table('deliveries')->where('id', $delivery_id)->value('delivery_reference') . '%')
                ->orderBy('d.delivery_date', 'desc')
                ->get();

            $current_tank_status = DB::table('tanks')
                ->select([
                    'capacity_liters', 'current_volume_liters',
                    DB::raw('(capacity_liters - current_volume_liters) as available_space'),
                    DB::raw('ROUND((current_volume_liters / capacity_liters) * 100, 2) as fill_percentage')
                ])
                ->where('id', $delivery->tank_id)
                ->first();

            $current_overflow_status = DB::table('delivery_overflow_storage')
                ->where('tank_id', $delivery->tank_id)
                ->where('fuel_type', $delivery->fuel_type)
                ->where('is_exhausted', false)
                ->where('remaining_volume_liters', '>', 0)
                ->select([
                    DB::raw('SUM(remaining_volume_liters) as total_overflow_volume'),
                    DB::raw('COUNT(*) as overflow_count'),
                    DB::raw('MIN(overflow_date) as oldest_overflow_date')
                ])
                ->first();

            return view('deliveries.show', compact(
                'delivery', 'fifo_layer', 'overflow_records', 'rtt_deliveries',
                'current_tank_status', 'current_overflow_status'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Error loading delivery details: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form for delivery
     */
    public function edit($delivery_id)
    {
        try {
            $delivery = $this->getAccessibleDelivery($delivery_id);
            if (!$delivery) {
                return back()->with('error', 'Delivery not found or access denied');
            }

            $has_overflow = DB::table('delivery_overflow_storage')
                ->where('original_delivery_id', $delivery_id)
                ->where('is_exhausted', false)
                ->exists();

            if ($has_overflow) {
                return back()->with('error', 'Cannot edit delivery with active overflow storage. Process RTT operations first.');
            }

            $available_tanks = DB::table('tanks')
                ->select(['id', 'tank_number', 'fuel_type', 'capacity_liters', 'current_volume_liters'])
                ->where('station_id', $delivery->station_id)
                ->orderBy('tank_number')
                ->get();

            $suppliers = DB::table('deliveries as d')
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->where('t.station_id', $delivery->station_id)
                ->whereNotNull('d.supplier_name')
                ->distinct()
                ->pluck('d.supplier_name');

            return view('deliveries.edit', compact('delivery', 'available_tanks', 'suppliers'));

        } catch (\Exception $e) {
            return back()->with('error', 'Error loading delivery edit form: ' . $e->getMessage());
        }
    }

    /**
     * Update delivery
     */
    public function update(Request $request, $delivery_id)
    {
        try {
            $delivery = $this->getAccessibleDelivery($delivery_id);
            if (!$delivery) {
                return back()->with('error', 'Delivery not found or access denied');
            }

            $has_overflow = DB::table('delivery_overflow_storage')
                ->where('original_delivery_id', $delivery_id)
                ->where('is_exhausted', false)
                ->exists();

            if ($has_overflow) {
                return back()->with('error', 'Cannot update delivery with active overflow storage.');
            }

            $validator = Validator::make($request->all(), [
                'supplier_name' => 'nullable|string|max:255',
                'invoice_number' => 'nullable|string|max:100',
                'delivery_date' => 'required|date|before_or_equal:today',
                'delivery_time' => 'required|date_format:H:i'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            DB::table('deliveries')
                ->where('id', $delivery_id)
                ->update([
                    'supplier_name' => $request->supplier_name ? trim($request->supplier_name) : null,
                    'invoice_number' => $request->invoice_number ? trim($request->invoice_number) : null,
                    'delivery_date' => $request->delivery_date,
                    'delivery_time' => $request->delivery_time
                ]);

            return redirect()->route('deliveries.show', $delivery_id)
                ->with('success', 'Delivery updated successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Delivery update failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete delivery
     */
    public function destroy($delivery_id)
    {
        try {
            $delivery = $this->getAccessibleDelivery($delivery_id);
            if (!$delivery) {
                return back()->with('error', 'Delivery not found or access denied');
            }

            $has_overflow = DB::table('delivery_overflow_storage')
                ->where('original_delivery_id', $delivery_id)
                ->exists();

            if ($has_overflow) {
                return back()->with('error', 'Cannot delete delivery with overflow records. Process all RTT operations first.');
            }

            $has_fifo_consumption = DB::table('fifo_consumption_log as fcl')
                ->join('fifo_layers as fl', 'fcl.fifo_layer_id', '=', 'fl.id')
                ->where('fl.delivery_id', $delivery_id)
                ->exists();

            if ($has_fifo_consumption) {
                return back()->with('error', 'Cannot delete delivery that has been consumed in reconciliations.');
            }

            DB::beginTransaction();

            DB::table('fifo_layers')->where('delivery_id', $delivery_id)->delete();

            $delivery_detail = DB::table('deliveries')->where('id', $delivery_id)->first();
            DB::table('tanks')
                ->where('id', $delivery_detail->tank_id)
                ->decrement('current_volume_liters', $delivery_detail->volume_liters);

            DB::table('deliveries')->where('id', $delivery_id)->delete();

            DB::commit();

            return redirect()->route('deliveries.index')
                ->with('success', 'Delivery deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Delivery deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Get tank capacity for AJAX
     */
    public function getTankCapacity($tank_id)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();

            $tank_query = DB::table('tanks as t')
                ->leftJoin('delivery_overflow_storage as dos', function($join) {
                    $join->on('t.id', '=', 'dos.tank_id')
                         ->where('dos.is_exhausted', false)
                         ->where('dos.remaining_volume_liters', '>', 0);
                })
                ->select([
                    't.id', 't.tank_number', 't.fuel_type', 't.capacity_liters', 't.current_volume_liters',
                    's.id as station_id', 's.name as station_name',
                    DB::raw('(t.capacity_liters - t.current_volume_liters) as available_space_liters'),
                    DB::raw('COALESCE(SUM(dos.remaining_volume_liters), 0) as available_overflow_liters'),
                    DB::raw('COUNT(dos.id) as overflow_count')
                ])
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('t.id', $tank_id)
                ->groupBy('t.id', 't.tank_number', 't.fuel_type', 't.capacity_liters', 't.current_volume_liters', 's.id', 's.name');

            if (auth()->user()->role !== 'admin') {
                $tank_query->where('s.id', auth()->user()->station_id);
            }

            $tank = $tank_query->first();

            if (!$tank || !$accessible_stations->contains('id', $tank->station_id)) {
                return response()->json(['error' => 'Tank not found or access denied'], 404);
            }

            return response()->json($tank);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Return-to-Tank (RTT) operation
     */
    public function returnToTank(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'overflow_id' => 'required|exists:delivery_overflow_storage,id',
                'return_volume_liters' => ['required', 'numeric', 'min:0.001', 'regex:/^\d+(\.\d{1,3})?$/']
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $overflow_id = $request->overflow_id;
            $return_volume = (float) $request->return_volume_liters;

            $overflow = $this->getAccessibleOverflowRecord($overflow_id);
            if (!$overflow) {
                return response()->json(['error' => 'Overflow record not found or access denied'], 404);
            }

            $validation = $this->validateRTTOperation($overflow, $return_volume);
            if (!$validation['valid']) {
                return response()->json(['error' => $validation['message']], 422);
            }

            $rtt_result = $this->processReturnToTankOperation($overflow, $return_volume);

            return response()->json([
                'success' => true,
                'message' => $rtt_result['message'],
                'delivery_id' => $rtt_result['delivery_id'],
                'remaining_overflow' => $rtt_result['remaining_overflow'],
                'tank_status' => $rtt_result['tank_status']
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'RTT operation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Overflow management dashboard
     */
    public function overflowDashboard(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();
            $station_id = $request->get('station_id');

            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            $overflow_query = DB::table('delivery_overflow_storage as dos')
                ->select([
                    'dos.id', 'dos.tank_id', 'dos.overflow_volume_liters', 'dos.remaining_volume_liters',
                    'dos.cost_per_liter_ugx', 'dos.remaining_value_ugx', 'dos.supplier_name',
                    'dos.delivery_reference', 'dos.overflow_date', 'dos.overflow_time',
                    'dos.storage_reason', 'dos.priority_level', 'dos.manual_hold',
                    'dos.quality_approved', 'dos.is_exhausted', 't.tank_number',
                    't.fuel_type', 't.capacity_liters', 't.current_volume_liters',
                    's.name as station_name',
                    DB::raw('(t.capacity_liters - t.current_volume_liters) as available_space'),
                    DB::raw('CASE
                        WHEN dos.manual_hold = 1 THEN "MANUAL_HOLD"
                        WHEN dos.quality_approved = 0 THEN "QUALITY_PENDING"
                        WHEN (t.capacity_liters - t.current_volume_liters) <= 0 THEN "NO_SPACE"
                        WHEN (t.capacity_liters - t.current_volume_liters) >= dos.remaining_volume_liters THEN "FULL_RTT_ELIGIBLE"
                        WHEN (t.capacity_liters - t.current_volume_liters) > 0 THEN "PARTIAL_RTT_ELIGIBLE"
                        ELSE "NOT_ELIGIBLE"
                    END as rtt_eligibility'),
                    DB::raw('LEAST(dos.remaining_volume_liters, (t.capacity_liters - t.current_volume_liters)) as max_rtt_volume')
                ])
                ->join('tanks as t', 'dos.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('dos.is_exhausted', false)
                ->where('dos.remaining_volume_liters', '>', 0);

            if (auth()->user()->role !== 'admin') {
                $overflow_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $overflow_query->where('s.id', $station_id);
            }

            $overflow_records = $overflow_query->orderBy('dos.priority_level', 'desc')
                ->orderBy('dos.overflow_date')
                ->paginate(20)
                ->withQueryString();

            return view('deliveries.overflow_dashboard', compact(
                'overflow_records', 'accessible_stations', 'station_id'
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Error loading overflow dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Get supported fuel types from service
     */
    public function getSupportedFuelTypes(Request $request)
    {
        try {
            $table = $request->get('table', 'tanks');
            $fuelTypes = $this->fuelService->getSupportedFuelTypes($table);

            return response()->json(['success' => true, 'fuel_types' => $fuelTypes]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get tanks by station with fuel type filtering
     */
    public function getTanksByStation(Request $request)
    {
        try {
            $station_id = $request->get('station_id');
            $fuel_type = $request->get('fuel_type');

            if (!$station_id) {
                return response()->json(['error' => 'Station ID required'], 400);
            }

            $accessible_stations = $this->getUserAccessibleStations();
            if (!$accessible_stations->contains('id', $station_id)) {
                return response()->json(['error' => 'Access denied to station'], 403);
            }

            $tanks = $this->fuelService->getTanksByStation($station_id, $fuel_type);

            return response()->json(['success' => true, 'tanks' => $tanks]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * PRIVATE HELPER METHODS
     */

    private function checkRTTRequirements(Request $request): array
    {
        $tank_id = $request->tank_id;
        $delivery_volume = (float) $request->volume_liters;

        $tank = DB::table('tanks')->where('id', $tank_id)->first();
        if (!$tank) {
            throw new Exception("Tank not found: $tank_id");
        }

        $available_space = $tank->capacity_liters - $tank->current_volume_liters;

        $overflow_reserves = DB::table('delivery_overflow_storage')
            ->where('tank_id', $tank_id)
            ->where('fuel_type', $tank->fuel_type)
            ->where('is_exhausted', false)
            ->where('remaining_volume_liters', '>', 0)
            ->where('manual_hold', false)
            ->where('quality_approved', true)
            ->sum('remaining_volume_liters');

        $has_overflow_reserves = $overflow_reserves > 0;
        $blocks_delivery = false;
        $recommends_rtt = false;
        $message = '';

        if ($has_overflow_reserves) {
            if ($available_space <= 0) {
                $blocks_delivery = true;
                $message = sprintf(
                    'Tank is full (%.3fL/%.3fL) with %.3fL overflow reserves. Must process RTT first.',
                    $tank->current_volume_liters, $tank->capacity_liters, $overflow_reserves
                );
            } elseif ($delivery_volume > $available_space) {
                $blocks_delivery = true;
                $space_needed = $delivery_volume - $available_space;
                $message = sprintf(
                    'Delivery requires %.3fL but only %.3fL available. %.3fL overflow reserves exist. Process RTT first to avoid additional overflow.',
                    $delivery_volume, $available_space, $overflow_reserves
                );
            } else {
                $recommends_rtt = true;
                $message = sprintf(
                    'Tank has %.3fL overflow reserves. Consider RTT before delivery to optimize storage.',
                    $overflow_reserves
                );
            }
        }

        return [
            'blocks_delivery' => $blocks_delivery,
            'recommends_rtt' => $recommends_rtt,
            'has_overflow_reserves' => $has_overflow_reserves,
            'overflow_volume' => $overflow_reserves,
            'available_space' => $available_space,
            'message' => $message
        ];
    }

    private function processDeliveryWithManualOverflow(Request $request): array
    {
        DB::beginTransaction();
        try {
            $tank_id = $request->tank_id;
            $delivery_volume = (float) $request->volume_liters;

            $tank = DB::table('tanks')->where('id', $tank_id)->first();
            $available_space = $tank->capacity_liters - $tank->current_volume_liters;

            if ($delivery_volume <= $available_space) {
                $deliveryData = $this->buildDeliveryData($request);
                $delivery_id = $this->fuelService->createDelivery($deliveryData);

                DB::commit();
                return [
                    'primary_delivery_id' => $delivery_id,
                    'has_overflow' => false,
                    'message' => 'Delivery recorded successfully. FIFO automation completed.'
                ];
            }

            $tank_volume = $available_space;
            $overflow_volume = $delivery_volume - $available_space;

            $primaryDeliveryData = $this->buildDeliveryData($request, $tank_volume);
            $primary_delivery_id = $this->fuelService->createDelivery($primaryDeliveryData);

            $this->storeOverflowRecord($primary_delivery_id, $tank, $overflow_volume, $request);

            DB::commit();
            return [
                'primary_delivery_id' => $primary_delivery_id,
                'has_overflow' => true,
                'overflow_volume' => $overflow_volume,
                'message' => sprintf(
                    'Delivery processed: %.3fL to tank, %.3fL stored in overflow. Use RTT operations to return overflow to tank when space available.',
                    $tank_volume, $overflow_volume
                )
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function storeOverflowRecord(int $primaryDeliveryId, $tank, float $overflowVolume, Request $request): void
    {
        $deliveryReference = DB::table('deliveries')->where('id', $primaryDeliveryId)->value('delivery_reference');

        $volumePercentage = ($overflowVolume / $tank->capacity_liters) * 100;
        $priority = 'NORMAL';

        if ($volumePercentage >= 50) {
            $priority = 'CRITICAL';
        } elseif ($volumePercentage >= 25) {
            $priority = 'HIGH';
        } elseif ($volumePercentage >= 10) {
            $priority = 'NORMAL';
        } else {
            $priority = 'LOW';
        }

        $overflowReference = $deliveryReference . '-OVF';

        $overflowData = [
            'station_id' => $tank->station_id,
            'tank_id' => $tank->id,
            'original_delivery_id' => $primaryDeliveryId,
            'fuel_type' => $tank->fuel_type,
            'overflow_volume_liters' => round($overflowVolume, 3),
            'remaining_volume_liters' => round($overflowVolume, 3),
            'cost_per_liter_ugx' => round((float) $request->cost_per_liter_ugx, 4),
            'supplier_name' => $request->supplier_name ? trim($request->supplier_name) : null,
            'delivery_reference' => $overflowReference,
            'overflow_date' => $request->delivery_date,
            'overflow_time' => $request->delivery_time,
            'storage_reason' => 'TANK_CAPACITY_EXCEEDED',
            'priority_level' => $priority,
            'manual_hold' => false,
            'quality_approved' => true,
            'temperature_celsius' => null,
            'batch_number' => null,
            'quality_notes' => sprintf('Overflow from delivery %s - manual RTT required', $deliveryReference),
            'is_exhausted' => false,
            'created_by_user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('delivery_overflow_storage')->insert($overflowData);
    }

    private function processReturnToTankOperation($overflow, float $returnVolume): array
    {
        DB::beginTransaction();
        try {
            $rttDeliveryData = [
                'tank_id' => $overflow->tank_id,
                'user_id' => auth()->id(),
                'volume_liters' => $returnVolume,
                'cost_per_liter_ugx' => $overflow->cost_per_liter_ugx,
                'delivery_date' => now()->format('Y-m-d'),
                'delivery_time' => now()->format('H:i:s'),
                'supplier_name' => 'RTT-' . ($overflow->supplier_name ?? 'OVERFLOW'),
                'invoice_number' => 'RTT-' . $overflow->delivery_reference . '-' . date('His')
            ];

            $rtt_delivery_id = $this->fuelService->createDelivery($rttDeliveryData);

            $new_remaining_volume = $overflow->remaining_volume_liters - $returnVolume;
            $is_exhausted = $new_remaining_volume <= 0.001;

            DB::table('delivery_overflow_storage')
                ->where('id', $overflow->id)
                ->update([
                    'remaining_volume_liters' => max(0, round($new_remaining_volume, 3)),
                    'is_exhausted' => $is_exhausted,
                    'updated_at' => now()
                ]);

            $updated_tank = DB::table('tanks')->where('id', $overflow->tank_id)->first();
            $remaining_overflow = DB::table('delivery_overflow_storage')
                ->where('tank_id', $overflow->tank_id)
                ->where('is_exhausted', false)
                ->sum('remaining_volume_liters');

            $tank_status = [
                'current_volume' => $updated_tank->current_volume_liters,
                'capacity' => $updated_tank->capacity_liters,
                'available_space' => $updated_tank->capacity_liters - $updated_tank->current_volume_liters,
                'fill_percentage' => round(($updated_tank->current_volume_liters / $updated_tank->capacity_liters) * 100, 2),
                'remaining_overflow' => $remaining_overflow
            ];

            DB::commit();
            return [
                'delivery_id' => $rtt_delivery_id,
                'remaining_overflow' => max(0, $new_remaining_volume),
                'tank_status' => $tank_status,
                'message' => sprintf(
                    'RTT completed: %.3fL returned to tank. %.3fL remaining in overflow. Tank: %.1f%% full.',
                    $returnVolume, max(0, $new_remaining_volume), $tank_status['fill_percentage']
                )
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function buildDeliveryData(Request $request, ?float $overrideVolume = null): array
    {
        return [
            'tank_id' => $request->tank_id,
            'user_id' => auth()->id(),
            'volume_liters' => $overrideVolume ?? (float) $request->volume_liters,
            'cost_per_liter_ugx' => (float) $request->cost_per_liter_ugx,
            'delivery_date' => $request->delivery_date,
            'delivery_time' => $request->delivery_time,
            'supplier_name' => $request->supplier_name ? trim($request->supplier_name) : null,
            'invoice_number' => $request->invoice_number ? trim($request->invoice_number) : null
        ];
    }

    private function buildDeliveryValidator(Request $request): \Illuminate\Validation\Validator
    {
        $rules = [
            'station_id' => ['required', 'exists:stations,id'],
            'tank_id' => ['required', 'exists:tanks,id'],
            'volume_liters' => ['required', 'numeric', 'min:0.001', 'max:999999999.999', 'regex:/^\d+(\.\d{1,3})?$/'],
            'cost_per_liter_ugx' => ['required', 'numeric', 'min:0.0001', 'max:99999.9999', 'regex:/^\d+(\.\d{1,4})?$/'],
            'delivery_date' => ['required', 'date', 'before_or_equal:today'],
            'delivery_time' => ['required', 'date_format:H:i'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:100']
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            $tank = DB::table('tanks')->where('id', $request->tank_id)->first();
            if (!$tank || $tank->station_id != $request->station_id) {
                $validator->errors()->add('tank_id', 'Tank does not belong to selected station');
            }
        });

        return $validator;
    }

    private function performBusinessValidation(Request $request): array
    {
        if ($request->invoice_number) {
            $duplicate = DB::table('deliveries')
                ->where('invoice_number', $request->invoice_number)
                ->where('delivery_date', $request->delivery_date)
                ->exists();

            if ($duplicate) {
                return ['valid' => false, 'message' => 'Duplicate invoice number for the same date'];
            }
        }

        return ['valid' => true];
    }

    private function getAccessibleDelivery(int $deliveryId)
    {
        $query = DB::table('deliveries as d')
            ->join('tanks as t', 'd.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->where('d.id', $deliveryId)
            ->select('d.*', 's.id as station_id');

        if (auth()->user()->role !== 'admin') {
            $query->where('s.id', auth()->user()->station_id);
        }

        return $query->first();
    }

    private function getAccessibleOverflowRecord(int $overflowId)
    {
        $query = DB::table('delivery_overflow_storage as dos')
            ->join('tanks as t', 'dos.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->where('dos.id', $overflowId)
            ->select('dos.*', 's.id as station_id');

        if (auth()->user()->role !== 'admin') {
            $query->where('s.id', auth()->user()->station_id);
        }

        return $query->first();
    }

    private function validateRTTOperation($overflow, float $returnVolume): array
    {
        if ($returnVolume > $overflow->remaining_volume_liters) {
            return [
                'valid' => false,
                'message' => sprintf('Return volume %.3fL exceeds available overflow %.3fL', $returnVolume, $overflow->remaining_volume_liters)
            ];
        }

        if ($overflow->manual_hold) {
            return ['valid' => false, 'message' => 'Overflow is on manual hold. Remove hold before RTT.'];
        }

        if (!$overflow->quality_approved) {
            return ['valid' => false, 'message' => 'Overflow quality not approved. Approve quality before RTT.'];
        }

        $tank = DB::table('tanks')->where('id', $overflow->tank_id)->first();
        $available_space = $tank->capacity_liters - $tank->current_volume_liters;

        if ($available_space <= 0) {
            return [
                'valid' => false,
                'message' => sprintf('Tank is full (%.3fL/%.3fL). No space for RTT.', $tank->current_volume_liters, $tank->capacity_liters)
            ];
        }

        if ($returnVolume > $available_space) {
            return [
                'valid' => false,
                'message' => sprintf('Return volume %.3fL exceeds tank available space %.3fL. Maximum RTT: %.3fL', $returnVolume, $available_space, $available_space)
            ];
        }

        return [
            'valid' => true,
            'message' => sprintf('RTT validation passed. %.3fL can be returned. Tank will be %.1f%% full after RTT.', $returnVolume, (($tank->current_volume_liters + $returnVolume) / $tank->capacity_liters) * 100)
        ];
    }

    private function applyFiltersToQuery($query, $search, $tank_id, $station_id, $date_from, $date_to)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('d.delivery_reference', 'like', "%{$search}%")
                    ->orWhere('d.supplier_name', 'like', "%{$search}%")
                    ->orWhere('d.invoice_number', 'like', "%{$search}%")
                    ->orWhere('t.tank_number', 'like', "%{$search}%");
            });
        }

        if ($tank_id) $query->where('d.tank_id', $tank_id);
        if ($station_id) $query->where('s.id', $station_id);
        if ($date_from) $query->where('d.delivery_date', '>=', $date_from);
        if ($date_to) $query->where('d.delivery_date', '<=', $date_to);
    }

    private function applyFiltersToOverflowQuery($query, $search, $tank_id, $station_id, $date_from, $date_to)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('dos.delivery_reference', 'like', "%{$search}%")
                    ->orWhere('dos.supplier_name', 'like', "%{$search}%")
                    ->orWhere('t.tank_number', 'like', "%{$search}%");
            });
        }

        if ($tank_id) $query->where('dos.tank_id', $tank_id);
        if ($station_id) $query->where('s.id', $station_id);
        if ($date_from) $query->where('dos.overflow_date', '>=', $date_from);
        if ($date_to) $query->where('dos.overflow_date', '<=', $date_to);
    }

   private function getEssentialStatistics($station_id, $date_from, $date_to): array
{
    $user = auth()->user();

    $q = DB::table('deliveries as d')
        ->join('tanks as t', 'd.tank_id', '=', 't.id')
        ->join('stations as s', 't.station_id', '=', 's.id');

    // Enforce station ACL for non-admins
    if ($user && $user->role !== 'admin') {
        $q->where('s.id', $user->station_id);
    }

    // Optional filters (consistent with index())
    if ($station_id) {
        $q->where('s.id', $station_id);
    }
    if ($date_from) {
        $q->whereDate('d.delivery_date', '>=', $date_from);
    }
    if ($date_to) {
        $q->whereDate('d.delivery_date', '<=', $date_to);
    }

    // Aggregates with COALESCE to avoid nulls
    $row = $q->selectRaw(
        'COUNT(*) AS total_deliveries,
         COALESCE(SUM(d.volume_liters), 0) AS total_volume,
         COALESCE(SUM(d.total_cost_ugx), 0) AS total_cost,
         COUNT(DISTINCT d.tank_id) AS tanks_served'
    )->first();

    if (!$row) {
        return [
            'total_deliveries' => 0,
            'total_volume'     => 0.0,
            'total_cost'       => 0.0,
            'tanks_served'     => 0,
        ];
    }

    // Return strict array with numeric types
    return [
        'total_deliveries' => (int) $row->total_deliveries,
        'total_volume'     => (float) $row->total_volume,
        'total_cost'       => (float) $row->total_cost,
        'tanks_served'     => (int) $row->tanks_served,
    ];
}


    private function getAvailableTanks($station_id)
    {
        $query = DB::table('tanks as t')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->select('t.id', 't.tank_number', 't.fuel_type', 's.name as station_name');

        if (auth()->user()->role !== 'admin') {
            $query->where('s.id', auth()->user()->station_id);
        }

        if ($station_id) $query->where('s.id', $station_id);

        return $query->orderBy('s.name')->orderBy('t.tank_number')->get();
    }

    private function getCriticalOverflowData($station_id): array
    {
        $query = DB::table('delivery_overflow_storage as dos')
            ->join('tanks as t', 'dos.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->where('dos.is_exhausted', false)
            ->where('dos.remaining_volume_liters', '>', 0);

        if (auth()->user()->role !== 'admin') {
            $query->where('s.id', auth()->user()->station_id);
        }

        if ($station_id) $query->where('s.id', $station_id);

        $stats = $query->select([
            DB::raw('COUNT(*) as total_overflow_records'),
            DB::raw('SUM(dos.remaining_volume_liters) as total_overflow_volume'),
            DB::raw('COUNT(DISTINCT dos.tank_id) as tanks_with_overflow'),
            DB::raw('SUM(CASE WHEN (t.capacity_liters - t.current_volume_liters) >= dos.remaining_volume_liters THEN 1 ELSE 0 END) as rtt_eligible_records')
        ])->first();

        return [
            'total_overflow_records' => (int) ($stats->total_overflow_records ?? 0),
            'total_overflow_volume' => (float) ($stats->total_overflow_volume ?? 0),
            'tanks_with_overflow' => (int) ($stats->tanks_with_overflow ?? 0),
            'rtt_eligible_records' => (int) ($stats->rtt_eligible_records ?? 0)
        ];
    }

    private function getUserAccessibleStations()
    {
        $user = auth()->user();

        if (!$user) {
            return collect([]);
        }

        if ($user->role === 'admin') {
            return DB::table('stations')
                ->select('id', 'name', 'location', 'currency_code')
                ->orderBy('name')
                ->get();
        }

        return DB::table('stations')
            ->select('id', 'name', 'location', 'currency_code')
            ->where('id', $user->station_id)
            ->get();
    }
}
