<?php

namespace SellerAdmin\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SellerAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(base_path('selleradmin/resources/views'), 'selleradmin');
        $this->loadMigrationsFrom(base_path('selleradmin/database/migrations'));

        Route::middleware(['web', \SellerAdmin\Http\Middleware\EnsureSellerAdminAccess::class])
            ->prefix('selleradmin')
            ->group(base_path('selleradmin/routes/web.php'));
    }
}
