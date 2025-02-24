<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Middleware;

use Closure;

class AdjustCatalogVatRates
{
    public function handle($request, Closure $next, ...$guards)
    {
        // Country selection via cookie? ...

        // customer() auth? -> get billing country -> vat rates + mapping -> run adjust catalog prices, ..., discount vat rates, shipping vat rates.
        // ...

        return $next($request);
    }
}
