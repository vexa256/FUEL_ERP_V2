<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricingController extends Controller
{
    /**
     * Pricing analysis dashboard with strict station access control
     * 🔒 ENFORCES STATION-LEVEL ACCESS CONTROL
     */
    public function index(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();
            $station_id = $request->get('station_id');

            // Validate station access
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            // Current pricing analysis with station filtering
            $current_pricing_query = DB::table('selling_prices as sp')
                ->select([
                    'sp.fuel_type',
                    'sp.price_per_liter_ugx',
                    'sp.effective_from_date',
                    'sp.station_id',
                    's.name as station_name',
                    's.currency_code',
                    DB::raw('(SELECT AVG(fl.cost_per_liter_ugx)
                             FROM fifo_layers fl
                             JOIN tanks t ON fl.tank_id = t.id
                             WHERE t.station_id = sp.station_id
                             AND t.fuel_type = sp.fuel_type
                             AND fl.is_exhausted = 0) as avg_cost_per_liter'),
                    DB::raw('(sp.price_per_liter_ugx - (SELECT AVG(fl.cost_per_liter_ugx)
                             FROM fifo_layers fl
                             JOIN tanks t ON fl.tank_id = t.id
                             WHERE t.station_id = sp.station_id
                             AND t.fuel_type = sp.fuel_type
                             AND fl.is_exhausted = 0)) as margin_per_liter'),
                    DB::raw('CASE WHEN (SELECT AVG(fl.cost_per_liter_ugx)
                             FROM fifo_layers fl
                             JOIN tanks t ON fl.tank_id = t.id
                             WHERE t.station_id = sp.station_id
                             AND t.fuel_type = sp.fuel_type
                             AND fl.is_exhausted = 0) > 0
                             THEN ((sp.price_per_liter_ugx - (SELECT AVG(fl.cost_per_liter_ugx)
                                   FROM fifo_layers fl
                                   JOIN tanks t ON fl.tank_id = t.id
                                   WHERE t.station_id = sp.station_id
                                   AND t.fuel_type = sp.fuel_type
                                   AND fl.is_exhausted = 0)) /
                                   (SELECT AVG(fl.cost_per_liter_ugx)
                                   FROM fifo_layers fl
                                   JOIN tanks t ON fl.tank_id = t.id
                                   WHERE t.station_id = sp.station_id
                                   AND t.fuel_type = sp.fuel_type
                                   AND fl.is_exhausted = 0)) * 100
                             ELSE 0 END as margin_percentage'),
                    DB::raw('(SELECT SUM(t.current_volume_liters)
                             FROM tanks t
                             WHERE t.station_id = sp.station_id
                             AND t.fuel_type = sp.fuel_type) as current_stock_liters')
                ])
                ->join('stations as s', 'sp.station_id', '=', 's.id')
                ->where('sp.is_active', 1);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $current_pricing_query->where('sp.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $current_pricing_query->where('sp.station_id', $station_id);
            }

            $current_pricing = $current_pricing_query->orderBy('s.name')->orderBy('sp.fuel_type')->get();

            // Recent sales performance with station filtering
            $sales_performance_query = DB::table('daily_reconciliations as dr')
                ->select([
                    't.fuel_type',
                    's.name as station_name',
                    's.id as station_id',
                    DB::raw('SUM(dr.total_sales_ugx) as total_sales'),
                    DB::raw('SUM(dr.total_cogs_ugx) as total_cogs'),
                    DB::raw('SUM(dr.gross_profit_ugx) as gross_profit'),
                    DB::raw('AVG(dr.profit_margin_percentage) as avg_margin'),
                    DB::raw('SUM(dr.total_dispensed_liters) as total_volume'),
                    DB::raw('COUNT(*) as transaction_days')
                ])
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('dr.reconciliation_date', '>=', now()->subDays(30));

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $sales_performance_query->where('t.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $sales_performance_query->where('t.station_id', $station_id);
            }

            $sales_performance = $sales_performance_query
                ->groupBy('t.fuel_type', 's.name', 's.id')
                ->orderBy('s.name')
                ->orderBy('t.fuel_type')
                ->get();

            // Price change impact summary with station filtering
            $price_changes_query = DB::table('price_change_log as pcl')
                ->select([
                    'pcl.*',
                    's.name as station_name'
                ])
                ->join('stations as s', 'pcl.station_id', '=', 's.id')
                ->where('pcl.created_at', '>=', now()->subDays(30));

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $price_changes_query->where('pcl.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $price_changes_query->where('pcl.station_id', $station_id);
            }

            $recent_price_changes = $price_changes_query
                ->orderBy('pcl.created_at', 'desc')
                ->limit(10)
                ->get();

            // Summary statistics with station filtering
            $summary_query = DB::table('selling_prices as sp')
                ->join('stations as s', 'sp.station_id', '=', 's.id')
                ->select([
                    DB::raw('COUNT(DISTINCT sp.station_id) as stations_count'),
                    DB::raw('COUNT(*) as active_prices'),
                    DB::raw('AVG(sp.price_per_liter_ugx) as avg_price'),
                    DB::raw('MIN(sp.price_per_liter_ugx) as min_price'),
                    DB::raw('MAX(sp.price_per_liter_ugx) as max_price')
                ])
                ->where('sp.is_active', 1);

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $summary_query->where('sp.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $summary_query->where('sp.station_id', $station_id);
            }

            $summary_stats = $summary_query->first();

            return view('pricing.index', compact(
                'accessible_stations', 'current_pricing', 'sales_performance',
                'recent_price_changes', 'summary_stats', 'station_id'
            ));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get profit analysis data with station access control
     * 🔒 STATION-SCOPED AJAX ENDPOINT
     */
    public function getProfitAnalysis(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();
            $station_id = $request->get('station_id');
            $days = $request->get('days', 30);

            // Validate station access
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return response()->json(['error' => 'Access denied to selected station'], 403);
            }

            $query = DB::table('daily_reconciliations as dr')
                ->select([
                    't.fuel_type',
                    's.name as station_name',
                    'dr.reconciliation_date',
                    'dr.total_sales_ugx',
                    'dr.total_cogs_ugx',
                    'dr.gross_profit_ugx',
                    'dr.profit_margin_percentage',
                    'dr.total_dispensed_liters'
                ])
                ->join('tanks as t', 'dr.tank_id', '=', 't.id')
                ->join('stations as s', 't.station_id', '=', 's.id')
                ->where('dr.reconciliation_date', '>=', now()->subDays($days));

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $query->where('t.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $query->where('t.station_id', $station_id);
            }

            $profit_data = $query->orderBy('dr.reconciliation_date', 'desc')->get();

            return response()->json($profit_data);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get price history data with station access control
     * 🔒 STATION-SCOPED AJAX ENDPOINT
     */
    public function getPriceHistory(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();
            $station_id = $request->get('station_id');
            $fuel_type = $request->get('fuel_type');

            // Validate station access
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return response()->json(['error' => 'Access denied to selected station'], 403);
            }

            $query = DB::table('price_change_log as pcl')
                ->select([
                    'pcl.*',
                    's.name as station_name',
                    'u.first_name',
                    'u.last_name'
                ])
                ->join('stations as s', 'pcl.station_id', '=', 's.id')
                ->join('users as u', 'pcl.changed_by_user_id', '=', 'u.id');

            // Apply station access control
            if (auth()->user()->role !== 'admin') {
                $query->where('pcl.station_id', auth()->user()->station_id);
            }

            if ($station_id) {
                $query->where('pcl.station_id', $station_id);
            }

            if ($fuel_type) {
                $query->where('pcl.fuel_type', $fuel_type);
            }

            $price_history = $query->orderBy('pcl.created_at', 'desc')->limit(50)->get();

            return response()->json($price_history);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
}
