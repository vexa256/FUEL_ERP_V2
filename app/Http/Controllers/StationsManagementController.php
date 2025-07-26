<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StationsManagementController extends Controller
{
    /**
     * Display stations management dashboard
     */
    public function index(Request $request)
    {
        try {
            $search = $request->get('search');
            $currency = $request->get('currency');
            $timezone = $request->get('timezone');

            // Build stations query
            $query = DB::table('stations as s')
                ->select([
                    's.id',
                    's.name',
                    's.location',
                    's.currency_code',
                    's.timezone',
                    's.created_at',
                    's.updated_at',
                    DB::raw('(SELECT COUNT(*) FROM users WHERE station_id = s.id) as total_users'),
                    DB::raw('(SELECT COUNT(*) FROM users WHERE station_id = s.id AND is_active = 1) as active_users'),
                    DB::raw('(SELECT COUNT(*) FROM tanks WHERE station_id = s.id) as total_tanks'),
                    DB::raw('(SELECT COUNT(*) FROM deliveries d JOIN tanks t ON d.tank_id = t.id WHERE t.station_id = s.id AND DATE(d.delivery_date) = CURDATE()) as today_deliveries'),
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE station_id = s.id AND status = "open") as open_notifications')
                ])
                ->orderBy('s.name');

            // Apply filters
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('s.name', 'like', "%{$search}%")
                      ->orWhere('s.location', 'like', "%{$search}%");
                });
            }

            if ($currency) {
                $query->where('s.currency_code', $currency);
            }

            if ($timezone) {
                $query->where('s.timezone', $timezone);
            }

            $stations = $query->paginate(15)->withQueryString();

            // Get summary statistics
            $stats = DB::table('stations')
                ->select([
                    DB::raw('COUNT(*) as total_stations'),
                    DB::raw('COUNT(DISTINCT currency_code) as total_currencies'),
                    DB::raw('COUNT(DISTINCT timezone) as total_timezones')
                ])
                ->first();

            // Get currency and timezone options for filters
            $currencies = DB::table('stations')
                ->select('currency_code')
                ->distinct()
                ->orderBy('currency_code')
                ->pluck('currency_code');

            $timezones = DB::table('stations')
                ->select('timezone')
                ->distinct()
                ->orderBy('timezone')
                ->pluck('timezone');

            return view('stations.index', compact(
                'stations', 'stats', 'currencies', 'timezones',
                'search', 'currency', 'timezone'
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load stations: ' . $e->getMessage());
        }
    }

    /**
     * Show create station form
     */
    public function create()
    {
        try {
            // Get available currency codes and timezones
            $currencies = [
                'UGX' => 'Uganda Shilling (UGX)',
                'USD' => 'US Dollar (USD)',
                'EUR' => 'Euro (EUR)',
                'KES' => 'Kenyan Shilling (KES)',
                'TZS' => 'Tanzanian Shilling (TZS)'
            ];

            $timezones = [
                'Africa/Kampala' => 'Africa/Kampala (UTC+3)',
                'Africa/Nairobi' => 'Africa/Nairobi (UTC+3)',
                'Africa/Dar_es_Salaam' => 'Africa/Dar_es_Salaam (UTC+3)',
                'UTC' => 'UTC (UTC+0)'
            ];

            return view('stations.create', compact('currencies', 'timezones'));

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Store new station with enterprise validation
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate request data according to database schema
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-zA-Z0-9\s\-\.\_]+$/',
                    'unique:stations,name'
                ],
                'location' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-zA-Z0-9\s\-\.\,\_]+$/'
                ],
                'currency_code' => [
                    'required',
                    'string',
                    'size:3',
                    'regex:/^[A-Z]{3}$/'
                ],
                'timezone' => [
                    'required',
                    'string',
                    'max:50',
                    'in:Africa/Kampala,Africa/Nairobi,Africa/Dar_es_Salaam,UTC'
                ]
            ], [
                'name.regex' => 'Station name can only contain letters, numbers, spaces, hyphens, dots, and underscores',
                'name.unique' => 'Station name already exists',
                'location.regex' => 'Location can only contain letters, numbers, spaces, hyphens, dots, commas, and underscores',
                'currency_code.size' => 'Currency code must be exactly 3 characters',
                'currency_code.regex' => 'Currency code must be 3 uppercase letters',
                'timezone.in' => 'Invalid timezone selection'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Create station record
            $station_id = DB::table('stations')->insertGetId([
                'name' => trim($request->name),
                'location' => trim($request->location),
                'currency_code' => strtoupper($request->currency_code),
                'timezone' => $request->timezone,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log creation in audit
            $this->logAuditAction('stations', $station_id, 'INSERT', null, [
                'name' => trim($request->name),
                'location' => trim($request->location),
                'currency_code' => strtoupper($request->currency_code),
                'timezone' => $request->timezone
            ]);

            DB::commit();

            return redirect()->route('stations.index')
                ->with('success', 'Station created successfully. You can now add tanks and users to this station.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create station: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show station details with comprehensive data
     */
    public function show($id)
    {
        try {
            // Get station with comprehensive stats
            $station = DB::table('stations as s')
                ->select([
                    's.*',
                    DB::raw('(SELECT COUNT(*) FROM users WHERE station_id = s.id) as total_users'),
                    DB::raw('(SELECT COUNT(*) FROM users WHERE station_id = s.id AND is_active = 1) as active_users'),
                    DB::raw('(SELECT COUNT(*) FROM tanks WHERE station_id = s.id) as total_tanks'),
                    DB::raw('(SELECT COUNT(*) FROM meters m JOIN tanks t ON m.tank_id = t.id WHERE t.station_id = s.id AND m.is_active = 1) as active_meters'),
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE station_id = s.id AND status = "open") as open_notifications'),
                    DB::raw('(SELECT COUNT(*) FROM selling_prices WHERE station_id = s.id AND is_active = 1) as active_prices')
                ])
                ->where('s.id', $id)
                ->first();

            if (!$station) {
                return back()->with('error', 'Station not found.');
            }

            // Get tanks summary
            $tanks_summary = DB::table('tanks as t')
                ->select([
                    't.fuel_type',
                    DB::raw('COUNT(*) as tank_count'),
                    DB::raw('SUM(t.capacity_liters) as total_capacity'),
                    DB::raw('SUM(t.current_volume_liters) as total_current_volume'),
                    DB::raw('ROUND(AVG((t.current_volume_liters / t.capacity_liters) * 100), 2) as avg_fill_percentage')
                ])
                ->where('t.station_id', $id)
                ->groupBy('t.fuel_type')
                ->get();

            // Get recent activity (last 30 days)
            $recent_activity = DB::table('daily_reconciliations as dr')
                ->select([
                    'dr.reconciliation_date',
                    't.fuel_type',
                    DB::raw('SUM(dr.total_sales_ugx) as daily_sales'),
                    DB::raw('SUM(dr.total_dispensed_liters) as daily_volume'),
                    DB::raw('AVG(ABS(dr.variance_percentage)) as avg_variance')
                ])
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->where('t.station_id', $id)
                ->where('dr.reconciliation_date', '>=', now()->subDays(30))
                ->groupBy('dr.reconciliation_date', 't.fuel_type')
                ->orderBy('dr.reconciliation_date', 'desc')
                ->limit(20)
                ->get();

            // Get current selling prices
            $current_prices = DB::table('selling_prices as sp')
                ->select([
                    'sp.fuel_type',
                    'sp.price_per_liter_ugx',
                    'sp.effective_from_date',
                    'sp.effective_to_date',
                    'u.first_name',
                    'u.last_name'
                ])
                ->join('users as u', 'sp.set_by_user_id', '=', 'u.id')
                ->where('sp.station_id', $id)
                ->where('sp.is_active', true)
                ->get();

            // Get recent notifications
            $recent_notifications = DB::table('notifications')
                ->where('station_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return view('stations.show', compact(
                'station', 'tanks_summary', 'recent_activity',
                'current_prices', 'recent_notifications'
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load station details: ' . $e->getMessage());
        }
    }

    /**
     * Show edit station form
     */
    public function edit($id)
    {
        try {
            $station = DB::table('stations')->where('id', $id)->first();

            if (!$station) {
                return back()->with('error', 'Station not found.');
            }

            // Get available currency codes and timezones
            $currencies = [
                'UGX' => 'Uganda Shilling (UGX)',
                'USD' => 'US Dollar (USD)',
                'EUR' => 'Euro (EUR)',
                'KES' => 'Kenyan Shilling (KES)',
                'TZS' => 'Tanzanian Shilling (TZS)'
            ];

            $timezones = [
                'Africa/Kampala' => 'Africa/Kampala (UTC+3)',
                'Africa/Nairobi' => 'Africa/Nairobi (UTC+3)',
                'Africa/Dar_es_Salaam' => 'Africa/Dar_es_Salaam (UTC+3)',
                'UTC' => 'UTC (UTC+0)'
            ];

            return view('stations.edit', compact('station', 'currencies', 'timezones'));

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load edit form: ' . $e->getMessage());
        }
    }

    /**
     * Update station with enterprise validation
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // Get existing station
            $existing_station = DB::table('stations')->where('id', $id)->first();

            if (!$existing_station) {
                return back()->with('error', 'Station not found.');
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-zA-Z0-9\s\-\.\_]+$/',
                    Rule::unique('stations')->ignore($id)
                ],
                'location' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-zA-Z0-9\s\-\.\,\_]+$/'
                ],
                'currency_code' => [
                    'required',
                    'string',
                    'size:3',
                    'regex:/^[A-Z]{3}$/'
                ],
                'timezone' => [
                    'required',
                    'string',
                    'max:50',
                    'in:Africa/Kampala,Africa/Nairobi,Africa/Dar_es_Salaam,UTC'
                ]
            ], [
                'name.regex' => 'Station name can only contain letters, numbers, spaces, hyphens, dots, and underscores',
                'name.unique' => 'Station name already exists',
                'location.regex' => 'Location can only contain letters, numbers, spaces, hyphens, dots, commas, and underscores',
                'currency_code.size' => 'Currency code must be exactly 3 characters',
                'currency_code.regex' => 'Currency code must be 3 uppercase letters',
                'timezone.in' => 'Invalid timezone selection'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Check for critical dependencies before currency change
            if ($existing_station->currency_code !== strtoupper($request->currency_code)) {
                $has_financial_data = $this->checkFinancialDataDependencies($id);
                if ($has_financial_data) {
                    return back()->with('error', 'Cannot change currency: Station has existing financial data (deliveries, sales, prices). Currency changes would corrupt financial calculations.')->withInput();
                }
            }

            // Prepare update data
            $update_data = [
                'name' => trim($request->name),
                'location' => trim($request->location),
                'currency_code' => strtoupper($request->currency_code),
                'timezone' => $request->timezone,
                'updated_at' => now()
            ];

            // Update station
            DB::table('stations')->where('id', $id)->update($update_data);

            // Log update in audit
            $this->logAuditAction('stations', $id, 'UPDATE', (array)$existing_station, $update_data);

            DB::commit();

            return redirect()->route('stations.index')
                ->with('success', 'Station updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update station: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Get station dashboard data for AJAX
     */
    public function getDashboardData($id)
    {
        try {
            // Real-time station metrics
            $metrics = DB::table('stations as s')
                ->select([
                    's.name',
                    's.location',
                    DB::raw('(SELECT COUNT(*) FROM tanks WHERE station_id = s.id) as total_tanks'),
                    DB::raw('(SELECT COUNT(*) FROM users WHERE station_id = s.id AND is_active = 1) as active_users'),
                    DB::raw('(SELECT COUNT(*) FROM notifications WHERE station_id = s.id AND status = "open" AND severity IN ("high", "critical")) as urgent_alerts'),
                    DB::raw('(SELECT COALESCE(SUM(dr.total_sales_ugx), 0) FROM daily_reconciliations dr JOIN tanks t ON dr.tank_id = t.id WHERE t.station_id = s.id AND dr.reconciliation_date = CURDATE()) as today_sales'),
                    DB::raw('(SELECT COALESCE(SUM(dr.total_dispensed_liters), 0) FROM daily_reconciliations dr JOIN tanks t ON dr.tank_id = t.id WHERE t.station_id = s.id AND dr.reconciliation_date = CURDATE()) as today_volume')
                ])
                ->where('s.id', $id)
                ->first();

            if (!$metrics) {
                return response()->json(['error' => 'Station not found'], 404);
            }

            // Tank status summary
            $tank_status = DB::table('vw_current_tank_status')
                ->where('station_id', $id)
                ->select('fuel_type', 'stock_level_status', DB::raw('COUNT(*) as count'))
                ->groupBy('fuel_type', 'stock_level_status')
                ->get();

            return response()->json([
                'metrics' => $metrics,
                'tank_status' => $tank_status
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get dashboard data'], 500);
        }
    }

    /**
     * Delete station with dependency validation
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $station = DB::table('stations')->where('id', $id)->first();

            if (!$station) {
                return response()->json(['error' => 'Station not found'], 404);
            }

            // Check for critical dependencies that prevent deletion
            $dependencies = $this->checkStationDependencies($id);

            if (!empty($dependencies)) {
                return response()->json([
                    'error' => 'Cannot delete station',
                    'message' => 'Station has existing data that would be lost:',
                    'dependencies' => $dependencies
                ], 400);
            }

            // Safe to delete - no dependencies found
            DB::table('stations')->where('id', $id)->delete();

            $this->logAuditAction('stations', $id, 'DELETE', (array)$station, null);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Station deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete station: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get station data for AJAX requests
     */
    public function getStationData($id)
    {
        try {
            $station = DB::table('stations')->where('id', $id)->first();

            if (!$station) {
                return response()->json(['error' => 'Station not found'], 404);
            }

            return response()->json(['station' => $station]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get station data'], 500);
        }
    }

    /**
     * Check for financial data dependencies that prevent currency changes
     */
    private function checkFinancialDataDependencies($station_id)
    {
        // Check for deliveries with costs
        $has_deliveries = DB::table('deliveries as d')
            ->join('tanks as t', 'd.tank_id', '=', 't.id')
            ->where('t.station_id', $station_id)
            ->exists();

        // Check for selling prices
        $has_prices = DB::table('selling_prices')
            ->where('station_id', $station_id)
            ->exists();

        // Check for reconciliations with financial data
        $has_reconciliations = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->where('t.station_id', $station_id)
            ->where(function($q) {
                $q->where('dr.total_sales_ugx', '>', 0)
                  ->orWhere('dr.total_cogs_ugx', '>', 0);
            })
            ->exists();

        return $has_deliveries || $has_prices || $has_reconciliations;
    }

    /**
     * Check for dependencies that prevent station deletion
     */
    private function checkStationDependencies($station_id)
    {
        $dependencies = [];

        // Check users
        $user_count = DB::table('users')->where('station_id', $station_id)->count();
        if ($user_count > 0) {
            $dependencies[] = "{$user_count} users";
        }

        // Check tanks
        $tank_count = DB::table('tanks')->where('station_id', $station_id)->count();
        if ($tank_count > 0) {
            $dependencies[] = "{$tank_count} tanks";
        }

        // Check deliveries
        $delivery_count = DB::table('deliveries as d')
            ->join('tanks as t', 'd.tank_id', '=', 't.id')
            ->where('t.station_id', $station_id)
            ->count();
        if ($delivery_count > 0) {
            $dependencies[] = "{$delivery_count} deliveries";
        }

        // Check reconciliations
        $reconciliation_count = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->where('t.station_id', $station_id)
            ->count();
        if ($reconciliation_count > 0) {
            $dependencies[] = "{$reconciliation_count} reconciliations";
        }

        // Check selling prices
        $price_count = DB::table('selling_prices')->where('station_id', $station_id)->count();
        if ($price_count > 0) {
            $dependencies[] = "{$price_count} price records";
        }

        // Check notifications
        $notification_count = DB::table('notifications')->where('station_id', $station_id)->count();
        if ($notification_count > 0) {
            $dependencies[] = "{$notification_count} notifications";
        }

        return $dependencies;
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
            // Log audit failure but don't break main operation
            \Log::error('Audit log failed: ' . $e->getMessage());
        }
    }
}
