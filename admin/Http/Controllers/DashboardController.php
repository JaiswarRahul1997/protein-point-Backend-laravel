<?php

namespace Admin\Http\Controllers;

use Admin\Models\AdminUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;
use SellerAdmin\Models\SellerUser;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin::dashboard.index', [
            'adminUsers' => AdminUser::count(),
            'sellerUsers' => SellerUser::count(),
            'storefrontUsers' => User::count(),
        ]);
    }
}
