<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
// use App\Http\Controllers\;
use App\Http\Controllers\VarianceController;
use App\Http\Controllers\DeliveriesController;
use App\Http\Controllers\RTTAnalyticsController;
use App\Http\Controllers\MeterReadingsController;
use App\Http\Controllers\PriceAnalysisController;
use App\Http\Controllers\TankManagementController;
use App\Http\Controllers\MeterManagementController;
use App\Http\Controllers\UsersManagementController;
use App\Http\Controllers\DailyDipReadingsController;
use App\Http\Controllers\FifoCostAnalysisController;
use App\Http\Controllers\StationsManagementController;
use App\Http\Controllers\ReconciliationAnalysisController;
use App\Http\Controllers\DailyEveningDipReadingsController;





Route::middleware(['auth'])->group(function () {

    // Reconciliation Analysis Routes
    Route::prefix('reconciliation-analysis')->name('reconciliation-analysis.')->group(function () {

        Route::get('/', [ReconciliationAnalysisController::class, 'index'])->name('index');

        Route::get('/missing', [ReconciliationAnalysisController::class, 'missingReconciliations'])->name('missing');

        Route::get('/faulty', [ReconciliationAnalysisController::class, 'faultyReconciliations'])->name('faulty');

        Route::get('/fifo-integrity', [ReconciliationAnalysisController::class, 'fifoIntegrity'])->name('fifo-integrity');

        Route::get('/variance-analysis', [ReconciliationAnalysisController::class, 'varianceAnalysis'])->name('variance-analysis');

        Route::post('/process-manual', [ReconciliationAnalysisController::class, 'processManualReconciliation'])->name('process-manual');

        Route::get('/performance-report', [ReconciliationAnalysisController::class, 'performanceReport'])->name('performance-report');

    });

});

Route::middleware(['auth'])->group(function () {
   Route::get('/price-analysis', [PriceAnalysisController::class, 'index'])->name('price-analysis.index');
   Route::get('/price-analysis/history', [PriceAnalysisController::class, 'priceHistory'])->name('price-analysis.history');
});





// Add to web.php

Route::middleware(['auth'])->group(function () {

    // Variance Management Routes
    Route::prefix('variance')->name('variance.')->group(function () {

        // Main variance dashboard
        Route::get('/', [VarianceController::class, 'index'])->name('index');

        // Investigation log
        Route::get('/investigation-log', [VarianceController::class, 'investigationLog'])->name('investigation-log');

        // Update investigation status
        Route::post('/{notification_id}/investigation', [VarianceController::class, 'updateInvestigation'])->name('update-investigation');

    });

});
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/daily-reconciliation', [ReportsController::class, 'dailyReconciliation'])->name('daily-reconciliation');
    Route::get('/weekly-summary', [ReportsController::class, 'weeklySummary'])->name('weekly-summary');
    Route::get('/monthly-summary', [ReportsController::class, 'monthlySummary'])->name('monthly-summary');
});

// Route::middleware(['auth'])->group(function () {
//     Route::get('/meter-readings', [MeterReadingsController::class, 'index'])->name('meter-readings.index');
//     Route::post('/meter-readings/morning', [MeterReadingsController::class, 'storeMorningReading'])->name('meter-readings.store-morning');
//     Route::post('/meter-readings/evening', [MeterReadingsController::class, 'storeEveningReading'])->name('meter-readings.store-evening');
//     Route::get('/meter-readings/pending', [MeterReadingsController::class, 'getPendingReadings'])->name('meter-readings.pending');
// });


// Evening Dip Readings Routes
// Route::middleware(['auth'])->group(function () {
//     Route::get('/daily-evening-dip-readings', [DailyEveningDipReadingsController::class, 'index'])->name('daily-evening-dip-readings.index');
//     Route::post('/daily-evening-dip-readings', [DailyEveningDipReadingsController::class, 'store'])->name('daily-evening-dip-readings.store');
//     Route::get('/daily-evening-dip-readings/pending', [DailyEveningDipReadingsController::class, 'getPendingTanks'])->name('daily-evening-dip-readings.pending');
// });
// routes/web.php
// routes/web.php - Simple backward compatible routes

// Route::middleware(['auth'])->group(function () {

//     // NEW ROUTES - Morning Dip Readings
//     Route::prefix('morning-dip-readings')->name('morning-dip-readings.')->group(function () {
//         Route::get('/', [App\Http\Controllers\MorningDipReadingsController::class, 'index'])->name('index');
//         Route::post('/station-data', [App\Http\Controllers\MorningDipReadingsController::class, 'getStationData'])->name('station-data');
//         Route::post('/', [App\Http\Controllers\MorningDipReadingsController::class, 'store'])->name('store');
//         Route::post('/summary', [App\Http\Controllers\MorningDipReadingsController::class, 'getSummary'])->name('summary');
//         Route::post('/tank-history', [App\Http\Controllers\MorningDipReadingsController::class, 'getTankHistory'])->name('tank-history');
//     });

//     // OLD ROUTES - Keep working with new controller
//     Route::get('daily-dip-readings', [App\Http\Controllers\MorningDipReadingsController::class, 'index'])
//         ->name('daily-dip-readings.index');

//     Route::post('daily-dip-readings/morning', [App\Http\Controllers\MorningDipReadingsController::class, 'store'])
//         ->name('daily-dip-readings.store-morning');

//     Route::post('daily-dip-readings/evening', [App\Http\Controllers\EveningDipReadingsController::class, 'store'])
//         ->name('daily-dip-readings.store-evening');

//     Route::get('daily-dip-readings/pending', [App\Http\Controllers\MorningDipReadingsController::class, 'getSummary'])
//         ->name('daily-dip-readings.pending');
// });
// // Deliveries Routes
// use App\Http\Controllers\DeliveriesController;

Route::middleware(['auth'])->group(function () {

    Route::get('/rtt/analytics', [RTTAnalyticsController::class, 'index'])->name('rtt.analytics');
    // ✅ Your original routes (unchanged)
    Route::get('/deliveries', [DeliveriesController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/create', [DeliveriesController::class, 'create'])->name('deliveries.create');
    Route::post('/deliveries', [DeliveriesController::class, 'store'])->name('deliveries.store');
    Route::get('/deliveries/{delivery}', [DeliveriesController::class, 'show'])->name('deliveries.show');
    Route::get('/api/tanks/{tank}/capacity', [DeliveriesController::class, 'getTankCapacity'])->name('api.tanks.capacity');

    // 🆕 NEW: Missing CRUD routes for deliveries
    Route::get('/deliveries/{delivery}/edit', [DeliveriesController::class, 'edit'])->name('deliveries.edit');
    Route::put('/deliveries/{delivery}', [DeliveriesController::class, 'update'])->name('deliveries.update');
    Route::delete('/deliveries/{delivery}', [DeliveriesController::class, 'destroy'])->name('deliveries.destroy');

    // 🆕 NEW: Overflow management routes
    Route::get('/deliveries/overflow/dashboard', [DeliveriesController::class, 'overflowDashboard'])->name('deliveries.overflow.dashboard');
    Route::post('/deliveries/overflow/return-to-tank', [DeliveriesController::class, 'returnToTank'])->name('deliveries.overflow.rtt');

    // 🆕 NEW: Pre-validation and API routes
    Route::post('/api/deliveries/pre-validate', [DeliveriesController::class, 'preValidateDelivery'])->name('api.deliveries.prevalidate');
    Route::get('/api/fuel-types', [DeliveriesController::class, 'getSupportedFuelTypes'])->name('api.fuel.types');
    Route::get('/api/stations/{station}/tanks', [DeliveriesController::class, 'getTanksByStation'])->name('api.stations.tanks');
});

Route::middleware(['auth'])->prefix('pricing')->name('pricing.')->group(function () {
    Route::get('/', [PricingController::class, 'index'])->name('index');
    Route::get('/profit-analysis', [PricingController::class, 'getProfitAnalysis'])->name('profit-analysis');
    Route::get('/price-history', [PricingController::class, 'getPriceHistory'])->name('price-history');
});

Route::middleware(['auth'])->group(function () {

    // Meter Management Routes
    Route::prefix('meters')->name('meters.')->group(function () {
        Route::get('/', [MeterManagementController::class, 'index'])->name('index');
        Route::get('/create', [MeterManagementController::class, 'create'])->name('create');
        Route::post('/', [MeterManagementController::class, 'store'])->name('store');
        Route::get('/{meter_id}', [MeterManagementController::class, 'show'])->name('show');
        Route::get('/{meter_id}/edit', [MeterManagementController::class, 'edit'])->name('edit');
        Route::put('/{meter_id}', [MeterManagementController::class, 'update'])->name('update');
        Route::delete('/{meter_id}', [MeterManagementController::class, 'destroy'])->name('destroy');
        Route::get('/stations/{station_id}/tanks', [MeterManagementController::class, 'getTanksForStation'])->name('tanks_for_station');
        Route::patch('/{meter_id}/toggle-status', [MeterManagementController::class, 'toggleStatus'])->name('toggle_status');
    });

    Route::prefix('tanks')->name('tanks.')->group(function () {
        // Core CRUD Operations (✅ Already Defined)
        Route::get('/', [TankManagementController::class, 'index'])->name('index');
        Route::get('/create', [TankManagementController::class, 'create'])->name('create');
        Route::post('/', [TankManagementController::class, 'store'])->name('store');
        Route::get('/{tank_id}', [TankManagementController::class, 'show'])->name('show');
        Route::get('/{tank_id}/edit', [TankManagementController::class, 'edit'])->name('edit');
        Route::put('/{tank_id}', [TankManagementController::class, 'update'])->name('update');
        Route::delete('/{tank_id}', [TankManagementController::class, 'destroy'])->name('destroy');

        // ✅ Already Defined - Enhanced Operations
        Route::get('/{tank_id}/dashboard-data', [TankManagementController::class, 'getDashboardData'])->name('dashboard-data');
        Route::put('/{tank_id}/stock-thresholds', [TankManagementController::class, 'updateStockThresholds'])->name('update-stock-thresholds');

        // 🔴 MISSING - Add These Enhanced Routes
        Route::get('/{tank_id}/reconciliation-history', [TankManagementController::class, 'getReconciliationHistory'])->name('reconciliation-history');
        Route::get('/{tank_id}/fifo-status', [TankManagementController::class, 'getFifoStatus'])->name('fifo-status');
        Route::get('/{tank_id}/export', [TankManagementController::class, 'exportTankData'])->name('export');
    });

    Route::group(['prefix' => 'stations', 'as' => 'stations.'], function () {
        Route::get('/', [StationsManagementController::class, 'index'])->name('index');
        Route::get('/create', [StationsManagementController::class, 'create'])->name('create');
        Route::post('/', [StationsManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [StationsManagementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [StationsManagementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [StationsManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [StationsManagementController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/dashboard-data', [StationsManagementController::class, 'getDashboardData'])->name('dashboard-data');
        Route::get('/{id}/data', [StationsManagementController::class, 'getStationData'])->name('data');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UsersManagementController::class, 'index'])->name('index');
        Route::get('/create', [UsersManagementController::class, 'create'])->name('create');
        Route::post('/', [UsersManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [UsersManagementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [UsersManagementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [UsersManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [UsersManagementController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [UsersManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/station/{stationId}', [UsersManagementController::class, 'getUsersByStation'])->name('by-station');
    });

});

Route::redirect('/', url('/stations'));

Route::get('/dashboard', function () {
    return redirect()->to('/stations');
})->middleware(['auth', 'verified'])->name('dashboard');



Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/V2.php';
