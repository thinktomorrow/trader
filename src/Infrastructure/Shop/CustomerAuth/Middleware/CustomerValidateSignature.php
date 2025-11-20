<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ValidateSignature;

class CustomerValidateSignature extends ValidateSignature
{
    public function handle($request, Closure $next, ...$args)
    {
        [$relative, $ignore] = $this->parseArguments($args);

        if ($request->hasValidSignatureWhileIgnoring($ignore, ! $relative)) {
            return $next($request);
        }

        return redirect()->route('customer.verification.show')
            ->with('status', trans('trader-auth.verify.invalid_verification'));
    }
}
