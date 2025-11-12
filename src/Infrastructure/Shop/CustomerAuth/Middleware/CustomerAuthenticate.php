<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CustomerAuthenticate
{
    public function handle($request, Closure $next, ?string $redirectRoute = null)
    {
        if (! Auth::guard('customer')->check()) {
            return redirect()->route($redirectRoute ?? 'customer.login');
        }

        return $next($request);
    }
}
