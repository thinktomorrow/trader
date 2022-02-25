<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use function view;

class CustomerForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('customer-guest');
    }

    public function showLinkRequestForm()
    {
        return view('chief::admin.auth.passwords.email');
    }

    public function broker()
    {
        return Password::broker('customer');
    }
}
