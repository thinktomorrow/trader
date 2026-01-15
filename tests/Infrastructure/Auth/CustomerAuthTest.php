<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerHasLoggedIn;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerHasLoggedOut;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addLocation(__DIR__ . '/views');
        $this->app['view']->addNamespace('trader', __DIR__ . '/views/shop');
    }

    public function test_non_authenticated_are_kept_out()
    {
        $response = $this->get(route('customer.index'));
        $response->assertRedirect(route('customer.login'));
    }


    public function test_it_redirects_if_unauthenticated_customer()
    {
        $response = $this->get(route('customer.index'));

        $response->assertRedirect(route('customer.login'));
    }

    public function test_entering_valid_login_credentials_lets_you_pass()
    {
        $customer = $this->orderContext->createCustomer();
        $customerLogin = $this->orderContext->createCustomerLogin($customer);

        $response = $this->post(route('customer.login.store'), [
            'email' => 'ben@thinktomorrow.be',
            'password' => '123456',
        ]);

        $this->assertTrue(Auth::guard('customer')->check());
        $this->assertEquals($customer->customerId->get(), Auth::guard('customer')->user()->customer_id);

        $response->assertRedirect(route('customer.index'));
        $this->assertFalse(session()->has('errors'));
    }

    public function test_when_logging_in_an_event_is_published()
    {
        Event::fake();

        $customer = $this->orderContext->createCustomer();
        $customerLogin = $this->orderContext->createCustomerLogin($customer);

        $this->post(route('customer.login.store'), [
            'email' => 'ben@thinktomorrow.be',
            'password' => '123456',
        ]);

        Event::assertDispatched(CustomerHasLoggedIn::class);
    }

    public function test_entering_invalid_login_credentials_keeps_you_out()
    {
        $customer = $this->orderContext->createCustomer();
        $customerLogin = $this->orderContext->createCustomerLogin($customer);

        // Enter invalid credentials
        $response = $this->post(route('customer.login.store'), [
            'email' => 'ben@thinktomorrow.be',
            'password' => 'xxx',
        ]);

        $this->assertNull(Auth::guard('customer')->user());
        $this->assertTrue(session()->has('errors'));
        $response->assertRedirect('/');
    }

    public function test_it_displays_customer_page_for_authenticated()
    {
        $customer = $this->orderContext->createCustomer();
        $customerLogin = $this->orderContext->createCustomerLogin($customer);

        $response = $this->actingAs(CustomerModel::first(), 'customer')
            ->get(route('customer.index'));

        $response->assertStatus(200);
        $this->assertFalse(session()->has('errors'));
    }

    public function test_it_redirects_authenticated_customer_to_intended_page()
    {
        $customer = $this->orderContext->createCustomer();
        $customerLogin = $this->orderContext->createCustomerLogin($customer);

        $this->get(route('customer.orders'))
            ->assertRedirect(route('customer.login'));

        $response = $this->post(route('customer.login.store'), [
            'email' => 'ben@thinktomorrow.be',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('customer.orders'));
    }

    public function test_it_can_log_out()
    {
        $customer = $this->orderContext->createCustomer();
        $customerLogin = $this->orderContext->createCustomerLogin($customer);

        Auth::guard('customer')->login(CustomerModel::first());
        $this->assertEquals($customer->customerId->get(), Auth::guard('customer')->user()->customer_id);

        $response = $this->get(route('customer.logout'));
        $response->assertRedirect('/');

        $this->assertNull(Auth::guard('customer')->user());
        $this->assertFalse(Auth::guard('customer')->check());
    }

    public function test_when_logging_out_an_event_is_published()
    {
        Event::fake();

        $customer = $this->orderContext->createCustomer();
        $customerLogin = $this->orderContext->createCustomerLogin($customer);

        Auth::guard('customer')->login(CustomerModel::first());
        $this->assertEquals($customer->customerId->get(), Auth::guard('customer')->user()->customer_id);

        $response = $this->get(route('customer.logout'));

        Event::assertDispatched(CustomerHasLoggedOut::class);
    }

    public function test_it_will_redirect_if_logged_in_when_trying_to_log_in()
    {
        $customer = $this->orderContext->createCustomer();
        $customerLogin = $this->orderContext->createCustomerLogin($customer);

        Auth::guard('customer')->login(CustomerModel::first());

        $this->assertEquals($customer->customerId->get(), Auth::guard('customer')->user()->customer_id);

        $response = $this->post(route('customer.login.store'), [
            'email' => 'ben@thinktomorrow.be',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('customer.index'));
    }
}
