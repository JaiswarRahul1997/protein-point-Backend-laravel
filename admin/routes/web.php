<?php

use Admin\Http\Controllers\AuthController;
use Admin\Http\Controllers\CategoryController;
use Admin\Http\Controllers\DashboardController;
use Admin\Http\Controllers\MediaController;
use Admin\Http\Controllers\ProductController;
use Admin\Http\Middleware\EnsureAdminAccess;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('login', [AuthController::class, 'login'])->name('admin.login.submit');
Route::get('signup', [AuthController::class, 'showSignup'])->name('admin.signup');
Route::post('signup', [AuthController::class, 'signup'])->name('admin.signup.submit');

Route::middleware(EnsureAdminAccess::class)->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::get('media/{path}', [MediaController::class, 'show'])
        ->where('path', '.*')
        ->name('admin.media');

    Route::get('products/{type?}', [ProductController::class, 'index'])
        ->whereIn('type', ['simple', 'configurable', 'bundle', 'grouped', 'virtual'])
        ->name('admin.products.index');
    Route::post('products-manage/seed-dummy', [ProductController::class, 'seedDummy'])->name('admin.products.seed-dummy');
    Route::get('products-manage/create', [ProductController::class, 'create'])->name('admin.products.create');
    Route::post('products-manage', [ProductController::class, 'store'])->name('admin.products.store');
    Route::get('products-manage/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('products-manage/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('products-manage/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');

    Route::get('categories', [CategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('categories/create', [CategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');
});
