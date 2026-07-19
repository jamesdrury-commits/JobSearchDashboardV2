<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobDetailsController;
use App\Http\Controllers\LegacyDashboardApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('dashboard/jobs/{job}', [JobDetailsController::class, 'show'])->name('dashboard.jobs.show');
});

Route::match(['GET', 'POST'], 'api.php', LegacyDashboardApiController::class)->name('legacy-api');

require __DIR__.'/settings.php';
