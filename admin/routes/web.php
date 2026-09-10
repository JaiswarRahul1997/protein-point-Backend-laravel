<?php

use Admin\Http\Controllers\AuthController;
use Admin\Http\Controllers\BannerController;
use Admin\Http\Controllers\BrandController;
use Admin\Http\Controllers\CategoryController;
use Admin\Http\Controllers\CustomerController;
use Admin\Http\Controllers\DashboardController;
use Admin\Http\Controllers\MediaController;
use Admin\Http\Controllers\MenuController;
use Admin\Http\Controllers\OrderController;
use Admin\Http\Controllers\NewsletterCampaignController;
use Admin\Http\Controllers\NewsletterSubscriberController;
use Admin\Http\Controllers\ProductAttributeController;
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
        ->whereIn('type', ['all', 'simple', 'configurable', 'bundle', 'grouped', 'virtual'])
        ->name('admin.products.index');
    Route::post('products-manage/seed-dummy', [ProductController::class, 'seedDummy'])->name('admin.products.seed-dummy');
    Route::get('products-manage/csv-template', [ProductController::class, 'csvTemplate'])->name('admin.products.csv-template');
    Route::get('products-manage/export-csv', [ProductController::class, 'exportCsv'])->name('admin.products.export-csv');
    Route::post('products-manage/import-csv', [ProductController::class, 'importCsv'])->name('admin.products.import-csv');
    Route::get('products-manage/create', [ProductController::class, 'create'])->name('admin.products.create');
    Route::post('products-manage', [ProductController::class, 'store'])->name('admin.products.store');
    Route::get('products-manage/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('products-manage/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('products-manage/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');

    Route::get('attributes', [ProductAttributeController::class, 'index'])->name('admin.attributes.index');
    Route::get('attributes/create', [ProductAttributeController::class, 'create'])->name('admin.attributes.create');
    Route::post('attributes', [ProductAttributeController::class, 'store'])->name('admin.attributes.store');
    Route::get('attributes/{attribute}/edit', [ProductAttributeController::class, 'edit'])->name('admin.attributes.edit');
    Route::put('attributes/{attribute}', [ProductAttributeController::class, 'update'])->name('admin.attributes.update');
    Route::delete('attributes/{attribute}', [ProductAttributeController::class, 'destroy'])->name('admin.attributes.destroy');

    Route::get('categories', [CategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('categories/create', [CategoryController::class, 'create'])->name('admin.categories.create');
    Route::get('categories/export-csv', [CategoryController::class, 'exportCsv'])->name('admin.categories.export-csv');
    Route::post('categories/import-csv', [CategoryController::class, 'importCsv'])->name('admin.categories.import-csv');
    Route::post('categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('admin.categories.toggle-status');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    Route::get('brands', [BrandController::class, 'index'])->name('admin.brands.index');
    Route::get('brands/create', [BrandController::class, 'create'])->name('admin.brands.create');
    Route::post('brands', [BrandController::class, 'store'])->name('admin.brands.store');
    Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])->name('admin.brands.edit');
    Route::put('brands/{brand}', [BrandController::class, 'update'])->name('admin.brands.update');
    Route::patch('brands/{brand}/toggle-status', [BrandController::class, 'toggleStatus'])->name('admin.brands.toggle-status');
    Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('admin.brands.destroy');

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

    Route::get('customers', [CustomerController::class, 'index'])->name('admin.customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('admin.customers.show');
    Route::post('customers/{customer}/login-as', [CustomerController::class, 'loginAs'])->name('admin.customers.login-as');
    Route::put('customers/{customer}/status', [CustomerController::class, 'updateStatus'])->name('admin.customers.status');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('admin.customers.destroy');

    Route::get('newsletter/campaigns', [NewsletterCampaignController::class, 'index'])->name('admin.newsletter.campaigns.index');
    Route::get('newsletter/campaigns/create', [NewsletterCampaignController::class, 'create'])->name('admin.newsletter.campaigns.create');
    Route::post('newsletter/campaigns', [NewsletterCampaignController::class, 'store'])->name('admin.newsletter.campaigns.store');
    Route::get('newsletter/campaigns/{campaign}', [NewsletterCampaignController::class, 'show'])->name('admin.newsletter.campaigns.show');
    Route::get('newsletter/campaigns/{campaign}/edit', [NewsletterCampaignController::class, 'edit'])->name('admin.newsletter.campaigns.edit');
    Route::put('newsletter/campaigns/{campaign}', [NewsletterCampaignController::class, 'update'])->name('admin.newsletter.campaigns.update');
    Route::delete('newsletter/campaigns/{campaign}', [NewsletterCampaignController::class, 'destroy'])->name('admin.newsletter.campaigns.destroy');
    Route::post('newsletter/campaigns/{campaign}/schedule', [NewsletterCampaignController::class, 'schedule'])->name('admin.newsletter.campaigns.schedule');
    Route::post('newsletter/campaigns/{campaign}/send', [NewsletterCampaignController::class, 'sendNow'])->name('admin.newsletter.campaigns.send');
    Route::post('newsletter/campaigns/{campaign}/cancel', [NewsletterCampaignController::class, 'cancel'])->name('admin.newsletter.campaigns.cancel');
    Route::get('newsletter/subscribers', [NewsletterSubscriberController::class, 'index'])->name('admin.newsletter.subscribers.index');
});
