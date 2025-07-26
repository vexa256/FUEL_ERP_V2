<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Daily Reconciliation Reports Dashboard
     * 🔒 ENFORCES STATION-LEVEL ACCESS CONTROL - REAL FIELDS ONLY
     */
    public function dailyReconciliation(Request $request)
    {
        try {
            // Get user's accessible stations based on role
            $accessible_stations = $this->getUserAccessibleStations();

            $station_id = $request->get('station_id');
            $report_date = $request->get('report_date', now()->format('Y-m-d'));
            $tank_id = $request->get('tank_id');

            // Validate station access if specified
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            // Get available tanks for selected station - REAL SCHEMA FIELDS ONLY
            $available_tanks = collect([]);
            if ($station_id) {
                $available_tanks = DB::table('tanks')
                    ->select('id', 'tank_number', 'fuel_type')
                    ->where('station_id', $station_id)
                    ->orderBy('tank_number')
                    ->get();
            }

            // Build daily reconciliations query - ONLY REAL SCHEMA FIELDS
            $reconciliations_query = DB::table('daily_reconciliations as dr')
                ->select([
                    'dr.id',
                    'dr.tank_id',
                    'dr.reconciliation_date',
                    'dr.opening_stock_liters',
                    'dr.total_delivered_liters',
                    'dr.total_dispensed_liters',
                    'dr.theoretical_closing_stock_liters',
                    'dr.actual_closing_stock_liters',
                    'dr.volume_variance_liters',
                    'dr.variance_percentage',
                    'dr.total_cogs_ugx',
                    'dr.total_sales_ugx',
                    'dr.gross_profit_ugx',
                    'dr.profit_margin_percentage',
                    'dr.reconciled_at',
                    't.tank_number',
                    't.fuel_type',
                    's.id as station_id',
                    's.name as station_name',
                    's.currency_code',
                    'u.first_name',
                    'u.last_name'
                ])
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->join('users as u', 'dr.reconciled_by_user_id', '=', 'u.id')
                ->where('dr.reconciliation_date', $report_date);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $reconciliations_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $reconciliations_query->where('s.id', $station_id);
            }

            if ($tank_id) {
                $reconciliations_query->where('dr.tank_id', $tank_id);
            }

            $reconciliations = $reconciliations_query->orderBy('t.tank_number')->get();

            // Get summary statistics for the date - REAL AGGREGATIONS ONLY
            $summary_query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('dr.reconciliation_date', $report_date);

            if (auth()->user()->role !== 'admin') {
                $summary_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $summary_query->where('s.id', $station_id);
            }

            $summary = $summary_query->select([
                DB::raw('COUNT(*) as total_tanks'),
                DB::raw('SUM(dr.total_sales_ugx) as total_sales'),
                DB::raw('SUM(dr.total_cogs_ugx) as total_cogs'),
                DB::raw('SUM(dr.gross_profit_ugx) as total_profit'),
                DB::raw('SUM(dr.total_dispensed_liters) as total_volume_sold'),
                DB::raw('AVG(ABS(dr.variance_percentage)) as avg_variance'),
                DB::raw('COUNT(CASE WHEN ABS(dr.variance_percentage) > 2.0000 THEN 1 END) as high_variance_count')
            ])->first();

            return view('reports.daily-reconciliation', compact(
                'accessible_stations', 'reconciliations', 'summary',
                'available_tanks', 'station_id', 'report_date', 'tank_id'
            ));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Weekly Summary Reports
     * 🔒 STATION-SCOPED ACCESS CONTROL - REAL FIELDS ONLY
     */
    public function weeklySummary(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();

            $station_id = $request->get('station_id');
            $week_start = $request->get('week_start', now()->startOfWeek()->format('Y-m-d'));
            $week_end = Carbon::parse($week_start)->endOfWeek()->format('Y-m-d');

            // Validate station access
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            // Build weekly summary query - ONLY REAL SCHEMA FIELDS
            $weekly_data_query = DB::table('daily_reconciliations as dr')
                ->select([
                    'dr.reconciliation_date',
                    't.fuel_type',
                    's.id as station_id',
                    's.name as station_name',
                    DB::raw('SUM(dr.total_sales_ugx) as daily_sales'),
                    DB::raw('SUM(dr.total_cogs_ugx) as daily_cogs'),
                    DB::raw('SUM(dr.gross_profit_ugx) as daily_profit'),
                    DB::raw('SUM(dr.total_dispensed_liters) as daily_volume'),
                    DB::raw('AVG(ABS(dr.variance_percentage)) as daily_avg_variance'),
                    DB::raw('COUNT(CASE WHEN ABS(dr.variance_percentage) > 2.0000 THEN 1 END) as daily_variance_alerts')
                ])
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$week_start, $week_end]);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $weekly_data_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $weekly_data_query->where('s.id', $station_id);
            }

            $weekly_data = $weekly_data_query
                ->groupBy('dr.reconciliation_date', 't.fuel_type', 's.id', 's.name')
                ->orderBy('dr.reconciliation_date')
                ->orderBy('t.fuel_type')
                ->get();

            // Get week totals - REAL AGGREGATIONS ONLY
            $week_totals_query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$week_start, $week_end]);

            if (auth()->user()->role !== 'admin') {
                $week_totals_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $week_totals_query->where('s.id', $station_id);
            }

            $week_totals = $week_totals_query->select([
                DB::raw('SUM(dr.total_sales_ugx) as total_sales'),
                DB::raw('SUM(dr.total_cogs_ugx) as total_cogs'),
                DB::raw('SUM(dr.gross_profit_ugx) as total_profit'),
                DB::raw('SUM(dr.total_dispensed_liters) as total_volume'),
                DB::raw('AVG(ABS(dr.variance_percentage)) as avg_variance'),
                DB::raw('COUNT(CASE WHEN ABS(dr.variance_percentage) > 2.0000 THEN 1 END) as total_variance_alerts'),
                DB::raw('COUNT(DISTINCT dr.reconciliation_date) as days_reported'),
                DB::raw('COUNT(DISTINCT dr.tank_id) as tanks_active')
            ])->first();

            // Get fuel type breakdown - REAL FIELDS ONLY
            $fuel_breakdown_query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$week_start, $week_end]);

            if (auth()->user()->role !== 'admin') {
                $fuel_breakdown_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $fuel_breakdown_query->where('s.id', $station_id);
            }

            $fuel_breakdown = $fuel_breakdown_query
                ->select([
                    't.fuel_type',
                    DB::raw('SUM(dr.total_sales_ugx) as fuel_sales'),
                    DB::raw('SUM(dr.total_cogs_ugx) as fuel_cogs'),
                    DB::raw('SUM(dr.gross_profit_ugx) as fuel_profit'),
                    DB::raw('SUM(dr.total_dispensed_liters) as fuel_volume'),
                    DB::raw('AVG(dr.profit_margin_percentage) as avg_margin')
                ])
                ->groupBy('t.fuel_type')
                ->orderBy('t.fuel_type')
                ->get();

            return view('reports.weekly-summary', compact(
                'accessible_stations', 'weekly_data', 'week_totals', 'fuel_breakdown',
                'station_id', 'week_start', 'week_end'
            ));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Monthly Summary Reports with Deep Insights
     * 🔒 STATION-SCOPED ACCESS CONTROL - REAL FIELDS ONLY
     */
    public function monthlySummary(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();

            $station_id = $request->get('station_id');
            $month = $request->get('month', now()->format('Y-m'));
            $month_start = Carbon::parse($month . '-01')->startOfMonth()->format('Y-m-d');
            $month_end = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');

            // Validate station access
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            // Get monthly totals - ONLY REAL SCHEMA FIELDS
            $monthly_totals_query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$month_start, $month_end]);

            if (auth()->user()->role !== 'admin') {
                $monthly_totals_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $monthly_totals_query->where('s.id', $station_id);
            }

            $monthly_totals = $monthly_totals_query->select([
                DB::raw('SUM(dr.total_sales_ugx) as total_sales'),
                DB::raw('SUM(dr.total_cogs_ugx) as total_cogs'),
                DB::raw('SUM(dr.gross_profit_ugx) as total_profit'),
                DB::raw('SUM(dr.total_dispensed_liters) as total_volume'),
                DB::raw('SUM(dr.total_delivered_liters) as total_deliveries'),
                DB::raw('AVG(ABS(dr.variance_percentage)) as avg_variance'),
                DB::raw('MAX(ABS(dr.variance_percentage)) as max_variance'),
                DB::raw('COUNT(CASE WHEN ABS(dr.variance_percentage) > 2.0000 THEN 1 END) as variance_alerts'),
                DB::raw('COUNT(DISTINCT dr.reconciliation_date) as days_reported'),
                DB::raw('COUNT(DISTINCT dr.tank_id) as tanks_active'),
                DB::raw('COUNT(*) as total_reconciliations')
            ])->first();

            // Get daily performance trend - REAL FIELDS ONLY
            $daily_trend_query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$month_start, $month_end]);

            if (auth()->user()->role !== 'admin') {
                $daily_trend_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $daily_trend_query->where('s.id', $station_id);
            }

            $daily_trend = $daily_trend_query
                ->select([
                    'dr.reconciliation_date',
                    DB::raw('SUM(dr.total_sales_ugx) as daily_sales'),
                    DB::raw('SUM(dr.gross_profit_ugx) as daily_profit'),
                    DB::raw('SUM(dr.total_dispensed_liters) as daily_volume'),
                    DB::raw('AVG(ABS(dr.variance_percentage)) as daily_variance')
                ])
                ->groupBy('dr.reconciliation_date')
                ->orderBy('dr.reconciliation_date')
                ->get();

            // Get fuel type performance - REAL FIELDS ONLY
            $fuel_performance_query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$month_start, $month_end]);

            if (auth()->user()->role !== 'admin') {
                $fuel_performance_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $fuel_performance_query->where('s.id', $station_id);
            }

            $fuel_performance = $fuel_performance_query
                ->select([
                    't.fuel_type',
                    DB::raw('SUM(dr.total_sales_ugx) as fuel_sales'),
                    DB::raw('SUM(dr.total_cogs_ugx) as fuel_cogs'),
                    DB::raw('SUM(dr.gross_profit_ugx) as fuel_profit'),
                    DB::raw('SUM(dr.total_dispensed_liters) as fuel_volume'),
                    DB::raw('AVG(dr.profit_margin_percentage) as avg_margin'),
                    DB::raw('AVG(ABS(dr.variance_percentage)) as avg_variance'),
                    DB::raw('COUNT(CASE WHEN ABS(dr.variance_percentage) > 2.0000 THEN 1 END) as variance_incidents')
                ])
                ->groupBy('t.fuel_type')
                ->orderBy('fuel_sales', 'desc')
                ->get();

            // Get tank-level insights - REAL FIELDS ONLY
            $tank_insights_query = DB::table('daily_reconciliations as dr')
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('dr.reconciliation_date', [$month_start, $month_end]);

            if (auth()->user()->role !== 'admin') {
                $tank_insights_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $tank_insights_query->where('s.id', $station_id);
            }

            $tank_insights = $tank_insights_query
                ->select([
                    't.id as tank_id',
                    't.tank_number',
                    't.fuel_type',
                    's.name as station_name',
                    DB::raw('SUM(dr.total_sales_ugx) as tank_sales'),
                    DB::raw('SUM(dr.gross_profit_ugx) as tank_profit'),
                    DB::raw('SUM(dr.total_dispensed_liters) as tank_volume'),
                    DB::raw('AVG(dr.profit_margin_percentage) as avg_margin'),
                    DB::raw('AVG(ABS(dr.variance_percentage)) as avg_variance'),
                    DB::raw('MAX(ABS(dr.variance_percentage)) as max_variance'),
                    DB::raw('COUNT(CASE WHEN ABS(dr.variance_percentage) > 2.0000 THEN 1 END) as variance_count'),
                    DB::raw('COUNT(*) as reconciliation_days')
                ])
                ->groupBy('t.id', 't.tank_number', 't.fuel_type', 's.name')
                ->orderBy('tank_sales', 'desc')
                ->get();

            // Get delivery insights - REAL DELIVERIES TABLE FIELDS
            $delivery_insights_query = DB::table('deliveries as d')
                ->join('tanks as t', 'd.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->whereBetween('d.delivery_date', [$month_start, $month_end]);

            if (auth()->user()->role !== 'admin') {
                $delivery_insights_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $delivery_insights_query->where('s.id', $station_id);
            }

            $delivery_insights = $delivery_insights_query
                ->select([
                    DB::raw('COUNT(*) as total_deliveries'),
                    DB::raw('SUM(d.volume_liters) as total_delivered_volume'),
                    DB::raw('SUM(d.total_cost_ugx) as total_delivery_cost'),
                    DB::raw('AVG(d.cost_per_liter_ugx) as avg_cost_per_liter'),
                    DB::raw('COUNT(DISTINCT d.supplier_name) as unique_suppliers'),
                    't.fuel_type'
                ])
                ->groupBy('t.fuel_type')
                ->orderBy('t.fuel_type')
                ->get();

            // Get variance analysis - REAL NOTIFICATIONS TABLE FIELDS
            $variance_analysis_query = DB::table('notifications as n')
                ->join('tanks as t', 'n.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('n.notification_type', 'volume_variance')
                ->whereBetween('n.notification_date', [$month_start, $month_end]);

            if (auth()->user()->role !== 'admin') {
                $variance_analysis_query->where('s.id', auth()->user()->station_id);
            }

            if ($station_id) {
                $variance_analysis_query->where('s.id', $station_id);
            }

            $variance_analysis = $variance_analysis_query
                ->select([
                    'n.severity',
                    DB::raw('COUNT(*) as alert_count'),
                    DB::raw('AVG(n.variance_percentage) as avg_variance_pct'),
                    DB::raw('MAX(n.variance_percentage) as max_variance_pct'),
                    DB::raw('SUM(n.variance_magnitude) as total_variance_liters')
                ])
                ->groupBy('n.severity')
                ->orderByRaw("FIELD(n.severity, 'low', 'medium', 'high', 'critical')")
                ->get();

            return view('reports.monthly-summary', compact(
                'accessible_stations', 'monthly_totals', 'daily_trend', 'fuel_performance',
                'tank_insights', 'delivery_insights', 'variance_analysis',
                'station_id', 'month', 'month_start', 'month_end'
            ));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
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
}
