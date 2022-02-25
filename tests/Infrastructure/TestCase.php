<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Tests\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Infrastructure\Shop\ShopServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use TestHelpers;
    use RefreshDatabase;

    public function getPackageProviders($app)
    {
        return [
            TraderServiceProvider::class,
            ShopServiceProvider::class,
        ];
    }
}
