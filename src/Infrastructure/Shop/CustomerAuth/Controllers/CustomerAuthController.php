<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Validation\ValidatesRequests;
use function route;
use function redirect;

class CustomerAuthController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {

    }

    public function showLoginForm()
    {
        // TODO/ return view;
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::guard('customer')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
            return redirect()->intended(route('customer.home'));
        }

        $failedAttempt = 'Jouw gegevens zijn onjuist of jouw account is niet actief.';

        return redirect()->back()->withInput($request->only('email', 'remember'))->withErrors($failedAttempt);
    }

    /**
     * Log the admin out of the application.
     *
     * @param \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

//        $request->session()->forget('chief_password_hash');

        return redirect('/');
    }
}
