<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Shop\ShopServiceProvider;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;
use Thinktomorrow\Trader\Testing\Order\OrderContext;
use Thinktomorrow\Trader\Testing\Support\Catalog;
use Thinktomorrow\Trader\Testing\Support\Shop;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use TestHelpers;
    use RefreshDatabase;

    protected CatalogContext $catalogContext;
    protected OrderContext $orderContext;

    protected function getEnvironmentSetUp($app)
    {
        # Setup default database to use sqlite :memory:
        //        $app['config']->set('database.default', 'mysql');
        //        $app['config']->set('database.connections.mysql', [
        //            'driver' => 'mysql',
        //            'host' => '127.0.0.1',
        //            'port' => '3306',
        //            'database' => 'trader-test',
        //            'username' => 'root',
        //            'password' => null,
        //            'prefix' => '',
        //        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        CatalogContext::setUp();
        OrderContext::setUp();

        $this->catalogContext = CatalogContext::mysql();
        $this->orderContext = OrderContext::mysql();
    }

    public function getPackageProviders($app)
    {
        return [
            TraderServiceProvider::class,
            ShopServiceProvider::class,
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
