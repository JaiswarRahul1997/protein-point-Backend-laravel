<?php

use Admin\Http\Controllers\AuthController;
use Admin\Http\Controllers\BannerController;
use Admin\Http\Controllers\CategoryController;
use Admin\Http\Controllers\DashboardController;
use Admin\Http\Controllers\MediaController;
use Admin\Http\Controllers\MenuController;
use Admin\Http\Controllers\OrderController;
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

    Route::get('banners', [BannerController::class, 'index'])->name('admin.banners.index');
    Route::get('banners/create', [BannerController::class, 'create'])->name('admin.banners.create');
    Route::post('banners', [BannerController::class, 'store'])->name('admin.banners.store');
    Route::get('banners/{banner}/edit', [BannerController::class, 'edit'])->name('admin.banners.edit');
    Route::put('banners/{banner}', [BannerController::class, 'update'])->name('admin.banners.update');
    Route::delete('banners/{banner}', [BannerController::class, 'destroy'])->name('admin.banners.destroy');

    Route::get('menus', [MenuController::class, 'index'])->name('admin.menus.index');
    Route::get('menus/create', [MenuController::class, 'create'])->name('admin.menus.create');
    Route::post('menus', [MenuController::class, 'store'])->name('admin.menus.store');
    Route::get('menus/{menu}/edit', [MenuController::class, 'edit'])->name('admin.menus.edit');
    Route::put('menus/{menu}', [MenuController::class, 'update'])->name('admin.menus.update');
    Route::delete('menus/{menu}', [MenuController::class, 'destroy'])->name('admin.menus.destroy');

    Route::get('orders', [OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('admin.orders.show');
    Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.status');
});
