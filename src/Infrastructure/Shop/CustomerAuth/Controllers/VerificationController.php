<?php

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;

/*
|--------------------------------------------------------------------------
| Email Verification Controller
|--------------------------------------------------------------------------
|
| This controller is responsible for handling email verification for any
| user that recently registered with the application. Emails may also
| be re-sent if the user didn't receive the original email message.
|
*/

class VerificationController extends Controller
{
    use VerifiesEmails;

    public function __construct()
    {
        $this->middleware('customer-auth')->only('show', 'resend');;
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /** Where to redirect users after verification. */
    public function redirectPath()
    {
        return route('customer.index');
    }

    public function show(Request $request)
    {
        return Auth::guard('customer')->user()->hasVerifiedEmail()
            ? redirect($this->redirectPath())
            : view('trader::customer.auth.verify');
    }

    public function verify(Request $request)
    {
        $customer = CustomerModel::findOrFail($request->route('id'));

        if (!hash_equals((string)$request->route('hash'), sha1($customer->getEmailForVerification()))) {
            throw new AuthorizationException();
        }
        if ($customer->hasVerifiedEmail()) {

            $route = Auth::guard('customer')->check() ? 'customer.index' : 'customer.login';

            return redirect()->route($route)
                ->with('status', trans('trader-auth.verify.already_verified'));
        }

        $customer->markEmailAsVerified();

        event(new \Illuminate\Auth\Events\Verified($customer));

        return redirect()->route('customer.login')
            ->with('status', trans('trader-auth.verify.success_verified'))
            ->with('verified', true);
    }
}
