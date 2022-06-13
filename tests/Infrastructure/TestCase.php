<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Shop\ShopServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultTaxonNode;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantForCart;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        (new TestContainer())->add(TaxonNode::class, DefaultTaxonNode::class);
        (new TestContainer())->add(VariantForCart::class, DefaultVariantForCart::class);
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
        (new InMemoryProductRepository())->clear();
        (new InMemoryVariantRepository(new InMemoryProductRepository()))->clear();
        (new InMemoryTaxonRepository())->clear();

        parent::tearDown();
    }
}
