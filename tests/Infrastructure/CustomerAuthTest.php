<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function non_authenticated_are_kept_out()
    {
        $response = $this->get(route('customer.home'));
        $response->assertRedirect(route('customer.login'));
    }


    /** @test */
    public function it_returns_a_json_error_if_unauthenticated_request_expects_json_response()
    {
        $response = $this->get(route('customer.home'), [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function entering_valid_login_credentials_lets_you_pass()
    {
        $customer = $this->createACustomerLogin();

        $response = $this->post(route('customer.login.store'), [
            'email' => 'ben@thinktomorrow.be',
            'password' => '123456',
        ]);

        $this->assertTrue(Auth::guard('customer')->check());
        $this->assertEquals($customer->customerId->get(), Auth::guard('customer')->user()->customer_id);

        $response->assertRedirect(route('customer.home'));
        $this->assertFalse(session()->has('errors'));
    }

    /** @test */
    public function entering_invalid_login_credentials_keeps_you_out()
    {
        $this->createACustomerLogin();

        // Enter invalid credentials
        $response = $this->post(route('customer.login.store'), [
            'email' => 'ben@thinktomorrow.be',
            'password' => 'xxx',
        ]);

        $this->assertNull(Auth::guard('customer')->user());
        $this->assertTrue(session()->has('errors'));
        $response->assertRedirect('/');
    }

    /** @test */
    public function it_displays_customer_page_for_authenticated()
    {
        $customer = $this->createACustomerLogin();

        $response = $this->actingAs(CustomerModel::first(), 'customer')
            ->get(route('customer.home'));

        $response->assertStatus(200);
        $this->assertFalse(session()->has('errors'));
    }

    /** @test */
    public function it_redirects_authenticated_customer_to_intended_page()
    {
        $this->createACustomerLogin();

        $this->get(route('customer.orders'))
             ->assertRedirect(route('customer.login'));

        $response = $this->post(route('customer.login.store'), [
            'email' => 'ben@thinktomorrow.be',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('customer.orders'));
    }

    /** @test */
    public function customer_is_attached_to_current_cart_after_login()
    {
        $this->markTestSkipped();
    }

    /** @test */
    public function it_can_log_out()
    {
        $customer = $this->createACustomerLogin();

        Auth::guard('customer')->login(CustomerModel::first());
        $this->assertEquals($customer->customerId->get(), Auth::guard('customer')->user()->customer_id);

        $response = $this->get(route('customer.logout'));
        $response->assertRedirect('/');

        $this->assertNull(Auth::guard('customer')->user());
        $this->assertFalse(Auth::guard('customer')->check());
    }

    /** @test */
    public function it_will_redirect_if_logged_in_when_trying_to_log_in()
    {
        $customer = $this->createACustomerLogin();

        Auth::guard('customer')->login(CustomerModel::first());

        $this->assertEquals($customer->customerId->get(), Auth::guard('customer')->user()->customer_id);

        $response = $this->post(route('customer.login.store'), [
            'email' => 'ben@thinktomorrow.be',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('customer.home'));
    }
}
