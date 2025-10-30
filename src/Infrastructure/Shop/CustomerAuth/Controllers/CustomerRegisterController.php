<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Thinktomorrow\Trader\Application\Customer\CustomerApplication;
use Thinktomorrow\Trader\Application\Customer\RegisterCustomer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerHasLoggedIn;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;
use function redirect;
use function route;

class CustomerRegisterController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        private CustomerApplication $customerApplication,
        private CustomerRepository  $customerRepository,
    )
    {
        $this->middleware('customer-guest');
    }

    public function showRegisterForm()
    {
        return view('trader::customer.auth.register');
    }

    public function register(Request $request)
    {
        // Company -> is_business
        // Locale

        // Toggle voor business - particulier
        // Bus: company, vat, phone

        $this->validate($request, [
            'is_business' => ['sometimes', 'boolean'],
            'firstname' => ['required', 'string', 'max:200'],
            'lastname' => ['required', 'string', 'max:200'],
            'email' => ['required', 'string', 'email', 'max:200', 'unique:trader_customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company' => ['required_if:is_business,true', 'string', 'max:200']
        ]);

        $customerId = $this->customerApplication->registerCustomer(new RegisterCustomer(
            $request->email,
            !!$request->is_business,
            app()->getLocale(), [
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'company' => $request->company ?? null,
                'vat_number' => $request->vat_number ?? null,
                'phone' => $request->phone ?? null
            ]
        ));

        Auth::guard('customer')->login(CustomerModel::findOrFail($customerId->get()));

        event(new CustomerHasLoggedIn($customerId));

        return redirect()->intended(route('customer.index'));
    }
}
