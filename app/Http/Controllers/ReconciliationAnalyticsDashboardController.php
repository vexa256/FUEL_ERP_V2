<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * FUEL_ERP_V2 Reconciliation Analytics Dashboard Controller
 *
 * SCHEMA COMPLIANT: Uses actual database views and tables
 * SERVICE LAYER: Leverages existing views for optimized data retrieval
 * FILTERING: Station, Month, Year based filtering
 */
class ReconciliationAnalyticsDashboardController extends Controller
{
    /**
     * Display reconciliation analytics dashboard
     */
    public function index(Request $request)
    {
        try {
            // Get filter parameters
            $stationId = $request->get('station_id');
            $month = $request->get('month', date('n')); // Current month
            $year = $request->get('year', date('Y'));   // Current year

            // Get stations for filter dropdown
            $stations = DB::table('stations')
                ->select('id', 'name', 'location')
                ->orderBy('name')
                ->get();

            // Build analytics data
            $analytics = $this->buildAnalyticsData($stationId, $month, $year);

            return view('reports.reconciliation-analytics-dashboard', compact(
                'analytics',
                'stations',
                'stationId',
                'month',
                'year'
            ));

        } catch (Exception $e) {
            return back()->withError('Analytics loading failed: ' . $e->getMessage());
        }
    }

    /**
     * Get reconciliation analytics data
     */
    private function buildAnalyticsData(?int $stationId, int $month, int $year): array
    {
        // Monthly Summary using actual schema view
        $monthlySummary = $this->getMonthlySummary($stationId, $month, $year);

        // Daily Performance using actual schema view
        $dailyPerformance = $this->getDailyPerformance($stationId, $month, $year);

        // Tank Performance using actual reconciliation table
        $tankPerformance = $this->getTankPerformance($stationId, $month, $year);

        // Variance Analysis using actual reconciliation data
        $varianceAnalysis = $this->getVarianceAnalysis($stationId, $month, $year);

        // Financial Overview using actual reconciliation data
        $financialOverview = $this->getFinancialOverview($stationId, $month, $year);

        return [
            'monthly_summary' => $monthlySummary,
            'daily_performance' => $dailyPerformance,
            'tank_performance' => $tankPerformance,
            'variance_analysis' => $varianceAnalysis,
            'financial_overview' => $financialOverview,
            'filter_info' => [
                'station_id' => $stationId,
                'month' => $month,
                'year' => $year,
                'month_name' => Carbon::create($year, $month, 1)->format('F Y')
            ]
        ];
    }

    /**
     * Get monthly summary using actual daily_reconciliations table
     */
    private function getMonthlySummary(?int $stationId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $query = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dr.reconciliation_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                's.id as station_id',
                's.name as station_name',
                DB::raw('SUM(dr.total_sales_ugx) as monthly_sales_ugx'),
                DB::raw('SUM(dr.gross_profit_ugx) as monthly_profit_ugx'),
                DB::raw('AVG(dr.profit_margin_percentage) as avg_margin_percentage'),
                DB::raw('SUM(dr.total_dispensed_liters) as monthly_volume_sold'),
                DB::raw('COUNT(DISTINCT dr.reconciliation_date) as operating_days'),
                DB::raw('SUM(CASE WHEN ABS(dr.variance_percentage) > 5.0 THEN 1 ELSE 0 END) as monthly_variance_incidents'),
                DB::raw('MAX(dr.total_sales_ugx) as peak_day_sales_ugx'),
                DB::raw('MIN(dr.total_sales_ugx) as lowest_day_sales_ugx'),
                DB::raw('NULL as monthly_growth_percentage') // No previous data calculation for now
            )
            ->groupBy('s.id', 's.name')
            ->orderBy('s.name');

        if ($stationId) {
            $query->where('s.id', $stationId);
        }

        $results = $query->get();

        return [
            'data' => $results,
            'totals' => [
                'monthly_sales_ugx' => $results->sum('monthly_sales_ugx'),
                'monthly_profit_ugx' => $results->sum('monthly_profit_ugx'),
                'monthly_volume_sold' => $results->sum('monthly_volume_sold'),
                'operating_days' => $results->max('operating_days'),
                'monthly_variance_incidents' => $results->sum('monthly_variance_incidents')
            ]
        ];
    }

    /**
     * Get daily performance using actual daily_reconciliations table
     */
    private function getDailyPerformance(?int $stationId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $query = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dr.reconciliation_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                's.id as station_id',
                's.name as station_name',
                'dr.reconciliation_date',
                DB::raw('SUM(dr.total_sales_ugx) as total_sales_ugx'),
                DB::raw('SUM(dr.total_cogs_ugx) as total_cogs_ugx'),
                DB::raw('SUM(dr.gross_profit_ugx) as gross_profit_ugx'),
                DB::raw('AVG(dr.profit_margin_percentage) as profit_margin_percentage'),
                DB::raw('SUM(dr.total_dispensed_liters) as total_volume_sold_liters'),
                DB::raw('SUM(dr.total_delivered_liters) as total_volume_delivered_liters'),
                DB::raw('COUNT(dr.id) as tanks_reconciled'),
                DB::raw('SUM(CASE WHEN ABS(dr.variance_percentage) > 2.0 THEN 1 ELSE 0 END) as tanks_with_variance_alerts'),
                DB::raw('AVG(ABS(dr.variance_percentage)) as average_variance_percentage')
            )
            ->groupBy('s.id', 's.name', 'dr.reconciliation_date')
            ->orderBy('dr.reconciliation_date', 'desc')
            ->orderBy('s.name');

        if ($stationId) {
            $query->where('s.id', $stationId);
        }

        $results = $query->get();

        return [
            'data' => $results,
            'summary' => [
                'total_days' => $results->count(),
                'avg_daily_sales' => $results->avg('total_sales_ugx'),
                'avg_daily_profit' => $results->avg('gross_profit_ugx'),
                'avg_margin_pct' => $results->avg('profit_margin_percentage'),
                'total_variance_alerts' => $results->sum('tanks_with_variance_alerts')
            ]
        ];
    }

    /**
     * Get tank performance using actual daily_reconciliations table
     */
    private function getTankPerformance(?int $stationId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $query = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dr.reconciliation_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                's.name as station_name',
                't.tank_number',
                't.fuel_type',
                DB::raw('COUNT(dr.id) as reconciliation_days'),
                DB::raw('SUM(dr.total_sales_ugx) as total_sales'),
                DB::raw('SUM(dr.gross_profit_ugx) as total_profit'),
                DB::raw('AVG(dr.profit_margin_percentage) as avg_margin_pct'),
                DB::raw('SUM(dr.total_dispensed_liters) as total_volume_sold'),
                DB::raw('AVG(ABS(dr.variance_percentage)) as avg_variance_pct'),
                DB::raw('SUM(CASE WHEN ABS(dr.variance_percentage) > 5.0 THEN 1 ELSE 0 END) as high_variance_days')
            )
            ->groupBy('s.id', 's.name', 't.id', 't.tank_number', 't.fuel_type')
            ->orderBy('s.name')
            ->orderBy('t.tank_number');

        if ($stationId) {
            $query->where('s.id', $stationId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get variance analysis using actual daily_reconciliations table
     */
    private function getVarianceAnalysis(?int $stationId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $query = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dr.reconciliation_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                's.name as station_name',
                't.fuel_type',
                DB::raw('COUNT(dr.id) as total_reconciliations'),
                DB::raw('AVG(dr.variance_percentage) as avg_variance_pct'),
                DB::raw('AVG(ABS(dr.variance_percentage)) as avg_abs_variance_pct'),
                DB::raw('MIN(dr.variance_percentage) as min_variance_pct'),
                DB::raw('MAX(dr.variance_percentage) as max_variance_pct'),
                DB::raw('SUM(CASE WHEN ABS(dr.variance_percentage) < 2.0 THEN 1 ELSE 0 END) as low_variance_days'),
                DB::raw('SUM(CASE WHEN ABS(dr.variance_percentage) BETWEEN 2.0 AND 5.0 THEN 1 ELSE 0 END) as medium_variance_days'),
                DB::raw('SUM(CASE WHEN ABS(dr.variance_percentage) > 5.0 THEN 1 ELSE 0 END) as high_variance_days'),
                DB::raw('SUM(ABS(dr.volume_variance_liters)) as total_variance_volume')
            )
            ->groupBy('s.id', 's.name', 't.fuel_type')
            ->orderBy('s.name')
            ->orderBy('t.fuel_type');

        if ($stationId) {
            $query->where('s.id', $stationId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get financial overview using actual daily_reconciliations table
     */
    private function getFinancialOverview(?int $stationId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $query = DB::table('daily_reconciliations as dr')
            ->join('tanks as t', 'dr.tank_id', '=', 't.id')
            ->join('stations as s', 't.station_id', '=', 's.id')
            ->whereBetween('dr.reconciliation_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                's.name as station_name',
                DB::raw('SUM(dr.total_sales_ugx) as total_revenue'),
                DB::raw('SUM(dr.total_cogs_ugx) as total_cogs'),
                DB::raw('SUM(dr.gross_profit_ugx) as total_profit'),
                DB::raw('AVG(dr.profit_margin_percentage) as avg_margin_pct'),
                DB::raw('SUM(dr.total_dispensed_liters) as total_volume'),
                DB::raw('SUM(dr.total_sales_ugx) / NULLIF(SUM(dr.total_dispensed_liters), 0) as avg_price_per_liter'),
                DB::raw('SUM(dr.total_cogs_ugx) / NULLIF(SUM(dr.total_dispensed_liters), 0) as avg_cost_per_liter'),
                DB::raw('COUNT(DISTINCT dr.reconciliation_date) as operating_days')
            )
            ->groupBy('s.id', 's.name')
            ->orderBy('total_revenue', 'desc');

        if ($stationId) {
            $query->where('s.id', $stationId);
        }

        return $query->get()->toArray();
    }

    /**
     * Export analytics data to CSV
     */
    public function export(Request $request)
    {
        try {
            $stationId = $request->get('station_id');
            $month = $request->get('month', date('n'));
            $year = $request->get('year', date('Y'));

            $analytics = $this->buildAnalyticsData($stationId, $month, $year);

            $filename = sprintf(
                'reconciliation_analytics_%s_%s_%s.csv',
                $stationId ? "station_{$stationId}" : 'all_stations',
                $month,
                $year
            );

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            return response()->stream(function() use ($analytics) {
                $handle = fopen('php://output', 'w');

                // Monthly Summary
                fputcsv($handle, ['MONTHLY SUMMARY']);
                fputcsv($handle, ['Station', 'Sales (UGX)', 'Profit (UGX)', 'Margin %', 'Volume (L)', 'Variance Incidents']);
                foreach ($analytics['monthly_summary']['data'] as $row) {
                    fputcsv($handle, [
                        $row->station_name,
                        number_format($row->monthly_sales_ugx, 0),
                        number_format($row->monthly_profit_ugx, 0),
                        number_format($row->avg_margin_percentage, 2),
                        number_format($row->monthly_volume_sold, 0),
                        $row->monthly_variance_incidents
                    ]);
                }

                fputcsv($handle, []); // Empty line

                // Tank Performance
                fputcsv($handle, ['TANK PERFORMANCE']);
                fputcsv($handle, ['Station', 'Tank', 'Fuel Type', 'Days', 'Sales (UGX)', 'Profit (UGX)', 'Margin %', 'Volume (L)', 'Avg Variance %']);
                foreach ($analytics['tank_performance'] as $tank) {
                    fputcsv($handle, [
                        $tank->station_name,
                        $tank->tank_number,
                        $tank->fuel_type,
                        $tank->reconciliation_days,
                        number_format($tank->total_sales, 0),
                        number_format($tank->total_profit, 0),
                        number_format($tank->avg_margin_pct, 2),
                        number_format($tank->total_volume_sold, 0),
                        number_format($tank->avg_variance_pct, 4)
                    ]);
                }

                fclose($handle);
            }, 200, $headers);

        } catch (Exception $e) {
            return back()->withError('Export failed: ' . $e->getMessage());
        }
    }
}
