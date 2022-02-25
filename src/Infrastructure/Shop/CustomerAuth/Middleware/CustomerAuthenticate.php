<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class CustomerAuthenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('customer.login');
        }
    }
}
