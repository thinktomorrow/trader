<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\Acceptance\TestCase;

abstract class ProductContext extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }
}
