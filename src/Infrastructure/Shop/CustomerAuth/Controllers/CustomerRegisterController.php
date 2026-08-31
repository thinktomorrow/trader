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
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CustomerAlreadyExists;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;

class CustomerRegisterController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        private CustomerApplication $customerApplication,
        private CustomerRepository $customerRepository,
        private CustomerLoginRepository $customerLoginRepository,
    ) {
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

        $existingCustomer = CustomerModel::where('email', $request->input('email'))->first();

        if ($existingCustomer) {
            $this->sendVerificationNotificationWhenNeeded($existingCustomer);
        }

        if (! $existingCustomer) {
            try {
                $customerId = $this->customerApplication->registerCustomer(new RegisterCustomer(
                    $request->input('email'),
                    $request->boolean('is_business'),
                    app()->getLocale(),
                    [
                        'firstname' => $request->input('firstname'),
                        'lastname' => $request->input('lastname'),
                        'company' => $request->input('company'),
                        'vat_number' => $request->input('vat_number'),
                        'phone' => $request->input('phone'),
                    ]
                ));

                $this->customerLoginRepository->save(CustomerLogin::create(
                    $customerId,
                    Email::fromString($request->input('email')),
                    bcrypt($request->input('password'))
                ));

                $customer = CustomerModel::findOrFail($customerId->get());

                $customer->sendEmailVerificationNotification();
            } catch (CustomerAlreadyExists) {
                $this->sendVerificationNotificationWhenNeeded(
                    CustomerModel::where('email', $request->input('email'))->firstOrFail()
                );
            }
        }

        if (! $redirect) {
            $redirect = route('customer.login');
        }

        return redirect()
            ->to($redirect)
            ->with('status', __('trader-auth.register.status.verification_notice'));
    }

    private function sendVerificationNotificationWhenNeeded(CustomerModel $customer): void
    {
        if (! $customer->hasVerifiedEmail()) {
            $customer->sendEmailVerificationNotification();
        }
    }
}
