<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerHasLoggedIn;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerHasLoggedOut;

class CustomerAuthController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('customer-guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('trader::customer.auth.login');
    }

    public function login(Request $request, ?string $redirectAfterLogin = null)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $guard = Auth::guard('customer');

        if (! $guard instanceof StatefulGuard) {
            throw new \LogicException('The customer auth guard must be stateful.');
        }

        if ($guard->attempt(['email' => $request->input('email'), 'password' => $request->input('password')], $request->boolean('remember'))) {
            $customer = $guard->user();

            if (! is_callable([$customer, 'getCustomerId'])) {
                throw new \LogicException('The authenticated customer must expose its customer ID.');
            }

            event(new CustomerHasLoggedIn(
                CustomerId::fromString($customer->getCustomerId())
            ));

            if ($redirectAfterLogin) {
                return redirect()->to($redirectAfterLogin);
            }

            return redirect()->intended(route('customer.index'));
        }

        return redirect()->back()
            ->withInput($request->only('email', 'remember_me'))
            ->withErrors(['email' => trans('trader-customer.login_form.failed')]);
    }

    /**
     * Log the admin out of the application.
     *
     *
     * @return RedirectResponse|Redirector
     */
    public function logout(Request $request)
    {
        $guard = Auth::guard('customer');

        if (! $guard instanceof StatefulGuard) {
            throw new \LogicException('The customer auth guard must be stateful.');
        }

        $customer = $guard->user();

        if (! is_callable([$customer, 'getCustomerId'])) {
            throw new \LogicException('The authenticated customer must expose its customer ID.');
        }

        $customerId = CustomerId::fromString($customer->getCustomerId());

        $guard->logout();

        event(new CustomerHasLoggedOut($customerId));

        return redirect('/');
    }
}
