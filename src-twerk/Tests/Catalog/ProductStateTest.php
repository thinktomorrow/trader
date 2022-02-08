<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Thinktomorrow\Trader\Common\State\StateException;
use Thinktomorrow\Trader\Catalog\Products\Ports\ProductModel;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductState;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductStateMachine;

final class ProductStateTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_init_state()
    {
        $state = ProductState::fromString(ProductState::AVAILABLE);

        $this->assertTrue($state->is(ProductState::AVAILABLE));
        $this->assertFalse($state->is(ProductState::UNAVAILABLE));
    }

    /** @test */
    public function default_product_state_is_pending()
    {
        $product = ProductModel::find($this->createProduct()->getId());

        $this->assertEquals(ProductState::AVAILABLE, $product->getProductState());
    }

    /** @test */
    public function it_can_apply_a_transition()
    {
        $product = ProductModel::find($this->createProduct()->getId());

        app()->make(ProductStateMachine::class, ['object' => $product])->apply('mark_unavailable');

        $this->assertEquals(ProductState::UNAVAILABLE, $product->getProductState());
    }

    /** @test */
    public function it_cannot_apply_an_invalid_transition()
    {
        $this->expectException(StateException::class);

        $product = ProductModel::find($this->createProduct()->getId());

        app()->make(ProductStateMachine::class, ['object' => $product])->apply('restore');

        $this->assertEquals(ProductState::AVAILABLE, $product->getProductState());
    }

    /** @test */
    public function it_can_query_records_scoped_by_state()
    {
        // TODO: put in repo test
        $this->markTestIncomplete();
    }
}
