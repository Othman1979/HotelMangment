<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontDeskController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NightAuditController;
use App\Http\Controllers\OutletPosController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomRackController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::post('/language', LanguageController::class)->name('language');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('home');
    Route::get('/rack', RoomRackController::class)->name('rack');

    Route::middleware('role:manager,front_desk,cashier')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::get('/reservations/availability', [ReservationController::class, 'availability'])->name('reservations.availability');
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
        Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
        Route::post('/stays/{stay}/dates', [ReservationController::class, 'dates'])->name('stays.dates');
        Route::post('/stays/{stay}/assign', [ReservationController::class, 'assign'])->name('stays.assign');
        Route::post('/stays/{stay}/rate', [ReservationController::class, 'rate'])->name('stays.rate');

        Route::get('/guests/search', [GuestController::class, 'search'])->name('guests.search');
        Route::resource('guests', GuestController::class)->except('destroy');

        Route::get('/front/{list}', [FrontDeskController::class, 'index'])->whereIn('list', ['arrivals', 'in-house', 'departures'])->name('front.index');
        Route::get('/stays/{stay}/check-in', [FrontDeskController::class, 'checkInForm'])->name('stays.check-in');
        Route::post('/stays/{stay}/check-in', [FrontDeskController::class, 'checkIn']);
        Route::post('/stays/{stay}/check-out', [FrontDeskController::class, 'checkOut'])->name('stays.check-out');
        Route::post('/stays/{stay}/move', [FrontDeskController::class, 'move'])->name('stays.move');

        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
        Route::post('/accounts/{account}/charges', [AccountController::class, 'charge'])->name('accounts.charge');
        Route::post('/accounts/{account}/payments', [AccountController::class, 'pay'])->name('accounts.pay');
        Route::post('/transactions/{line}/reverse', [AccountController::class, 'reverse'])->name('transactions.reverse');
        Route::get('/payments/{payment}/receipt', [AccountController::class, 'receipt'])->name('payments.receipt');
        Route::get('/invoices/{invoice}', [AccountController::class, 'invoice'])->name('invoices.show');
        Route::get('/accounts/{account}/folio', [AccountController::class, 'folio'])->name('accounts.folio');
    });

    Route::middleware('role:manager,front_desk,cashier,outlet')->group(function () {
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shifts', [ShiftController::class, 'open'])->name('shifts.open');
        Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
        Route::post('/shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
    });

    Route::middleware('role:manager,outlet,front_desk,cashier')->group(function () {
        Route::get('/pos', [OutletPosController::class, 'index'])->name('pos.index');
        Route::get('/pos/{outlet}', [OutletPosController::class, 'show'])->name('pos.show');
        Route::post('/pos/{outlet}', [OutletPosController::class, 'store'])->name('pos.store');
        Route::get('/checks/{check}', [OutletPosController::class, 'check'])->name('pos.check');
    });

    Route::middleware('role:manager,housekeeping,front_desk')->group(function () {
        Route::get('/housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping.index');
        Route::post('/rooms/{room}/housekeeping', [HousekeepingController::class, 'status'])->name('housekeeping.status');
        Route::post('/rooms/{room}/block', [HousekeepingController::class, 'block'])->name('housekeeping.block');
        Route::post('/blocks/{block}/release', [HousekeepingController::class, 'release'])->name('housekeeping.release');
    });

    Route::middleware('role:manager,night_auditor')->group(function () {
        Route::get('/night-audit', [NightAuditController::class, 'index'])->name('night-audit.index');
        Route::post('/night-audit', [NightAuditController::class, 'run'])->name('night-audit.run');
    });

    Route::middleware('role:manager,night_auditor,cashier')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    });

    Route::middleware('role:manager')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/setup/{resource}', [SetupController::class, 'index'])->name('setup.index');
        Route::get('/setup/{resource}/create', [SetupController::class, 'create'])->name('setup.create');
        Route::post('/setup/{resource}', [SetupController::class, 'store'])->name('setup.store');
        Route::get('/setup/{resource}/{id}/edit', [SetupController::class, 'edit'])->name('setup.edit');
        Route::put('/setup/{resource}/{id}', [SetupController::class, 'update'])->name('setup.update');
    });
});
