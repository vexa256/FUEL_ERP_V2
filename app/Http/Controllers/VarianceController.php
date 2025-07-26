<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VarianceController extends Controller
{
    /**
     * Main Variance Dashboard - Active Alerts, Trends, Investigation
     * 🔒 ENFORCES STATION-LEVEL ACCESS CONTROL - REAL FIELDS ONLY
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $accessible_stations = $this->getUserAccessibleStations($user);

            if ($accessible_stations->isEmpty()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => 'No authorized stations found'], 403);
                }
                return redirect()->back()->with('error', 'No authorized stations found');
            }

            $station_id = $request->get('station_id');
            $date_from = $request->get('date_from', Carbon::now()->subDays(7)->format('Y-m-d'));
            $date_to = $request->get('date_to', Carbon::now()->format('Y-m-d'));
            $severity_filter = $request->get('severity');
            $status_filter = $request->get('status', 'open');

            // Validate station access
            $station_ids = $accessible_stations->pluck('id')->toArray();
            if (!$station_id || !in_array($station_id, $station_ids)) {
                $station_id = $accessible_stations->first()->id;
            }

            // Get active variance alerts - REAL NOTIFICATIONS TABLE FIELDS
            $active_alerts = $this->getVarianceAlerts($station_id, $date_from, $date_to, $severity_filter, $status_filter);

            // Get variance trends - REAL DAILY_RECONCILIATIONS FIELDS
            $variance_trends = $this->getVarianceTrends($station_id, $date_from, $date_to);

            // Get summary statistics - REAL AGGREGATIONS
            $summary_stats = $this->getVarianceSummary($station_id, $date_from, $date_to);

            // Get tank-level variance analysis - REAL SCHEMA JOINS
            $tank_analysis = $this->getTankVarianceAnalysis($station_id, $date_from, $date_to);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'stations' => $accessible_stations,
                        'selected_station' => $station_id,
                        'active_alerts' => $active_alerts,
                        'variance_trends' => $variance_trends,
                        'summary_stats' => $summary_stats,
                        'tank_analysis' => $tank_analysis,
                        'filters' => compact('date_from', 'date_to', 'severity_filter', 'status_filter')
                    ]
                ]);
            }

            return view('variance.index', compact(
                'accessible_stations', 'station_id', 'active_alerts', 'variance_trends',
                'summary_stats', 'tank_analysis', 'date_from', 'date_to', 'severity_filter', 'status_filter'
            ));

        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Investigation Log - View and manage variance investigations
     * 🔒 STATION-SCOPED ACCESS CONTROL
     */
    public function investigationLog(Request $request)
    {
        try {
            $user = Auth::user();
            $accessible_stations = $this->getUserAccessibleStations($user);

            if ($accessible_stations->isEmpty()) {
                return redirect()->back()->with('error', 'No authorized stations found');
            }

            $station_id = $request->get('station_id');
            $station_ids = $accessible_stations->pluck('id')->toArray();

            if (!$station_id || !in_array($station_id, $station_ids)) {
                $station_id = $accessible_stations->first()->id;
            }

            // Get investigation history - REAL NOTIFICATIONS TABLE FIELDS
            $investigations = DB::table('notifications as n')
                ->select([
                    'n.id',
                    'n.title',
                    'n.message',
                    'n.severity',
                    'n.status',
                    'n.variance_magnitude',
                    'n.variance_percentage',
                    'n.notification_date',
                    'n.resolution_notes',
                    'n.resolved_at',
                    'n.created_at',
                    't.tank_number',
                    't.fuel_type',
                    's.name as station_name',
                    'resolver.first_name as resolver_first_name',
                    'resolver.last_name as resolver_last_name'
                ])
                ->join('tanks as t', 'n.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->leftJoin('users as resolver', 'n.resolved_by_user_id', '=', 'resolver.id')
                ->where('n.notification_type', 'volume_variance')
                ->where('s.id', $station_id)
                ->orderBy('n.created_at', 'desc')
                ->paginate(20);

            return view('variance.investigation-log', compact('accessible_stations', 'station_id', 'investigations'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update Investigation Status - Approve/Investigate Variances
     * 🔒 RESPECTS DATABASE AUTOMATION - NO MANUAL VARIANCE CREATION
     * 🔒 ROLE-BASED ACCESS: ADMIN & MANAGER ONLY
     */
    public function updateInvestigation(Request $request, $notification_id)
    {
        $user = Auth::user();

        // CRITICAL: Only admin and manager can resolve variances
        if (!in_array($user->role, ['admin', 'manager'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient permissions. Only managers can approve/resolve variances.'
                ], 403);
            }
            return redirect()->back()->with('error', 'Insufficient permissions. Only managers can approve/resolve variances.');
        }

        // Validate request
        $validator = validator($request->all(), [
            'status' => 'required|in:open,investigating,resolved',
            'resolution_notes' => 'required_if:status,resolved|nullable|string|max:1000',
            'station_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Verify station access and notification exists - REAL SCHEMA VALIDATION
            $notification = DB::table('notifications as n')
                ->join('tanks as t', 'n.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('n.id', $notification_id)
                ->where('n.notification_type', 'volume_variance')
                ->where('s.id', $request->station_id)
                ->select('n.id', 'n.status', 's.id as station_id', 't.tank_number')
                ->first();

            if (!$notification) {
                throw new \Exception('Variance notification not found or access denied');
            }

            // Verify user has station access
            if (!$this->hasStationAccess($user, $notification->station_id)) {
                throw new \Exception('Unauthorized station access');
            }

            // Prepare update data - REAL NOTIFICATIONS TABLE FIELDS
            $update_data = [
                'status' => $request->status,
                'updated_at' => now()
            ];

            // Handle resolution
            if ($request->status === 'resolved') {
                $update_data['resolved_by_user_id'] = $user->id;
                $update_data['resolved_at'] = now();
                $update_data['resolution_notes'] = $request->resolution_notes;
            } else {
                // Clear resolution data if status changed from resolved
                $update_data['resolved_by_user_id'] = null;
                $update_data['resolved_at'] = null;
                if ($request->status !== 'investigating') {
                    $update_data['resolution_notes'] = null;
                }
            }

            // Update notification - RESPECTS DATABASE SCHEMA
            $affected = DB::table('notifications')
                ->where('id', $notification_id)
                ->update($update_data);

            if ($affected === 0) {
                throw new \Exception('Failed to update investigation status');
            }

            DB::commit();

            $response_data = [
                'success' => true,
                'message' => 'Investigation status updated successfully',
                'data' => [
                    'notification_id' => $notification_id,
                    'status' => $request->status,
                    'tank' => $notification->tank_number
                ]
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($response_data);
            }

            return redirect()->back()->with('success', $response_data['message']);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Get Variance Alerts - REAL NOTIFICATIONS TABLE QUERY
     */
    private function getVarianceAlerts($station_id, $date_from, $date_to, $severity_filter = null, $status_filter = 'open')
    {
        $query = DB::table('notifications as n')
            ->select([
                'n.id',
                'n.title',
                'n.message',
                'n.severity',
                'n.status',
                'n.variance_magnitude',
                'n.variance_percentage',
                'n.notification_date',
                'n.created_at',
                't.id as tank_id',
                't.tank_number',
                't.fuel_type',
                'dr.reconciliation_date',
                'dr.volume_variance_liters',
                'dr.theoretical_closing_stock_liters',
                'dr.actual_closing_stock_liters'
            ])
            ->join('tanks as t', 'n.tank_id', '=', 't.id')
            ->leftJoin('daily_reconciliations as dr', function($join) {
                $join->on('dr.tank_id', '=', 't.id')
                     ->on('dr.reconciliation_date', '=', 'n.notification_date');
            })
            ->where('n.notification_type', 'volume_variance')
            ->where('t.station_id', $station_id)
            ->whereBetween('n.notification_date', [$date_from, $date_to]);

        if ($severity_filter) {
            $query->where('n.severity', $severity_filter);
        }

        if ($status_filter) {
            $query->where('n.status', $status_filter);
        }

        return $query->orderBy('n.created_at', 'desc')->get();
    }

    /**
     * Get Variance Trends - REAL DAILY_RECONCILIATIONS ANALYSIS
     */
    private function getVarianceTrends($station_id, $date_from, $date_to)
    {
        return DB::table('daily_reconciliations as dr')
            ->select([
                'dr.reconciliation_date',
                't.fuel_type',
                't.tank_number',
                'dr.variance_percentage',
                'dr.volume_variance_liters',
                'dr.abs_variance_percentage',
                'dr.total_dispensed_liters'
            ])
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->where('t.station_id', $station_id)
            ->whereBetween('dr.reconciliation_date', [$date_from, $date_to])
            ->whereRaw('ABS(dr.variance_percentage) > 0.5') // Only significant variances
            ->orderBy('dr.reconciliation_date', 'desc')
            ->orderBy('dr.abs_variance_percentage', 'desc')
            ->get();
    }

    /**
     * Get Variance Summary Statistics - REAL AGGREGATIONS
     */
    private function getVarianceSummary($station_id, $date_from, $date_to)
    {
        // Notifications summary
        $alerts_summary = DB::table('notifications as n')
            ->join('tanks as t', 'n.tank_id', '=', 't.id')
            ->where('n.notification_type', 'volume_variance')
            ->where('t.station_id', $station_id)
            ->whereBetween('n.notification_date', [$date_from, $date_to])
            ->select([
                DB::raw('COUNT(*) as total_alerts'),
                DB::raw('COUNT(CASE WHEN n.severity = "critical" THEN 1 END) as critical_alerts'),
                DB::raw('COUNT(CASE WHEN n.severity = "high" THEN 1 END) as high_alerts'),
                DB::raw('COUNT(CASE WHEN n.severity = "medium" THEN 1 END) as medium_alerts'),
                DB::raw('COUNT(CASE WHEN n.status = "resolved" THEN 1 END) as resolved_alerts'),
                DB::raw('COUNT(CASE WHEN n.status = "investigating" THEN 1 END) as investigating_alerts'),
                DB::raw('COUNT(CASE WHEN n.status = "open" THEN 1 END) as open_alerts'),
                DB::raw('AVG(ABS(n.variance_percentage)) as avg_variance_percentage'),
                DB::raw('MAX(ABS(n.variance_percentage)) as max_variance_percentage'),
                DB::raw('SUM(ABS(n.variance_magnitude)) as total_variance_liters')
            ])
            ->first();

        // Reconciliations summary
        $reconciliation_summary = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->where('t.station_id', $station_id)
            ->whereBetween('dr.reconciliation_date', [$date_from, $date_to])
            ->select([
                DB::raw('COUNT(*) as total_reconciliations'),
                DB::raw('COUNT(CASE WHEN ABS(dr.variance_percentage) > 2.0000 THEN 1 END) as reconciliations_with_alerts'),
                DB::raw('AVG(ABS(dr.variance_percentage)) as avg_reconciliation_variance'),
                DB::raw('SUM(ABS(dr.volume_variance_liters)) as total_volume_variance'),
                DB::raw('COUNT(DISTINCT dr.tank_id) as tanks_with_variance')
            ])
            ->first();

        return (object) array_merge((array) $alerts_summary, (array) $reconciliation_summary);
    }

    /**
     * Get Tank-Level Variance Analysis - REAL SCHEMA JOINS
     */
    private function getTankVarianceAnalysis($station_id, $date_from, $date_to)
    {
        return DB::table('tanks as t')
            ->select([
                't.id as tank_id',
                't.tank_number',
                't.fuel_type',
                't.capacity_liters',
                DB::raw('COUNT(dr.id) as reconciliation_count'),
                DB::raw('COUNT(n.id) as alert_count'),
                DB::raw('AVG(ABS(dr.variance_percentage)) as avg_variance_percentage'),
                DB::raw('MAX(ABS(dr.variance_percentage)) as max_variance_percentage'),
                DB::raw('SUM(ABS(dr.volume_variance_liters)) as total_variance_liters'),
                DB::raw('COUNT(CASE WHEN n.severity = "critical" THEN 1 END) as critical_alerts'),
                DB::raw('COUNT(CASE WHEN n.status = "resolved" THEN 1 END) as resolved_alerts')
            ])
            ->leftJoin('daily_reconciliations as dr', function($join) use ($date_from, $date_to) {
                $join->on('dr.tank_id', '=', 't.id')
                     ->whereBetween('dr.reconciliation_date', [$date_from, $date_to]);
            })
            ->leftJoin('notifications as n', function($join) use ($date_from, $date_to) {
                $join->on('n.tank_id', '=', 't.id')
                     ->where('n.notification_type', 'volume_variance')
                     ->whereBetween('n.notification_date', [$date_from, $date_to]);
            })
            ->where('t.station_id', $station_id)
            ->groupBy('t.id', 't.tank_number', 't.fuel_type', 't.capacity_liters')
            ->orderBy('alert_count', 'desc')
            ->orderBy('avg_variance_percentage', 'desc')
            ->get();
    }

    /**
     * Get User's Accessible Stations - REAL STATIONS TABLE
     */
    private function getUserAccessibleStations($user)
    {
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

    /**
     * Check Station Access - REAL USER.STATION_ID FK
     */
    private function hasStationAccess($user, $station_id)
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->station_id == $station_id;
    }

    /**
     * Check if user can approve/resolve variances
     * 🔒 BUSINESS RULE: Only admin and manager roles
     */
    private function canApproveVariances($user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }
}
