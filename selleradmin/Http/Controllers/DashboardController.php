<?php

namespace SellerAdmin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('selleradmin::dashboard.index');
    }
}
