<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use function redirect;
use function route;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerHasLoggedIn;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerHasLoggedOut;

class CustomerAuthController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
    }

    public function showLoginForm()
    {
        return view('chief-trader-shop::customer.auth.login');
    }

    public function login(Request $request, ?string $redirectAfterLogin = null)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::guard('customer')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
            event(new CustomerHasLoggedIn(
                CustomerId::fromString(Auth::guard('customer')->user()->getCustomerId())
            ));

            if ($redirectAfterLogin) {
                return redirect()->to($redirectAfterLogin);
            }

            return redirect()->intended(route('customer.index'));
        }

        return redirect()->back()
            ->withInput($request->only('email', 'remember_me'))
            ->withErrors(['email' => trans('customer.login_form.failed')]);
    }

    /**
     * Log the admin out of the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout(Request $request)
    {
        $customerId = CustomerId::fromString(Auth::guard('customer')->user()->getCustomerId());

        Auth::guard('customer')->logout();

        event(new CustomerHasLoggedOut($customerId));

        return redirect('/');
    }
}
