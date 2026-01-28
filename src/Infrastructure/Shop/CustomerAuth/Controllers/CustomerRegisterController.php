<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Thinktomorrow\Trader\Application\Customer\CustomerApplication;
use Thinktomorrow\Trader\Application\Customer\RegisterCustomer;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;

class CustomerRegisterController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        private CustomerApplication     $customerApplication,
        private CustomerRepository      $customerRepository,
        private CustomerLoginRepository $customerLoginRepository,
    )
    {
        $this->middleware('customer-guest');
    }

    public function showRegisterForm()
    {
        return view('trader::customer.auth.register');
    }

    public function register(Request $request, ?string $redirect = null)
    {
        $this->validate($request, [
            'is_business' => ['sometimes', 'boolean'],
            'firstname' => ['required', 'string', 'max:200'],
            'lastname' => ['required', 'string', 'max:200'],
            'email' => ['required', 'string', 'email', 'max:200'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company' => ['required_if:is_business,true', 'nullable', 'max:200'],
        ], [
            'company.required_if' => __('trader-auth.register.validation.company_required'),
        ]);

        $existingCustomer = CustomerModel::where('email', $request->email)->first();

        if (!$existingCustomer) {
            // Maak nieuwe klant aan
            $customerId = $this->customerApplication->registerCustomer(new RegisterCustomer(
                $request->email,
                (bool)$request->is_business,
                app()->getLocale(),
                [
                    'firstname' => $request->firstname,
                    'lastname' => $request->lastname,
                    'company' => $request->company ?? null,
                    'vat_number' => $request->vat_number ?? null,
                    'phone' => $request->phone ?? null,
                ]
            ));

            // Maak login aan voor klant
            $this->customerLoginRepository->save(CustomerLogin::create(
                $customerId,
                Email::fromString($request->email),
                bcrypt($request->password)
            ));

            // Verzend verificatiemail
            $customer = CustomerModel::findOrFail($customerId->get());

            $customer->sendEmailVerificationNotification();

        } else {
            // Indien klant al bestaat maar nog niet geverifieerd, stuur opnieuw mail
            if (!$existingCustomer->hasVerifiedEmail()) {
                $existingCustomer->sendEmailVerificationNotification();
            }

            // Indien klant al verified, doen we niets — we geven gewoon dezelfde feedback
        }

        if (!$redirect) {
            $redirect = route('customer.login');
        }

        return redirect()
            ->to($redirect)
            ->with('status', __('trader-auth.verify.pending_verification'));
    }
}
