<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\RecurringController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::get('/register', [\App\Http\Controllers\AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Halaman (Blade)
    |--------------------------------------------------------------------------
    */
    Route::view('/', 'dashboard')->name('dashboard');
    Route::view('/transactions', 'transactions')->name('transactions');
    Route::view('/calendar', 'calendar')->name('calendar');
    Route::view('/stats', 'stats')->name('stats');
    Route::view('/budget', 'budget')->name('budget');
    Route::view('/goals', 'goals')->name('goals');

    /*
    |--------------------------------------------------------------------------
    | API JSON (dipanggil oleh assets/js/api.js)
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('categories', [CategoryController::class, 'index'])->name('categories');

        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::put('transactions/{id}', [TransactionController::class, 'update'])->whereNumber('id')->name('transactions.update');
        Route::delete('transactions/{id}', [TransactionController::class, 'destroy'])->whereNumber('id')->name('transactions.destroy');

        Route::get('calendar', CalendarController::class)->name('calendar');
        Route::get('summary', SummaryController::class)->name('summary');
        Route::get('stats', StatsController::class)->name('stats');
        Route::get('recurring', [RecurringController::class, 'index'])->name('recurring');

        Route::get('periods/active', [PeriodController::class, 'showActive'])->name('periods.active');
        Route::put('periods/active', [PeriodController::class, 'updateActive'])->name('periods.active.update');
        Route::delete('periods/active', [PeriodController::class, 'closeActive'])->name('periods.active.close');
        Route::post('periods', [PeriodController::class, 'store'])->name('periods.store');

        Route::get('goals', [GoalController::class, 'index'])->name('goals.index');
        Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
        Route::put('goals/{id}', [GoalController::class, 'update'])->whereNumber('id')->name('goals.update');
        Route::delete('goals/{id}', [GoalController::class, 'destroy'])->whereNumber('id')->name('goals.destroy');
    });
});
