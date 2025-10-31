<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use function route;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerHasLoggedIn;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;

class CustomerRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addLocation(__DIR__ . '/views');
        $this->app['view']->addNamespace('trader', __DIR__ . '/views/shop');
    }

    public function test_it_shows_register_form()
    {
        $response = $this->get(route('customer.register'));
        $response->assertStatus(200);
        $response->assertViewIs('trader::customer.auth.register');
    }

    public function test_it_can_register_a_new_customer()
    {
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

        $this->assertTrue(Auth::guard('customer')->check());
        $this->assertEquals($customer->customer_id, Auth::guard('customer')->id());

        $response->assertRedirect(route('customer.index'));
        Event::assertDispatched(CustomerHasLoggedIn::class);
    }

    public function test_it_requires_all_fields()
    {
        $response = $this->from(route('customer.register'))->post(route('customer.register.store'), []);

        $response->assertRedirect(route('customer.register'));
        $response->assertSessionHasErrors(['firstname', 'lastname', 'email', 'password']);
        $this->assertCount(0, CustomerModel::all());
    }

    public function test_it_requires_unique_email()
    {
        $this->createDummyCustomer();

        $response = $this->from(route('customer.register'))->post(route('customer.register.store'), [
            'firstname' => 'Ben',
            'lastname' => 'Doe',
            'email' => 'ben@thinktomorrow.be',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('customer.register'));
        $response->assertSessionHasErrors(['email']);
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
        $this->disableExceptionHandling();
        
        $customer = $this->createDummyCustomer();

        $this->actingAs($customer, 'customer');
        $response = $this->get(route('customer.register'));

        $response->assertRedirect(route('customer.index'));
    }

    private function createDummyCustomer(): CustomerModel
    {
        $model = new CustomerModel();
        $model->customer_id = 'cust_12345';
        $model->email = 'ben@thinktomorrow.be';
        $model->password = bcrypt('22222222');
        $model->is_business = false;
        $model->locale = 'en';
        $model->save();

        return $model;
    }
}
