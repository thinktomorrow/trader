<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use function view;

class CustomerForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
        //        $this->middleware('customer-guest');
    }

    public function showLinkRequestForm()
    {
        return view('chief-trader-shop::customer.auth.password.request-form');
    }

    public function broker()
    {
        return Password::broker('customer');
    }
}
