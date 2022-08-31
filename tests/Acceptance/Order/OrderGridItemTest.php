<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Tests\TestHelpers;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultOrderGridItem;

class OrderGridItemTest extends TestCase
{
    use TestHelpers;

    /** @test */
    public function it_can_create_a_order_grid_item()
    {
        $gridItem = DefaultOrderGridItem::fromMappedData(array_merge($this->createDefaultOrder()->getMappedData(), [
        ]), []);

        $this->assertEquals('xxx', $gridItem->getOrderId());
        $this->assertEquals('xx-ref', $gridItem->getOrderReference());
        $this->assertEquals('xx-invoice-ref', $gridItem->getInvoiceReference());
        $this->assertEquals(OrderState::cart_revived->value, $gridItem->getOrderState());

        $this->assertEquals('€ 4,20', $gridItem->getTotalPrice());
        $this->assertEquals('xx-ref', $gridItem->getTitle());
        $this->assertEquals('', $gridItem->getDescription());
        $this->assertEquals('/admin/orders/xxx', $gridItem->getUrl());
    }

    /** @test */
    public function it_can_get_important_timestamps()
    {
        $gridItem = DefaultOrderGridItem::fromMappedData(array_merge($this->createDefaultOrder()->getMappedData(), [
            'confirmed_at' => $confirmed_at = '2022-02-02 10:10:10',
            'paid_at' => $paid_at = '2022-02-03 10:10:10',
            'delivered_at' => $delivered_at = '2022-02-04 10:10:10',
        ]), []);

        $this->assertEquals($confirmed_at, $gridItem->getConfirmedAt());
        $this->assertEquals($paid_at, $gridItem->getPaidAt());
        $this->assertEquals($delivered_at, $gridItem->getDeliveredAt());
    }

    /** @test */
    public function it_can_get_shopper_details()
    {
        $gridItem = DefaultOrderGridItem::fromMappedData(array_merge($this->createDefaultOrder()->getMappedData(), [
        ]), [
            'email' => 'ben@thinktomorrow.be',
            'customer_id' => 'ccc-123',
        ]);

        $this->assertEquals('ben@thinktomorrow.be', $gridItem->getShopperTitle());
        $this->assertTrue($gridItem->hasCustomer());
        $this->assertEquals('/admin/customers/ccc-123', $gridItem->getCustomerUrl());
    }
}
