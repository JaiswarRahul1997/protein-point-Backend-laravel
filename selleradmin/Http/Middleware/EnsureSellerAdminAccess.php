<?php

namespace SellerAdmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSellerAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Placeholder for seller-admin authentication / authorization.
        return $next($request);
    }
}
