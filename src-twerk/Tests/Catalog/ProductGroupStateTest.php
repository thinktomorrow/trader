<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Thinktomorrow\Trader\Common\State\StateException;
use Thinktomorrow\Trader\Catalog\Products\Ports\ProductGroupModel;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupState;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupStateMachine;

final class ProductGroupStateTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_init_state()
    {
        $state = ProductGroupState::fromString(ProductGroupState::DRAFT);

        $this->assertTrue($state->is(ProductGroupState::DRAFT));
        $this->assertFalse($state->is(ProductGroupState::ARCHIVED));
    }

    /** @test */
    public function default_product_state_is_pending()
    {
        $productGroup = ProductGroupModel::find($this->createProductGroup()->getId());

        $this->assertEquals(ProductGroupState::DRAFT, $productGroup->getProductGroupState());
    }

    /** @test */
    public function it_can_apply_a_transition()
    {
        $productGroup = ProductGroupModel::find($this->createProductGroup()->getId());

        app()->make(ProductGroupStateMachine::class, ['object' => $productGroup])->apply('publish');

        $this->assertEquals(ProductGroupState::ONLINE, $productGroup->getProductGroupState());
    }

    /** @test */
    public function it_cannot_apply_an_invalid_transition()
    {
        $this->expectException(StateException::class);

        $productGroup = ProductGroupModel::find($this->createProductGroup()->getId());

        app()->make(ProductGroupStateMachine::class, ['object' => $productGroup])->apply('revive');

        $this->assertEquals(ProductGroupState::DRAFT, $productGroup->getProductGroupState());
    }

    /** @test */
    public function it_can_query_records_scoped_by_state()
    {
        // TODO: put in repo test
        $this->markTestIncomplete();
    }
}
