<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MeterManagementController extends Controller
{
    /**
     * Get user's accessible stations based on role
     * SECURITY: Enforces station-level access control
     */
    private function getUserAccessibleStations()
    {
        $user = auth()->user();

        // Admin sees all stations
        if ($user->role === 'admin') {
            return DB::table('stations')
                ->select('id', 'name', 'location')
                ->orderBy('name')
                ->get();
        }

        // Non-admin users only see their assigned station
        return DB::table('stations')
            ->select('id', 'name', 'location')
            ->where('id', $user->station_id)
            ->orderBy('name')
            ->get();
    }

    /**
     * Validate station access for current user
     * SECURITY: Prevents unauthorized station access
     */
    private function validateStationAccess($station_id)
    {
        $user = auth()->user();

        // Admin can access all stations
        if ($user->role === 'admin') {
            return true;
        }

        // Non-admin must match their assigned station
        return $user->station_id == $station_id;
    }

    /**
     * Get default station for user
     * SECURITY: Returns appropriate default based on role
     */
    private function getUserDefaultStation()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            // Admin gets first station or null for "all"
            return null;
        }

        // Non-admin gets their assigned station
        return $user->station_id;
    }

    /**
     * Display meter management dashboard with STATION-LEVEL SECURITY
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $search = $request->get('search');
            $station_id = $request->get('station_id');
            $tank_id = $request->get('tank_id');
            $status = $request->get('status');

            // SECURITY: Get user's accessible stations
            $accessible_stations = $this->getUserAccessibleStations();

            // SECURITY: If no station selected, use user's default
            if (!$station_id) {
                $station_id = $this->getUserDefaultStation();
            }

            // SECURITY: Validate station access if station specified
            if ($station_id && !$this->validateStationAccess($station_id)) {
                return back()->with('error', 'Access denied: You do not have permission to view this station\'s data');
            }

            // Build meters query with STATION-LEVEL FILTERING
            $query = DB::table('meters as m')
                ->select([
                    'm.id',
                    'm.tank_id',
                    'm.meter_number',
                    'm.current_reading_liters',
                    'm.is_active',
                    'm.created_at',
                    'm.updated_at',
                    't.tank_number',
                    't.fuel_type',
                    't.capacity_liters',
                    't.current_volume_liters',
                    's.id as station_id',
                    's.name as station_name',
                    's.location as station_location',
                    DB::raw('(SELECT COUNT(*) FROM meter_readings WHERE meter_id = m.id) as total_readings'),
                    DB::raw('(SELECT mr.reading_date FROM meter_readings mr WHERE mr.meter_id = m.id ORDER BY mr.reading_date DESC LIMIT 1) as last_reading_date'),
                    DB::raw('(SELECT mr.closing_reading_liters FROM meter_readings mr WHERE mr.meter_id = m.id ORDER BY mr.reading_date DESC LIMIT 1) as last_reading_value'),
                    DB::raw('(SELECT COUNT(*) FROM meter_readings mr WHERE mr.meter_id = m.id AND mr.reading_date = CURDATE()) as today_readings')
                ])
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id');

            // SECURITY: Apply station-level filtering based on user role
            if ($user->role !== 'admin') {
                // Non-admin users restricted to their station
                $query->where('s.id', $user->station_id);
            } elseif ($station_id) {
                // Admin with specific station selected
                $query->where('s.id', $station_id);
            }
            // Admin without station filter sees all stations

            $query->orderBy('s.name')
                  ->orderBy('t.tank_number')
                  ->orderBy('m.meter_number');

            // Apply additional filters
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('m.meter_number', 'like', "%{$search}%")
                      ->orWhere('t.tank_number', 'like', "%{$search}%")
                      ->orWhere('s.name', 'like', "%{$search}%");
                });
            }

            if ($tank_id) {
                $query->where('m.tank_id', $tank_id);
            }

            if ($status !== null) {
                $query->where('m.is_active', $status === 'active' ? 1 : 0);
            }

            $meters = $query->paginate(15)->withQueryString();

            // Get statistics with STATION-LEVEL FILTERING
            $stats_query = DB::table('meters as m')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->select([
                    DB::raw('COUNT(*) as total_meters'),
                    DB::raw('COUNT(CASE WHEN m.is_active = 1 THEN 1 END) as active_meters'),
                    DB::raw('COUNT(CASE WHEN m.is_active = 0 THEN 1 END) as inactive_meters'),
                    DB::raw('COUNT(DISTINCT t.id) as tanks_with_meters'),
                    DB::raw('COUNT(DISTINCT s.id) as stations_with_meters')
                ]);

            // SECURITY: Apply same station filtering to stats
            if ($user->role !== 'admin') {
                $stats_query->where('s.id', $user->station_id);
            } elseif ($station_id) {
                $stats_query->where('s.id', $station_id);
            }

            $stats = $stats_query->first();

            // Get tanks for selected station with SECURITY VALIDATION
            $tanks = collect();
            if ($station_id && $this->validateStationAccess($station_id)) {
                $tanks = DB::table('tanks')
                    ->select('id', 'tank_number', 'fuel_type')
                    ->where('station_id', $station_id)
                    ->orderBy('tank_number')
                    ->get();
            }

            return view('meters.index', compact(
                'meters', 'stats', 'accessible_stations', 'tanks',
                'search', 'station_id', 'tank_id', 'status'
            ));

            // Also pass 'stations' for backward compatibility if needed
            return view('meters.index', [
                'meters' => $meters,
                'stats' => $stats,
                'accessible_stations' => $accessible_stations,
                'stations' => $accessible_stations, // Alias for compatibility
                'tanks' => $tanks,
                'search' => $search,
                'station_id' => $station_id,
                'tank_id' => $tank_id,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show create meter form with STATION-LEVEL SECURITY
     */
    public function create(Request $request)
    {
        try {
            $user = auth()->user();
            $station_id = $request->get('station_id');
            $tank_id = $request->get('tank_id');

            // SECURITY: Get accessible stations
            $accessible_stations = $this->getUserAccessibleStations();

            // SECURITY: Set default station for non-admin users
            if (!$station_id && $user->role !== 'admin') {
                $station_id = $user->station_id;
            }

            // SECURITY: Validate station access
            if ($station_id && !$this->validateStationAccess($station_id)) {
                return back()->with('error', 'Access denied: You cannot create meters for this station');
            }

            $tanks = collect();
            $selected_station = null;
            $selected_tank = null;

            if ($station_id) {
                $selected_station = $accessible_stations->firstWhere('id', $station_id);

                if ($selected_station) {
                    $tanks = DB::table('tanks')
                        ->select([
                            'id', 'tank_number', 'fuel_type', 'capacity_liters',
                            DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = tanks.id) as meter_count')
                        ])
                        ->where('station_id', $station_id)
                        ->orderBy('tank_number')
                        ->get();
                }
            }

            if ($tank_id && $tanks->isNotEmpty()) {
                $selected_tank = $tanks->firstWhere('id', $tank_id);
            }

            $existing_meter_numbers = DB::table('meters')
                ->pluck('meter_number')
                ->toArray();

            return view('meters.create', [
                'accessible_stations' => $accessible_stations,
                'stations' => $accessible_stations, // Alias for compatibility
                'tanks' => $tanks,
                'selected_station' => $selected_station,
                'selected_tank' => $selected_tank,
                'existing_meter_numbers' => $existing_meter_numbers,
                'station_id' => $station_id,
                'tank_id' => $tank_id
            ]);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Store new meter with STATION-LEVEL SECURITY
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            // Validate basic input
            $validator = Validator::make($request->all(), [
                'tank_id' => [
                    'required',
                    'exists:tanks,id'
                ],
                'meter_number' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Z0-9\-\_]+$/',
                    'unique:meters,meter_number'
                ],
                'current_reading_liters' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:999999999.999',
                    'regex:/^\d+(\.\d{1,3})?$/'
                ],
                'is_active' => 'boolean'
            ], [
                'meter_number.regex' => 'Meter number can only contain uppercase letters, numbers, hyphens, and underscores',
                'meter_number.unique' => 'Meter number already exists',
                'current_reading_liters.regex' => 'Reading can have maximum 3 decimal places'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // SECURITY: Get tank and verify station access
            $tank = DB::table('tanks')->where('id', $request->tank_id)->first();
            if (!$tank) {
                return back()->with('error', 'Tank not found')->withInput();
            }

            // SECURITY: Validate user can access this tank's station
            if (!$this->validateStationAccess($tank->station_id)) {
                return back()->with('error', 'Access denied: You cannot create meters for this station')->withInput();
            }

            // Create meter record
            $meter_id = DB::table('meters')->insertGetId([
                'tank_id' => $request->tank_id,
                'meter_number' => strtoupper(trim($request->meter_number)),
                'current_reading_liters' => round($request->current_reading_liters, 3),
                'is_active' => $request->has('is_active') ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log creation in audit
            $this->logAuditAction('meters', $meter_id, 'INSERT', null, [
                'tank_id' => $request->tank_id,
                'meter_number' => strtoupper(trim($request->meter_number)),
                'current_reading_liters' => round($request->current_reading_liters, 3),
                'is_active' => $request->has('is_active') ? 1 : 0
            ]);

            DB::commit();

            return redirect()->route('meters.index', ['station_id' => $tank->station_id])
                ->with('success', 'Meter created successfully. You can now record daily readings for this meter.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Show meter details with STATION-LEVEL SECURITY
     */
    public function show($meter_id)
    {
        try {
            // Get meter with comprehensive details
            $meter = DB::table('meters as m')
                ->select([
                    'm.*',
                    't.tank_number',
                    't.fuel_type',
                    't.capacity_liters',
                    't.current_volume_liters',
                    's.id as station_id',
                    's.name as station_name',
                    's.location as station_location',
                    's.currency_code',
                    DB::raw('(SELECT COUNT(*) FROM meter_readings WHERE meter_id = m.id) as total_readings'),
                    DB::raw('(SELECT mr.reading_date FROM meter_readings mr WHERE mr.meter_id = m.id ORDER BY mr.reading_date DESC LIMIT 1) as last_reading_date'),
                    DB::raw('(SELECT mr.closing_reading_liters FROM meter_readings mr WHERE mr.meter_id = m.id ORDER BY mr.reading_date DESC LIMIT 1) as last_reading_value'),
                    DB::raw('(SELECT COUNT(*) FROM meter_readings mr WHERE mr.meter_id = m.id AND mr.reading_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as readings_30days')
                ])
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('m.id', $meter_id)
                ->first();

            if (!$meter) {
                return back()->with('error', 'Meter not found');
            }

            // SECURITY: Validate station access
            if (!$this->validateStationAccess($meter->station_id)) {
                return back()->with('error', 'Access denied: You do not have permission to view this meter');
            }

            // Get recent readings (last 10)
            $recent_readings = DB::table('meter_readings as mr')
                ->select([
                    'mr.*',
                    'u.first_name',
                    'u.last_name'
                ])
                ->join('users as u', 'mr.recorded_by_user_id', '=', 'u.id')
                ->where('mr.meter_id', $meter_id)
                ->orderBy('mr.reading_date', 'desc')
                ->limit(10)
                ->get();

            // Get reading statistics
            $reading_stats = DB::table('meter_readings')
                ->where('meter_id', $meter_id)
                ->select([
                    DB::raw('MIN(reading_date) as first_reading_date'),
                    DB::raw('MAX(reading_date) as last_reading_date'),
                    DB::raw('AVG(dispensed_liters) as avg_daily_dispensed'),
                    DB::raw('SUM(dispensed_liters) as total_dispensed'),
                    DB::raw('MAX(dispensed_liters) as max_daily_dispensed'),
                    DB::raw('COUNT(*) as total_reading_days')
                ])
                ->first();

            return view('meters.show', compact('meter', 'recent_readings', 'reading_stats'));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit meter form with STATION-LEVEL SECURITY
     */
    public function edit($meter_id)
    {
        try {
            // Get meter with tank and station details
            $meter = DB::table('meters as m')
                ->select([
                    'm.*',
                    't.tank_number',
                    't.fuel_type',
                    's.id as station_id',
                    's.name as station_name',
                    's.location as station_location'
                ])
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('m.id', $meter_id)
                ->first();

            if (!$meter) {
                return back()->with('error', 'Meter not found');
            }

            // SECURITY: Validate station access
            if (!$this->validateStationAccess($meter->station_id)) {
                return back()->with('error', 'Access denied: You do not have permission to edit this meter');
            }

            // Check if meter has readings (affects what can be changed)
            $has_readings = DB::table('meter_readings')->where('meter_id', $meter_id)->exists();

            // Get existing meter numbers for validation (excluding current)
            $existing_meter_numbers = DB::table('meters')
                ->where('id', '!=', $meter_id)
                ->pluck('meter_number')
                ->toArray();

            return view('meters.edit', compact('meter', 'has_readings', 'existing_meter_numbers'));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update meter with STATION-LEVEL SECURITY
     */
    public function update(Request $request, $meter_id)
    {
        DB::beginTransaction();

        try {
            // Get existing meter with station info
            $existing_meter = DB::table('meters as m')
                ->select('m.*', 't.station_id')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->where('m.id', $meter_id)
                ->first();

            if (!$existing_meter) {
                return back()->with('error', 'Meter not found');
            }

            // SECURITY: Validate station access
            if (!$this->validateStationAccess($existing_meter->station_id)) {
                return back()->with('error', 'Access denied: You do not have permission to update this meter');
            }

            // Check if meter has readings
            $has_readings = DB::table('meter_readings')->where('meter_id', $meter_id)->exists();

            // Validate according to constraints
            $validator = Validator::make($request->all(), [
                'meter_number' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Z0-9\-\_]+$/',
                    'unique:meters,meter_number,' . $meter_id
                ],
                'current_reading_liters' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:999999999.999',
                    'regex:/^\d+(\.\d{1,3})?$/'
                ],
                'is_active' => 'boolean'
            ], [
                'meter_number.regex' => 'Meter number can only contain uppercase letters, numbers, hyphens, and underscores',
                'current_reading_liters.regex' => 'Reading can have maximum 3 decimal places'
            ]);

            // Business rule validations
            $validator->after(function ($validator) use ($request, $existing_meter, $has_readings) {
                // Prevent reducing reading if meter has readings
                if ($has_readings && $request->current_reading_liters < $existing_meter->current_reading_liters) {
                    $validator->errors()->add('current_reading_liters', 'Cannot reduce meter reading below current value when readings exist');
                }
            });

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Update meter
            $update_data = [
                'meter_number' => strtoupper(trim($request->meter_number)),
                'current_reading_liters' => round($request->current_reading_liters, 3),
                'is_active' => $request->has('is_active') ? 1 : 0,
                'updated_at' => now()
            ];

            DB::table('meters')->where('id', $meter_id)->update($update_data);

            // Log update in audit
            $this->logAuditAction('meters', $meter_id, 'UPDATE', (array)$existing_meter, $update_data);

            DB::commit();

            return redirect()->route('meters.show', $meter_id)
                ->with('success', 'Meter updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Get tanks for station via AJAX with STATION-LEVEL SECURITY
     */
    public function getTanksForStation($station_id)
    {
        try {
            // SECURITY: Validate station access
            if (!$this->validateStationAccess($station_id)) {
                return response()->json(['error' => 'Access denied: You do not have permission to view this station\'s tanks'], 403);
            }

            $tanks = DB::table('tanks')
                ->select([
                    'id', 'tank_number', 'fuel_type', 'capacity_liters',
                    DB::raw('(SELECT COUNT(*) FROM meters WHERE tank_id = tanks.id) as meter_count')
                ])
                ->where('station_id', $station_id)
                ->orderBy('tank_number')
                ->get();

            return response()->json(['tanks' => $tanks]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle meter status with STATION-LEVEL SECURITY
     */
    public function toggleStatus($meter_id)
    {
        DB::beginTransaction();

        try {
            // Get meter with station info
            $meter = DB::table('meters as m')
                ->select('m.*', 't.station_id')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->where('m.id', $meter_id)
                ->first();

            if (!$meter) {
                return response()->json(['error' => 'Meter not found'], 404);
            }

            // SECURITY: Validate station access
            if (!$this->validateStationAccess($meter->station_id)) {
                return response()->json(['error' => 'Access denied: You do not have permission to modify this meter'], 403);
            }

            $new_status = !$meter->is_active;

            DB::table('meters')
                ->where('id', $meter_id)
                ->update([
                    'is_active' => $new_status,
                    'updated_at' => now()
                ]);

            // Log status change
            $this->logAuditAction('meters', $meter_id, 'UPDATE',
                ['is_active' => $meter->is_active],
                ['is_active' => $new_status]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'new_status' => $new_status,
                'message' => 'Meter status updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete meter with STATION-LEVEL SECURITY
     */
    public function destroy($meter_id)
    {
        DB::beginTransaction();

        try {
            // Get meter with station info
            $meter = DB::table('meters as m')
                ->select('m.*', 't.station_id')
                ->join('tanks as t', 'm.tank_id', '=', 't.id')
                ->where('m.id', $meter_id)
                ->first();

            if (!$meter) {
                return response()->json(['error' => 'Meter not found'], 404);
            }

            // SECURITY: Validate station access
            if (!$this->validateStationAccess($meter->station_id)) {
                return response()->json(['error' => 'Access denied: You do not have permission to delete this meter'], 403);
            }

            // Check for dependencies
            $reading_count = DB::table('meter_readings')->where('meter_id', $meter_id)->count();

            if ($reading_count > 0) {
                return response()->json([
                    'error' => 'Cannot delete meter',
                    'message' => "Meter has {$reading_count} readings that would be lost"
                ], 400);
            }

            // Safe to delete
            DB::table('meters')->where('id', $meter_id)->delete();

            $this->logAuditAction('meters', $meter_id, 'DELETE', (array)$meter, null);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Meter deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Log audit action
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
