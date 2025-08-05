<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CogsReportsController;
use App\Http\Controllers\DipReadingsController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\DailyProfitLossController;
use App\Http\Controllers\MeterReadingsV2Controller;
use App\Http\Controllers\FifoCostAnalysisController;
use App\Http\Controllers\MorningDipReadingsV2Controller;
use App\Http\Controllers\RevenueAnalyticsDashboardController;
use App\Http\Controllers\ReconciliationAnalyticsDashboardController;


Route::middleware(['auth', 'web'])->group(function () {
   Route::get('/reports/general-ledger', [GeneralLedgerController::class, 'index'])->name('reports.general-ledger');
});


Route::middleware(['auth'])->group(function () {
    Route::get('/inventory-analysis', [App\Http\Controllers\InventoryAnalysisController::class, 'index'])
        ->name('reports.inventory-analysis');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/fifo-cost-analysis', [FifoCostAnalysisController::class, 'index'])
        ->name('fifo-cost-analysis.index');

    Route::post('/fifo-cost-analysis/export', [FifoCostAnalysisController::class, 'exportData'])
        ->name('fifo-cost-analysis.export');

});

Route::middleware(['auth'])->group(function () {

    // COGS Dashboard - Main wizard interface
    Route::get('/cogs', [CogsReportsController::class, 'index'])->name('cogs.dashboard');

    // AJAX Data endpoints for wizard steps
    Route::get('/cogs/data', [CogsReportsController::class, 'index'])->name('cogs.data');

    // Export functionality
    Route::post('/cogs/export', [CogsReportsController::class, 'exportCogsData'])->name('cogs.export');

});




Route::middleware(['auth'])->group(function () {
    Route::get('/revenue-analytics', [RevenueAnalyticsDashboardController::class, 'index'])
        ->name('revenue.analytics.dashboard');

    Route::post('/revenue-analytics/data', [RevenueAnalyticsDashboardController::class, 'getRevenueAnalytics'])
        ->name('revenue.analytics.data');
});



// Add these routes to your web.php file
Route::middleware(['auth'])->group(function () {
   Route::get('/reconciliation-analytics', [ReconciliationAnalyticsDashboardController::class, 'index'])
       ->name('reconciliation.analytics');
   Route::get('/reconciliation-analytics/export', [ReconciliationAnalyticsDashboardController::class, 'export'])
       ->name('reconciliation.analytics.export');
});

Route::middleware(['auth'])->group(function () {




     Route::get('/reports/daily-profit-loss', [DailyProfitLossController::class, 'index'])
    ->name('reports.daily-profit-loss');

Route::get('/reports/daily-profit-loss/data', [DailyProfitLossController::class, 'getDailyProfitLoss'])
    ->name('reports.daily-profit-loss.data');

Route::get('/reports/daily-profit-loss/export', [DailyProfitLossController::class, 'exportDailyProfitLoss'])
    ->name('reports.daily-profit-loss.export');

});




Route::middleware(['auth'])->group(function () {

    // CORE ROUTES (VIEW + AJAX dual support)
    Route::get('/meter-readings', [MeterReadingsV2Controller::class, 'index'])
        ->name('meter-readings.index');

    Route::post('/meter-readings', [MeterReadingsV2Controller::class, 'store'])
        ->name('meter-readings.store');

    Route::get('/meter-readings/{id}', [MeterReadingsV2Controller::class, 'show'])
        ->name('meter-readings.show');

    // AJAX DATA ENDPOINTS
    Route::get('/api/meter-readings/station-meters', [MeterReadingsV2Controller::class, 'getStationMeters'])
        ->name('meter-readings.station-meters');

    Route::get('/api/meter-readings/meter-history', [MeterReadingsV2Controller::class, 'getMeterHistory'])
        ->name('meter-readings.meter-history');

    Route::get('/api/meter-readings/dashboard-data', [MeterReadingsV2Controller::class, 'getDashboardData'])
        ->name('meter-readings.dashboard-data');

    Route::get('/api/meter-readings/automation-health', [MeterReadingsV2Controller::class, 'getAutomationHealth'])
        ->name('meter-readings.automation-health');

    Route::post('/api/meter-readings/validate-preview', [MeterReadingsV2Controller::class, 'validatePreview'])
        ->name('meter-readings.validate-preview');

    // EXPORT FUNCTIONALITY
    Route::get('/meter-readings/export', [MeterReadingsV2Controller::class, 'export'])
        ->name('meter-readings.export');
});



Route::middleware(['auth'])->group(function () {
   Route::get('/dip-readings', [DipReadingsController::class, 'index'])->name('dip-readings.index');
   Route::post('/dip-readings/tank-readings', [DipReadingsController::class, 'getTankReadings'])->name('dip-readings.tank-readings');
   Route::post('/dip-readings/store', [DipReadingsController::class, 'store'])->name('dip-readings.store');
   Route::post('/dip-readings/history', [DipReadingsController::class, 'getReadingHistory'])->name('dip-readings.history');
   Route::delete('/dip-readings/destroy', [DipReadingsController::class, 'destroy'])->name('dip-readings.destroy');
});
