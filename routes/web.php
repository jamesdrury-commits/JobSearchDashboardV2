<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegacyDashboardApiController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::match(['GET', 'POST'], 'api.php', LegacyDashboardApiController::class)->name('legacy-api');

require __DIR__.'/settings.php';
