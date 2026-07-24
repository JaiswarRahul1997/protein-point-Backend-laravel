<?php

namespace Admin\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(base_path('admin/resources/views'), 'admin');
        $this->loadMigrationsFrom(base_path('admin/database/migrations'));

        Route::middleware(['web', \Admin\Http\Middleware\EnsureAdminAccess::class])
            ->prefix('admin')
            ->group(base_path('admin/routes/web.php'));
    }
}
