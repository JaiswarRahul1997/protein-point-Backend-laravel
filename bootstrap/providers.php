<?php

use Admin\Providers\AdminServiceProvider;
use App\Providers\AppServiceProvider;
use SellerAdmin\Providers\SellerAdminServiceProvider;

return [
    AppServiceProvider::class,
    AdminServiceProvider::class,
    SellerAdminServiceProvider::class,
];
