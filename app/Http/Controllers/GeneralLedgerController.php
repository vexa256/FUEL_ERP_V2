<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FuelERP_CriticalPrecisionService;
use Carbon\Carbon;
use Exception;

/**
 * GENERAL LEDGER & TRIAL BALANCE CONTROLLER
 *
 * MATHEMATICAL PRECISION: 100% accurate to 0.0001 UGX
 * MINIMAL CODE: Maximum efficiency, zero bloat
 * SCHEMA COMPLIANT: Uses exact FUEL_ERP_V2 field names
 * ACCESS CONTROLLED: Station-level security enforcement
 */
class GeneralLedgerController extends Controller
{
    private FuelERP_CriticalPrecisionService $fuelService;

    // EXACT schema enums - NO ASSUMPTIONS
    private const ACCOUNT_TYPES = ['revenue', 'cogs', 'inventory', 'variance_loss', 'variance_gain'];
    private const FUEL_TYPES = [
        'petrol', 'diesel', 'kerosene', 'fuelsave_unleaded', 'fuelsave_diesel',
        'v_power_unleaded', 'v_power_diesel', 'ago', 'super_unleaded', 'jet_a1',
        'avgas_100ll', 'heavy_fuel_oil', 'marine_gas_oil', 'low_sulfur_diesel',
        'ultra_low_sulfur_diesel', 'lpg', 'cooking_gas', 'industrial_lpg',
        'autogas', 'household_kerosene', 'illuminating_kerosene', 'industrial_kerosene'
    ];

    // DEBIT NORMAL BALANCE accounts (increases with debits)
    private const DEBIT_ACCOUNTS = ['cogs', 'inventory', 'variance_loss'];
    // CREDIT NORMAL BALANCE accounts (increases with credits)
    private const CREDIT_ACCOUNTS = ['revenue', 'variance_gain'];

    public function __construct(FuelERP_CriticalPrecisionService $fuelService)
    {
        $this->fuelService = $fuelService;
    }

    /**
     * SINGLE UNIFIED VIEW - General Ledger & Trial Balance
     * Tabbed interface with mathematical precision
     */
    public function index(Request $request)
    {
        try {
            $stationScope = $this->enforceStationAccess();
            $filters = $this->validateAndApplyFilters($request, $stationScope);

            $data = [
                'general_ledger' => $this->getGeneralLedgerData($filters),
                'trial_balance' => $this->getTrialBalanceData($filters),
                'mathematical_validation' => $this->validateTrialBalance($filters),
                'filter_options' => $this->getFilterOptions($stationScope),
                'applied_filters' => $filters['metadata']
            ];

            if ($request->ajax()) {
                $tab = $request->input('tab');
                if ($tab && isset($data[$tab])) {
                    return response()->json(['success' => true, 'data' => $data[$tab]]);
                }
                return response()->json(['success' => true, 'data' => $data]);
            }

            return view('reports.general-ledger', compact('data'));

        } catch (Exception $e) {
            Log::error('General Ledger Failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'filters' => $request->all()
            ]);

            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            throw $e;
        }
    }

    /**
     * GENERAL LEDGER DATA - Detailed transaction listing with running balances
     * MATHEMATICAL PRECISION: Exact running balance calculations
     */
    private function getGeneralLedgerData(array $filters): array
    {
        $perPage = 100;
        $page = request()->input('gl_page', 1);

        // Get detailed ledger entries with running balances
        $ledgerQuery = DB::table('financial_ledger')
            ->join('stations', 'financial_ledger.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['account_types']), fn($q) => $q->whereIn('financial_ledger.account_type', $filters['account_types']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('financial_ledger.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), fn($q) => $q->whereBetween('financial_ledger.entry_date', [$filters['date_range']['start'], $filters['date_range']['end']]))
            ->when(!empty($filters['month_filter']), fn($q) => $q->whereMonth('financial_ledger.entry_date', $filters['month_filter']))
            ->when(!empty($filters['year_filter']), fn($q) => $q->whereYear('financial_ledger.entry_date', $filters['year_filter']))
            ->selectRaw('
                financial_ledger.id,
                financial_ledger.entry_date,
                stations.name as station_name,
                financial_ledger.account_type,
                financial_ledger.fuel_type,
                financial_ledger.debit_amount_ugx,
                financial_ledger.credit_amount_ugx,
                financial_ledger.description,
                financial_ledger.reference_table,
                financial_ledger.reference_id,
                financial_ledger.reconciliation_id,
                financial_ledger.created_at
            ')
            ->orderBy('financial_ledger.entry_date', 'DESC')
            ->orderBy('financial_ledger.created_at', 'DESC');

        $ledgerData = $ledgerQuery->paginate($perPage, ['*'], 'gl_page', $page);

        // Calculate running balances BY ACCOUNT TYPE
        $runningBalances = $this->calculateRunningBalances($filters);

        // Account summaries for quick reference
        $accountSummaries = DB::table('financial_ledger')
            ->join('stations', 'financial_ledger.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['account_types']), fn($q) => $q->whereIn('financial_ledger.account_type', $filters['account_types']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('financial_ledger.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), fn($q) => $q->whereBetween('financial_ledger.entry_date', [$filters['date_range']['start'], $filters['date_range']['end']]))
            ->when(!empty($filters['month_filter']), fn($q) => $q->whereMonth('financial_ledger.entry_date', $filters['month_filter']))
            ->when(!empty($filters['year_filter']), fn($q) => $q->whereYear('financial_ledger.entry_date', $filters['year_filter']))
            ->selectRaw('
                account_type,
                fuel_type,
                COUNT(*) as transaction_count,
                SUM(debit_amount_ugx) as total_debits,
                SUM(credit_amount_ugx) as total_credits,
                (SUM(debit_amount_ugx) - SUM(credit_amount_ugx)) as net_balance
            ')
            ->groupBy('account_type', 'fuel_type')
            ->orderBy('account_type')
            ->orderBy('fuel_type')
            ->get();

        return [
            'detailed_entries' => $ledgerData->items(),
            'pagination' => [
                'current_page' => $ledgerData->currentPage(),
                'per_page' => $ledgerData->perPage(),
                'total' => $ledgerData->total(),
                'last_page' => $ledgerData->lastPage()
            ],
            'running_balances' => $runningBalances,
            'account_summaries' => $accountSummaries->map(fn($summary) => [
                'account_type' => $summary->account_type,
                'fuel_type' => $summary->fuel_type,
                'transaction_count' => (int) $summary->transaction_count,
                'total_debits' => round((float) $summary->total_debits, 4),
                'total_credits' => round((float) $summary->total_credits, 4),
                'net_balance' => round((float) $summary->net_balance, 4),
                'normal_balance_type' => in_array($summary->account_type, self::DEBIT_ACCOUNTS) ? 'DEBIT' : 'CREDIT'
            ])->toArray()
        ];
    }

    /**
     * TRIAL BALANCE DATA - Account summaries with mathematical validation
     * ENFORCES: Total Debits = Total Credits (fundamental accounting equation)
     */
    private function getTrialBalanceData(array $filters): array
    {
        // Core trial balance calculation - MATHEMATICAL PRECISION CRITICAL
        $trialBalance = DB::table('financial_ledger')
            ->join('stations', 'financial_ledger.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['account_types']), fn($q) => $q->whereIn('financial_ledger.account_type', $filters['account_types']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('financial_ledger.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), fn($q) => $q->whereBetween('financial_ledger.entry_date', [$filters['date_range']['start'], $filters['date_range']['end']]))
            ->when(!empty($filters['month_filter']), fn($q) => $q->whereMonth('financial_ledger.entry_date', $filters['month_filter']))
            ->when(!empty($filters['year_filter']), fn($q) => $q->whereYear('financial_ledger.entry_date', $filters['year_filter']))
            ->selectRaw('
                account_type,
                fuel_type,
                SUM(debit_amount_ugx) as total_debits,
                SUM(credit_amount_ugx) as total_credits,
                (SUM(debit_amount_ugx) - SUM(credit_amount_ugx)) as account_balance,
                COUNT(*) as entry_count
            ')
            ->groupBy('account_type', 'fuel_type')
            ->orderBy('account_type')
            ->orderBy('fuel_type')
            ->get();

        // MATHEMATICAL VALIDATION - Grand totals MUST balance
        $grandTotals = DB::table('financial_ledger')
            ->join('stations', 'financial_ledger.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['account_types']), fn($q) => $q->whereIn('financial_ledger.account_type', $filters['account_types']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('financial_ledger.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), fn($q) => $q->whereBetween('financial_ledger.entry_date', [$filters['date_range']['start'], $filters['date_range']['end']]))
            ->when(!empty($filters['month_filter']), fn($q) => $q->whereMonth('financial_ledger.entry_date', $filters['month_filter']))
            ->when(!empty($filters['year_filter']), fn($q) => $q->whereYear('financial_ledger.entry_date', $filters['year_filter']))
            ->selectRaw('
                SUM(debit_amount_ugx) as grand_total_debits,
                SUM(credit_amount_ugx) as grand_total_credits,
                (SUM(debit_amount_ugx) - SUM(credit_amount_ugx)) as trial_balance_difference,
                COUNT(*) as total_entries
            ')
            ->first();

        // Format trial balance with proper debit/credit presentation
        $formattedTrialBalance = $trialBalance->map(function($account) {
            $isDebitAccount = in_array($account->account_type, self::DEBIT_ACCOUNTS);
            $accountBalance = (float) $account->account_balance;

            return [
                'account_type' => $account->account_type,
                'fuel_type' => $account->fuel_type,
                'account_name' => ucwords(str_replace('_', ' ', $account->account_type)) . " - " . ucwords(str_replace('_', ' ', $account->fuel_type)),
                'total_debits' => round((float) $account->total_debits, 4),
                'total_credits' => round((float) $account->total_credits, 4),
                'account_balance' => round($accountBalance, 4),
                'balance_type' => $isDebitAccount ? 'DEBIT' : 'CREDIT',
                'trial_balance_debit' => ($isDebitAccount && $accountBalance > 0) ? round($accountBalance, 4) : 0.0000,
                'trial_balance_credit' => (!$isDebitAccount && $accountBalance < 0) ? round(abs($accountBalance), 4) : 0.0000,
                'entry_count' => (int) $account->entry_count
            ];
        })->toArray();

        return [
            'trial_balance_accounts' => $formattedTrialBalance,
            'grand_totals' => [
                'total_debits' => round((float) $grandTotals->grand_total_debits, 4),
                'total_credits' => round((float) $grandTotals->grand_total_credits, 4),
                'difference' => round((float) $grandTotals->trial_balance_difference, 4),
                'total_entries' => (int) $grandTotals->total_entries,
                'is_balanced' => abs((float) $grandTotals->trial_balance_difference) < 0.0001 // Allow for minimal rounding
            ],
            'trial_balance_summary' => [
                'total_debit_balances' => round(array_sum(array_column($formattedTrialBalance, 'trial_balance_debit')), 4),
                'total_credit_balances' => round(array_sum(array_column($formattedTrialBalance, 'trial_balance_credit')), 4)
            ]
        ];
    }

    /**
     * MATHEMATICAL VALIDATION - Ensures accounting equation integrity
     * Assets = Liabilities + Equity (in our case: Debits = Credits)
     */
    private function validateTrialBalance(array $filters): array
    {
        $validation = DB::table('financial_ledger')
            ->join('stations', 'financial_ledger.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['account_types']), fn($q) => $q->whereIn('financial_ledger.account_type', $filters['account_types']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('financial_ledger.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), fn($q) => $q->whereBetween('financial_ledger.entry_date', [$filters['date_range']['start'], $filters['date_range']['end']]))
            ->when(!empty($filters['month_filter']), fn($q) => $q->whereMonth('financial_ledger.entry_date', $filters['month_filter']))
            ->when(!empty($filters['year_filter']), fn($q) => $q->whereYear('financial_ledger.entry_date', $filters['year_filter']))
            ->selectRaw('
                SUM(debit_amount_ugx) as total_debits,
                SUM(credit_amount_ugx) as total_credits,
                ABS(SUM(debit_amount_ugx) - SUM(credit_amount_ugx)) as imbalance,
                COUNT(*) as total_transactions,
                COUNT(CASE WHEN debit_amount_ugx > 0 AND credit_amount_ugx > 0 THEN 1 END) as invalid_entries
            ')
            ->first();

        $totalDebits = (float) $validation->total_debits;
        $totalCredits = (float) $validation->total_credits;
        $imbalance = (float) $validation->imbalance;
        $isBalanced = $imbalance < 0.0001; // Allow minimal floating point precision

        return [
            'total_debits' => round($totalDebits, 4),
            'total_credits' => round($totalCredits, 4),
            'imbalance_amount' => round($imbalance, 4),
            'is_mathematically_balanced' => $isBalanced,
            'balance_percentage' => $totalDebits > 0 ? round(($imbalance / $totalDebits) * 100, 6) : 0,
            'total_transactions' => (int) $validation->total_transactions,
            'invalid_entries' => (int) $validation->invalid_entries,
            'validation_status' => $isBalanced ? 'BALANCED' : 'IMBALANCED',
            'validation_message' => $isBalanced ?
                'Trial balance is mathematically correct' :
                "CRITICAL: Trial balance imbalance of UGX " . number_format($imbalance, 4)
        ];
    }

    /**
     * CALCULATE RUNNING BALANCES - For general ledger display
     */
    private function calculateRunningBalances(array $filters): array
    {
        return DB::table('financial_ledger')
            ->join('stations', 'financial_ledger.station_id', '=', 'stations.id')
            ->when(!empty($filters['station_ids']), fn($q) => $q->whereIn('stations.id', $filters['station_ids']))
            ->when(!empty($filters['account_types']), fn($q) => $q->whereIn('financial_ledger.account_type', $filters['account_types']))
            ->when(!empty($filters['fuel_types']), fn($q) => $q->whereIn('financial_ledger.fuel_type', $filters['fuel_types']))
            ->when(!empty($filters['date_range']), fn($q) => $q->whereBetween('financial_ledger.entry_date', [$filters['date_range']['start'], $filters['date_range']['end']]))
            ->when(!empty($filters['month_filter']), fn($q) => $q->whereMonth('financial_ledger.entry_date', $filters['month_filter']))
            ->when(!empty($filters['year_filter']), fn($q) => $q->whereYear('financial_ledger.entry_date', $filters['year_filter']))
            ->selectRaw('
                account_type,
                SUM(debit_amount_ugx) as cumulative_debits,
                SUM(credit_amount_ugx) as cumulative_credits,
                (SUM(debit_amount_ugx) - SUM(credit_amount_ugx)) as running_balance
            ')
            ->groupBy('account_type')
            ->orderBy('account_type')
            ->get()
            ->keyBy('account_type')
            ->map(fn($balance) => [
                'cumulative_debits' => round((float) $balance->cumulative_debits, 4),
                'cumulative_credits' => round((float) $balance->cumulative_credits, 4),
                'running_balance' => round((float) $balance->running_balance, 4)
            ])
            ->toArray();
    }

    /**
     * STATION ACCESS CONTROL - MANDATORY SECURITY
     */
    private function enforceStationAccess(): array
    {
        $user = auth()->user();
        if (!$user) throw new Exception("Authentication required");

        if ($user->role === 'admin') {
            return DB::table('stations')->select('id', 'name', 'location')->get()->toArray();
        }

        if (!$user->station_id) throw new Exception("No assigned station");

        $station = DB::table('stations')->where('id', $user->station_id)->select('id', 'name', 'location')->first();
        if (!$station) throw new Exception("Assigned station not found");

        return [(array) $station];
    }

    /**
     * FILTER VALIDATION & APPLICATION
     */
    private function validateAndApplyFilters(Request $request, array $stationScope): array
    {
        $filters = [
            'station_ids' => array_column($stationScope, 'id'),
            'account_types' => [],
            'fuel_types' => [],
            'date_range' => null,
            'month_filter' => null,
            'year_filter' => null,
            'metadata' => []
        ];

        if ($request->has('station_ids')) {
            $requested = $request->input('station_ids', []);
            $filters['station_ids'] = array_intersect($requested, array_column($stationScope, 'id'));
        }

        if ($request->has('account_types')) {
            $requested = $request->input('account_types', []);
            $filters['account_types'] = array_intersect($requested, self::ACCOUNT_TYPES);
        }

        if ($request->has('fuel_types')) {
            $requested = $request->input('fuel_types', []);
            $filters['fuel_types'] = array_intersect($requested, self::FUEL_TYPES);
        }

        if ($request->has('start_date') || $request->has('end_date')) {
            $start = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $end = $request->input('end_date', now()->format('Y-m-d'));

            $startDate = Carbon::createFromFormat('Y-m-d', $start);
            $endDate = Carbon::createFromFormat('Y-m-d', $end);

            if ($startDate->greaterThan($endDate)) {
                throw new Exception("Start date cannot be after end date");
            }

            $filters['date_range'] = ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')];
        }

        if ($request->has('month')) {
            $month = (int) $request->input('month');
            if ($month >= 1 && $month <= 12) {
                $filters['month_filter'] = $month;
            }
        }

        if ($request->has('year')) {
            $year = (int) $request->input('year');
            if ($year >= 2020 && $year <= (int) date('Y') + 1) {
                $filters['year_filter'] = $year;
            }
        }

        $filters['metadata'] = [
            'stations_count' => count($filters['station_ids']),
            'filters_applied' => !empty($filters['account_types']) || !empty($filters['fuel_types']) ||
                               !empty($filters['date_range']) || !empty($filters['month_filter']) || !empty($filters['year_filter'])
        ];

        return $filters;
    }

    /**
     * GET FILTER OPTIONS - For UI dropdowns
     */
    private function getFilterOptions(array $stationScope): array
    {
        $stationIds = array_column($stationScope, 'id');

        return [
            'stations' => $stationScope,
            'account_types' => self::ACCOUNT_TYPES,
            'fuel_types' => self::FUEL_TYPES,
            'years' => DB::table('financial_ledger')
                ->whereIn('station_id', $stationIds)
                ->selectRaw('DISTINCT YEAR(entry_date) as year')
                ->orderBy('year', 'DESC')
                ->pluck('year')
                ->toArray(),
            'months' => [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ]
        ];
    }
}
