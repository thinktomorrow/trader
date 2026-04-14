<?php

declare(strict_types=1);

namespace Tests\Unit;

use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    //    use TestHelpers;

    protected CatalogContext $catalogContext;

    protected OrderContext $orderContext;

    protected function setUp(): void
    {
        parent::setUp();

        CatalogContext::setUp();
        OrderContext::setUp();

        $this->catalogContext = CatalogContext::inMemory();
        $this->orderContext = OrderContext::inMemory();
    }

    protected function tearDown(): void
    {
        CatalogContext::tearDown();
        OrderContext::tearDown();

        parent::tearDown();
    }
}
