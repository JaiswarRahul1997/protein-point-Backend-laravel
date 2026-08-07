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
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Admin\Console\Commands\SeedAdminProductsCommand::class,
            ]);
        }

        $this->loadViewsFrom(base_path('admin/resources/views'), 'admin');
        $this->loadMigrationsFrom(base_path('admin/database/migrations'));

        Route::middleware(['web'])
            ->prefix('admin')
            ->group(base_path('admin/routes/web.php'));
    }
}
