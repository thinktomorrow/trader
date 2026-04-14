<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CustomerVerified
{
    public function handle($request, Closure $next, ?string $redirectRoute = null)
    {
        if (! Auth::guard('customer')->check()) {
            return redirect()->route($redirectRoute ?? 'customer.login');
        }

        $customer = Auth::guard('customer')->user();

        if ($customer instanceof MustVerifyEmail && ! $customer->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::route($redirectRoute ?: 'customer.verification.show');
        }

        return $next($request);
    }
}
