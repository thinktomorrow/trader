<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerCreated;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerHasLoggedIn;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;

use function route;

class CustomerRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();

        $this->app['view']->addLocation(__DIR__.'/views');
        $this->app['view']->addNamespace('trader', __DIR__.'/views/shop');
    }

    public function test_it_shows_register_form()
    {
        $response = $this->get(route('customer.register'));
        $response->assertStatus(200);
        $response->assertViewIs('trader::customer.auth.register');
    }

    public function test_it_can_register_a_new_customer()
    {
        $this->disableExceptionHandling();
        Event::fake();

        $response = $this->post(route('customer.register.store'), [
            'firstname' => 'Ben',
            'lastname' => 'Doe',
            'email' => 'ben@thinktomorrow.be',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $customer = DB::table('trader_customers')
            ->where('email', 'ben@thinktomorrow.be')
            ->first();

        $data = json_decode($customer->data, true);

        $this->assertEquals('Ben', data_get($data, 'firstname'));
        $this->assertEquals('Doe', data_get($data, 'lastname'));

        $customer = CustomerModel::first();

        $this->assertFalse(Auth::guard('customer')->check());

        $response->assertRedirect(route('customer.login'));
        Event::assertDispatched(CustomerCreated::class, function (CustomerCreated $event): bool {
            return $event->email->get() === 'ben@thinktomorrow.be';
        });
        Event::assertNotDispatched(CustomerHasLoggedIn::class);
    }

    public function test_it_requires_all_fields()
    {
        $response = $this->from(route('customer.register'))->post(route('customer.register.store'), []);

        $response->assertRedirect(route('customer.register'));
        $response->assertSessionHasErrors(['firstname', 'lastname', 'email', 'password']);
        $this->assertCount(0, CustomerModel::all());
    }

    public function test_it_avoids_displaying_email_error()
    {
        $customer = $this->orderContext->createCustomer();
        $this->orderContext->createCustomerLogin($customer);

        $response = $this->post(route('customer.register.store'), [
            'firstname' => 'Ben',
            'lastname' => 'Doe',
            'email' => 'ben@thinktomorrow.be',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('customer.login'));
        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseCount('trader_customers', 1);
    }

    public function test_it_validates_register_request()
    {
        $response = $this->from(route('customer.register'))->post(route('customer.register.store'), [
            'firstname' => null,
            'lastname' => 'Doe',
            'email' => 'ben@thinktomorrow.be',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('customer.register'));
        $response->assertSessionHasErrors(['firstname']);
    }

    public function test_it_requires_password_confirmation_to_match()
    {
        $response = $this->from(route('customer.register'))->post(route('customer.register.store'), [
            'firstname' => 'Ben',
            'lastname' => 'Doe',
            'email' => 'ben@thinktomorrow.be',
            'password' => 'secret123',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertRedirect(route('customer.register'));
        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseCount('trader_customers', 0);
    }

    public function test_it_redirects_authenticated_customers_away_from_register_page()
    {
        $customer = $this->orderContext->createCustomer();
        $customerLogin = $this->orderContext->createCustomerLogin($customer);

        $customerModel = CustomerModel::find($customer->customerId->get());

        $this->actingAs($customerModel, 'customer');
        $response = $this->get(route('customer.register'));

        $response->assertRedirect(route('customer.index'));
    }

    private function createDummyCustomer(): CustomerModel
    {
        $model = new CustomerModel;
        $model->customer_id = 'cust_12345';
        $model->email = 'ben@thinktomorrow.be';
        $model->password = bcrypt('22222222');
        $model->is_business = false;
        $model->locale = 'en';
        $model->save();

        return $model;
    }
}
