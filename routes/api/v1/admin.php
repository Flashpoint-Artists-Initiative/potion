<?php

use App\Http\Controllers\Api\Admin\AuditController;
use App\Http\Controllers\Api\Admin\CompletedWaiversController;
use App\Http\Controllers\Api\Admin\MetricsController;
use App\Http\Controllers\Api\Admin\OrdersController;
use App\Http\Controllers\Api\Admin\TicketTransfersController;
use App\Http\Controllers\Api\LockdownController;
use App\Services\ApiLockdownService;
use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'token.refresh'])->prefix('admin')->as('api.admin.')->group(function () {
    Orion::resource('completed-waivers', CompletedWaiversController::class)->except(['update', 'batchUpdate']);
    Orion::resource('orders', OrdersController::class)->only(['index', 'show', 'search']);
    Orion::resource('audits', AuditController::class)->only(['index', 'show', 'search']);
    Orion::resource('ticket-transfers', TicketTransfersController::class)->only(['index', 'show', 'search', 'destroy']);

    // Lockdown Routes
    Route::get('lockdown', [LockdownController::class, 'getLockdownStatus'])->whereIn('type', ApiLockdownService::lockdownTypes())->name('lockdown.status');
    Route::post('lockdown/{type}', [LockdownController::class, 'enableLockdown'])->whereIn('type', ApiLockdownService::lockdownTypes())->name('lockdown.enable');
    Route::delete('lockdown/{type}', [LockdownController::class, 'disableLockdown'])->whereIn('type', ApiLockdownService::lockdownTypes())->name('lockdown.disable');

    // Metrics Routes
    Route::group(['prefix' => 'metrics', 'as' => 'metrics.', 'controller' => MetricsController::class], function () {
        Route::get('sales-data', 'salesDataAction')->name('salesData');
        Route::get('ticket-data', 'ticketDataAction')->name('ticketData');
    });
});
