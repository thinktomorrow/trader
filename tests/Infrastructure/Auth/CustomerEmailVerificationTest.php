<?php

namespace Tests\Infrastructure\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;

class CustomerEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addLocation(__DIR__.'/views');
        $this->app['view']->addNamespace('trader', __DIR__.'/views/shop');

        config()->set('trader.webmaster_email', 'example@trader');
        config()->set('trader.webmaster_name', 'Trader Webmaster');
    }

    public function test_it_shows_verification_notice_page_for_unverified_customer()
    {
        $customer = $this->createUnverifiedCustomer();

        $response = $this->actingAs($customer, 'customer')
            ->get(route('customer.verification.show'));

        $response->assertStatus(200);
        $response->assertViewIs('trader::customer.auth.verify');
    }

    public function test_it_redirects_verified_customer_away_from_verification_page()
    {
        $customer = $this->createVerifiedCustomer();

        $response = $this->actingAs($customer, 'customer')
            ->get(route('customer.verification.show'));

        $response->assertRedirect(route('customer.index'));
    }

    public function test_it_marks_customer_as_verified_when_visiting_signed_verification_url()
    {
        Event::fake([Verified::class]);

        $customer = $this->createUnverifiedCustomer();

        $this->actingAs($customer, 'customer');

        $verificationUrl = URL::temporarySignedRoute(
            'customer.verification.verify',
            now()->addMinutes(60),
            ['id' => $customer->getKey(), 'hash' => sha1($customer->email)]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect(route('customer.login'));

        $this->assertNotNull($customer->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_it_does_not_verify_if_signature_is_invalid()
    {
        $customer = $this->createUnverifiedCustomer();

        $this->actingAs($customer, 'customer');

        $invalidUrl = route('customer.verification.verify', [
            'id' => $customer->getKey(),
            'hash' => 'invalid-hash',
        ]);

        $response = $this->get($invalidUrl);

        $response->assertRedirect(route('customer.verification.show'));

        $this->assertNull($customer->fresh()->email_verified_at);
    }

    public function test_it_can_resend_verification_email()
    {
        $customer = $this->createUnverifiedCustomer();

        $this->actingAs($customer, 'customer');

        $response = $this->post(route('customer.verification.resend'));

        $response->assertRedirect();
        $response->assertSessionHas('resent');
    }

    private function createUnverifiedCustomer(): CustomerModel
    {
        $model = new CustomerModel;
        $model->customer_id = 'cust_unverified';
        $model->email = 'unverified@thinktomorrow.be';
        $model->password = bcrypt('password123');
        $model->email_verified_at = null;
        $model->is_business = false;
        $model->locale = 'en';
        $model->data = [
            'firstname' => 'Unverified',
            'lastname' => 'Customer',
        ];
        $model->save();

        return $model;
    }

    private function createVerifiedCustomer(): CustomerModel
    {
        $model = new CustomerModel;
        $model->customer_id = 'cust_verified';
        $model->email = 'verified@thinktomorrow.be';
        $model->password = bcrypt('password123');
        $model->email_verified_at = now();
        $model->is_business = false;
        $model->locale = 'en';
        $model->save();

        return $model;
    }
}
