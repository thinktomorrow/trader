<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CustomerRedirectIfAuthenticated
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('customer')->check()) {
            return redirect(route('customer.index'));
        }

        return $next($request);
    }
}
