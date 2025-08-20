<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RTTAnalyticsController extends Controller
{
    /**
     * RTT Analytics Dashboard
     */
    public function index(Request $request)
    {
        try {
            $accessible_stations = $this->getUserAccessibleStations();
            $station_id = $request->get('station_id');
            $date_from = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $date_to = $request->get('date_to', now()->format('Y-m-d'));

            // Validate station access
            if ($station_id && !$accessible_stations->contains('id', $station_id)) {
                return back()->with('error', 'Access denied to selected station');
            }

            // RTT Operations Summary
            $rtt_summary = $this->getRTTSummary($station_id, $date_from, $date_to);

            // RTT Operations History
            $rtt_operations = $this->getRTTOperations($station_id, $date_from, $date_to);

            // Overflow Processing Analytics
            $overflow_analytics = $this->getOverflowAnalytics($station_id, $date_from, $date_to);

            // Tank Efficiency Metrics
            $tank_efficiency = $this->getTankEfficiencyMetrics($station_id, $date_from, $date_to);

            // Monthly RTT Trends
            $monthly_trends = $this->getMonthlyRTTTrends($station_id);

            // Financial Impact Analysis
            $financial_impact = $this->getFinancialImpact($station_id, $date_from, $date_to);

            return view('deliveries.rttanalytics', compact(
                'accessible_stations',
                'station_id',
                'date_from',
                'date_to',
                'rtt_summary',
                'rtt_operations',
                'overflow_analytics',
                'tank_efficiency',
                'monthly_trends',
                'financial_impact'
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Error loading RTT analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get RTT Operations Summary
     */
    private function getRTTSummary($station_id, $date_from, $date_to): array
    {
        $query = DB::table('deliveries as d')
            ->join('tanks as t', 'd.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->where('d.supplier_name', 'like', 'RTT-%')
            ->whereBetween('d.delivery_date', [$date_from, $date_to]);

        if (auth()->user()->role !== 'admin') {
            $query->where('s.id', auth()->user()->station_id);
        }

        if ($station_id) {
            $query->where('s.id', $station_id);
        }

        $summary = $query->select([
            DB::raw('COUNT(*) as total_operations'),
            DB::raw('SUM(d.volume_liters) as total_volume'),
            DB::raw('SUM(d.total_cost_ugx) as total_value'),
            DB::raw('AVG(d.volume_liters) as avg_volume'),
            DB::raw('COUNT(DISTINCT d.tank_id) as tanks_involved'),
            DB::raw('COUNT(DISTINCT DATE(d.delivery_date)) as active_days')
        ])->first();

        return [
            'total_operations' => (int) ($summary->total_operations ?? 0),
            'total_volume' => (float) ($summary->total_volume ?? 0),
            'total_value' => (float) ($summary->total_value ?? 0),
            'avg_volume' => (float) ($summary->avg_volume ?? 0),
            'tanks_involved' => (int) ($summary->tanks_involved ?? 0),
            'active_days' => (int) ($summary->active_days ?? 0)
        ];
    }

    /**
     * Get RTT Operations History
     */
    private function getRTTOperations($station_id, $date_from, $date_to)
    {
        $query = DB::table('deliveries as d')
            ->select([
                'd.id',
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
                's.name as station_name',
                's.currency_code',
                'u.first_name',
                'u.last_name',
                DB::raw('SUBSTRING_INDEX(d.supplier_name, "-", -1) as original_supplier'),
                DB::raw('CASE
                    WHEN d.invoice_number LIKE "%RTT%" THEN SUBSTRING_INDEX(SUBSTRING_INDEX(d.invoice_number, "RTT-", -1), "-", 1)
                    ELSE NULL
                END as source_overflow_ref')
            ])
            ->join('tanks as t', 'd.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->join('users as u', 'd.user_id', '=', 'u.id')
            ->where('d.supplier_name', 'like', 'RTT-%')
            ->whereBetween('d.delivery_date', [$date_from, $date_to]);

        if (auth()->user()->role !== 'admin') {
            $query->where('s.id', auth()->user()->station_id);
        }

        if ($station_id) {
            $query->where('s.id', $station_id);
        }

        return $query->orderBy('d.delivery_date', 'desc')
                     ->orderBy('d.delivery_time', 'desc')
                     ->paginate(15)
                     ->withQueryString();
    }

    /**
     * Get Overflow Processing Analytics
     */
    private function getOverflowAnalytics($station_id, $date_from, $date_to): array
    {
        $query = DB::table('delivery_overflow_storage as dos')
            ->join('tanks as t', 'dos.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dos.overflow_date', [$date_from, $date_to]);

        if (auth()->user()->role !== 'admin') {
            $query->where('s.id', auth()->user()->station_id);
        }

        if ($station_id) {
            $query->where('s.id', $station_id);
        }

        // Overall overflow statistics
        $overflow_stats = $query->select([
            DB::raw('COUNT(*) as total_overflow_records'),
            DB::raw('SUM(dos.overflow_volume_liters) as total_overflow_created'),
            DB::raw('SUM(dos.remaining_volume_liters) as total_remaining'),
            DB::raw('SUM(dos.overflow_volume_liters - dos.remaining_volume_liters) as total_processed'),
            DB::raw('SUM(dos.remaining_value_ugx) as total_remaining_value'),
            DB::raw('AVG(dos.overflow_volume_liters) as avg_overflow_size'),
            DB::raw('COUNT(DISTINCT dos.tank_id) as tanks_with_overflow')
        ])->first();

        // Processing efficiency
        $processing_efficiency = DB::table('delivery_overflow_storage as dos')
            ->join('tanks as t', 'dos.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dos.overflow_date', [$date_from, $date_to])
            ->when(auth()->user()->role !== 'admin', function($q) {
                return $q->where('s.id', auth()->user()->station_id);
            })
            ->when($station_id, function($q) use ($station_id) {
                return $q->where('s.id', $station_id);
            })
            ->select([
                DB::raw('COUNT(CASE WHEN dos.is_exhausted = 1 THEN 1 END) as fully_processed'),
                DB::raw('COUNT(CASE WHEN dos.remaining_volume_liters > 0 AND dos.remaining_volume_liters < dos.overflow_volume_liters THEN 1 END) as partially_processed'),
                DB::raw('COUNT(CASE WHEN dos.remaining_volume_liters = dos.overflow_volume_liters THEN 1 END) as unprocessed'),
                DB::raw('AVG(DATEDIFF(CURDATE(), dos.overflow_date)) as avg_processing_days')
            ])->first();

        return [
            'total_overflow_records' => (int) ($overflow_stats->total_overflow_records ?? 0),
            'total_overflow_created' => (float) ($overflow_stats->total_overflow_created ?? 0),
            'total_remaining' => (float) ($overflow_stats->total_remaining ?? 0),
            'total_processed' => (float) ($overflow_stats->total_processed ?? 0),
            'total_remaining_value' => (float) ($overflow_stats->total_remaining_value ?? 0),
            'avg_overflow_size' => (float) ($overflow_stats->avg_overflow_size ?? 0),
            'tanks_with_overflow' => (int) ($overflow_stats->tanks_with_overflow ?? 0),
            'fully_processed' => (int) ($processing_efficiency->fully_processed ?? 0),
            'partially_processed' => (int) ($processing_efficiency->partially_processed ?? 0),
            'unprocessed' => (int) ($processing_efficiency->unprocessed ?? 0),
            'avg_processing_days' => (float) ($processing_efficiency->avg_processing_days ?? 0),
            'processing_rate' => $overflow_stats->total_overflow_created > 0 ?
                (($overflow_stats->total_processed / $overflow_stats->total_overflow_created) * 100) : 0
        ];
    }

    /**
     * Get Tank Efficiency Metrics
     */
    private function getTankEfficiencyMetrics($station_id, $date_from, $date_to)
    {
        $query = DB::table('tanks as t')
            ->select([
                't.id',
                't.tank_number',
                't.fuel_type',
                't.capacity_liters',
                't.current_volume_liters',
                's.name as station_name',
                DB::raw('(t.capacity_liters - t.current_volume_liters) as available_space'),
                DB::raw('ROUND((t.current_volume_liters / t.capacity_liters) * 100, 2) as fill_percentage'),
                DB::raw('COUNT(DISTINCT d.id) as total_deliveries'),
                DB::raw('SUM(CASE WHEN d.supplier_name LIKE "RTT-%" THEN d.volume_liters ELSE 0 END) as rtt_volume'),
                DB::raw('SUM(CASE WHEN d.supplier_name NOT LIKE "RTT-%" THEN d.volume_liters ELSE 0 END) as direct_delivery_volume'),
                DB::raw('COUNT(CASE WHEN d.supplier_name LIKE "RTT-%" THEN 1 END) as rtt_operations'),
                DB::raw('COALESCE(SUM(dos.remaining_volume_liters), 0) as current_overflow'),
                DB::raw('COUNT(dos.id) as overflow_records')
            ])
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->leftJoin('deliveries as d', function($join) use ($date_from, $date_to) {
                $join->on('t.id', '=', 'd.tank_id')
                     ->whereBetween('d.delivery_date', [$date_from, $date_to]);
            })
            ->leftJoin('delivery_overflow_storage as dos', function($join) {
                $join->on('t.id', '=', 'dos.tank_id')
                     ->where('dos.is_exhausted', false)
                     ->where('dos.remaining_volume_liters', '>', 0);
            });

        if (auth()->user()->role !== 'admin') {
            $query->where('s.id', auth()->user()->station_id);
        }

        if ($station_id) {
            $query->where('s.id', $station_id);
        }

        return $query->groupBy('t.id', 't.tank_number', 't.fuel_type', 't.capacity_liters', 't.current_volume_liters', 's.name')
                     ->orderBy('s.name')
                     ->orderBy('t.tank_number')
                     ->get();
    }

    /**
     * Get Monthly RTT Trends
     */
    private function getMonthlyRTTTrends($station_id): array
    {
        $query = DB::table('deliveries as d')
            ->select([
                DB::raw('DATE_FORMAT(d.delivery_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as operations'),
                DB::raw('SUM(d.volume_liters) as volume'),
                DB::raw('SUM(d.total_cost_ugx) as value'),
                DB::raw('AVG(d.volume_liters) as avg_volume')
            ])
            ->join('tanks as t', 'd.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->where('d.supplier_name', 'like', 'RTT-%')
            ->where('d.delivery_date', '>=', now()->subMonths(12)->startOfMonth());

        if (auth()->user()->role !== 'admin') {
            $query->where('s.id', auth()->user()->station_id);
        }

        if ($station_id) {
            $query->where('s.id', $station_id);
        }

        $trends = $query->groupBy(DB::raw('DATE_FORMAT(d.delivery_date, "%Y-%m")'))
                        ->orderBy('month')
                        ->get();

        return $trends->map(function($trend) {
            return [
                'month' => $trend->month,
                'month_name' => Carbon::createFromFormat('Y-m', $trend->month)->format('M Y'),
                'operations' => (int) $trend->operations,
                'volume' => (float) $trend->volume,
                'value' => (float) $trend->value,
                'avg_volume' => (float) $trend->avg_volume
            ];
        })->toArray();
    }

    /**
     * Get Financial Impact Analysis
     */
    private function getFinancialImpact($station_id, $date_from, $date_to): array
    {
        // RTT Cost Savings (avoided new overflow storage costs)
        $rtt_savings = DB::table('deliveries as d')
            ->join('tanks as t', 'd.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->where('d.supplier_name', 'like', 'RTT-%')
            ->whereBetween('d.delivery_date', [$date_from, $date_to])
            ->when(auth()->user()->role !== 'admin', function($q) {
                return $q->where('s.id', auth()->user()->station_id);
            })
            ->when($station_id, function($q) use ($station_id) {
                return $q->where('s.id', $station_id);
            })
            ->select([
                DB::raw('SUM(d.total_cost_ugx) as total_rtt_value'),
                DB::raw('SUM(d.volume_liters) as total_rtt_volume'),
                DB::raw('AVG(d.cost_per_liter_ugx) as avg_cost_per_liter')
            ])->first();

        // Current overflow value at risk
        $overflow_at_risk = DB::table('delivery_overflow_storage as dos')
            ->join('tanks as t', 'dos.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->where('dos.is_exhausted', false)
            ->where('dos.remaining_volume_liters', '>', 0)
            ->when(auth()->user()->role !== 'admin', function($q) {
                return $q->where('s.id', auth()->user()->station_id);
            })
            ->when($station_id, function($q) use ($station_id) {
                return $q->where('s.id', $station_id);
            })
            ->select([
                DB::raw('SUM(dos.remaining_value_ugx) as total_overflow_value'),
                DB::raw('SUM(dos.remaining_volume_liters) as total_overflow_volume'),
                DB::raw('COUNT(*) as overflow_records'),
                DB::raw('AVG(DATEDIFF(CURDATE(), dos.overflow_date)) as avg_age_days')
            ])->first();

        return [
            'total_rtt_value' => (float) ($rtt_savings->total_rtt_value ?? 0),
            'total_rtt_volume' => (float) ($rtt_savings->total_rtt_volume ?? 0),
            'avg_cost_per_liter' => (float) ($rtt_savings->avg_cost_per_liter ?? 0),
            'total_overflow_value' => (float) ($overflow_at_risk->total_overflow_value ?? 0),
            'total_overflow_volume' => (float) ($overflow_at_risk->total_overflow_volume ?? 0),
            'overflow_records' => (int) ($overflow_at_risk->overflow_records ?? 0),
            'avg_age_days' => (float) ($overflow_at_risk->avg_age_days ?? 0),
            'efficiency_ratio' => $rtt_savings->total_rtt_volume > 0 && $overflow_at_risk->total_overflow_volume > 0 ?
                ($rtt_savings->total_rtt_volume / ($rtt_savings->total_rtt_volume + $overflow_at_risk->total_overflow_volume)) * 100 : 0
        ];
    }

    /**
     * Get user accessible stations
     */
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
