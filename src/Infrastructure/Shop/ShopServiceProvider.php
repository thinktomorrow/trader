<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\CustomerModel;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Middleware\CustomerAuthenticate;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Middleware\CustomerRedirectIfAuthenticated;

class ShopServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        // Default setup for customer auth routes: this should be set up per project.
        $this->loadRoutesFrom(__DIR__.'/CustomerAuth/routes.php');

        $this->bootCustomerGuard();
    }

    private function bootCustomerGuard()
    {
        // Load up middleware
        $this->app->make(Router::class)->aliasMiddleware('customer-auth', CustomerAuthenticate::class);
        $this->app->make(Router::class)->aliasMiddleware('customer-guest', CustomerRedirectIfAuthenticated::class);

        $this->app['config']["auth.providers.customer"] = [
            'driver' => 'eloquent',
            'model' => CustomerModel::class,
        ];

        $this->app['config']["auth.guards.customer"] = [
            'driver' => 'session',
            'provider' => 'customer',
        ];

        $this->app['config']["auth.passwords.customer"] = [
            'provider' => 'customer',
            'table' => 'trader_customer_password_resets',
            'expire' => 60,
            'throttle' => 60,
        ];
    }
}
