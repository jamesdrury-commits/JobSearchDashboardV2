<?php

use App\Http\Controllers\LegacyDashboardApiController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], 'legacy-dashboard', LegacyDashboardApiController::class);
