<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers;

use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class CustomerResetPasswordController extends Controller
{
    use ResetsPasswords;

    public function showResetForm(Request $request, $token = null)
    {
        return view('chief-trader-shop::customer.auth.password.reset-form')->with(['token' => $token, 'email' => $request->email]);
    }

    protected function guard()
    {
        return Auth::guard('customer');
    }

    public function broker()
    {
        return Password::broker('customer');
    }

    public function redirectPath()
    {
        return route('customer.index');
    }
}
