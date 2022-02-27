<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Notifications\ResetCustomerPasswordNotification;

class CustomerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_send_a_password_reset_mail()
    {
        Notification::fake();

        $customer = $this->createACustomerLogin();

        $response = $this->post(route('customer.password.email'),[
            'email' => 'ben@thinktomorrow.be'
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('trader_customer_password_resets', [
            'email' => 'ben@thinktomorrow.be',
        ]);

        Notification::assertSentTo(
            CustomerModel::first(),
            ResetCustomerPasswordNotification::class
        );
    }

    /** @test */
    public function it_cannot_send_a_password_reset_when_account_does_not_exist()
    {
        Notification::fake();

        $this->createACustomerLogin();

        $response = $this->post(route('customer.password.email'),[
            'email' => 'fake@example.com'
        ]);

        $this->assertDatabaseCount('trader_customer_password_resets', 0);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_can_reset_your_password()
    {
        $this->disableExceptionHandling();

        Notification::fake();
        $this->createACustomerLogin();

        // Create reset token manually so we can check the token
        DB::insert('INSERT INTO trader_customer_password_resets (email, token, created_at) VALUES(?, ?, ?)', [
            "ben@thinktomorrow.be",
            bcrypt("71594f253f7543eca5d884b37c637b0611b6a40809250c2e5ba2fbc9db74916c"),
            Carbon::now()
        ]);

        $response = $this->post(route('customer.password.reset.store'), [
            'token'                 => "71594f253f7543eca5d884b37c637b0611b6a40809250c2e5ba2fbc9db74916c",
            'email'                 => "ben@thinktomorrow.be",
            'password'              => "new-password",
            'password_confirmation' => "new-password",
        ]);

        $response->assertRedirect(route('customer.home'));


        Auth::guard('customer')->logout();

        $response = $this->post(route('customer.login.store'),[
            'email'     => 'ben@thinktomorrow.be',
            'password'  => 'new-password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('customer.home'));

        $this->assertTrue(Auth::guard('customer')->check());
    }


}
