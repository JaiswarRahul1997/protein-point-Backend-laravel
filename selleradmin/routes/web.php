<?php

use SellerAdmin\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('selleradmin.dashboard');
