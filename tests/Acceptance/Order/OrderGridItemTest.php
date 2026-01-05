<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use DateTime;
use Money\Money;
use Tests\Acceptance\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultOrderGridItem;

class OrderGridItemTest extends TestCase
{
    use TestHelpers;

    public function test_it_can_create_a_order_grid_item()
    {
        $order = DefaultOrderGridItem::fromMappedData(array_merge($this->orderContext->createDefaultOrder()->getMappedData(), [
        ]), []);

        $this->assertEquals('order-aaa', $order->getOrderId());
        $this->assertEquals('order-aaa-ref', $order->getOrderReference());
        $this->assertEquals('order-aaa-invoice-ref', $order->getInvoiceReference());
        $this->assertEquals(DefaultOrderState::cart_pending->value, $order->getOrderState());

        $this->assertEquals('order-aaa-ref', $order->getTitle());
        $this->assertEquals('', $order->getDescription());
        $this->assertEquals('/admin/orders/order-aaa', $order->getUrl());

        $this->assertEquals(Money::EUR('82500'), $order->getSubtotalExcl());
        $this->assertEquals(Money::EUR('100000'), $order->getSubtotalIncl());
        $this->assertEquals(Money::EUR('4132'), $order->getShippingCostExcl());
        $this->assertEquals(Money::EUR('5000'), $order->getShippingCostIncl());
        $this->assertEquals(Money::EUR('1653'), $order->getPaymentCostExcl());
        $this->assertEquals(Money::EUR('2000'), $order->getPaymentCostIncl());
        $this->assertEquals(Money::EUR('5785'), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR('7000'), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR('82500'), $order->getTotalExcl());
        $this->assertEquals(Money::EUR('17500'), $order->getTotalVat());
        $this->assertEquals(Money::EUR('100000'), $order->getTotalIncl());
    }

    public function test_it_can_return_formatted_prices()
    {
        $order = DefaultOrderGridItem::fromMappedData(array_merge($this->orderContext->createDefaultOrder()->getMappedData(), [
        ]), []);

        $this->assertEquals('€ 825', $order->getFormattedSubtotalExcl());
        $this->assertEquals('€ 1.000', $order->getFormattedSubtotalIncl());
        $this->assertEquals('€ 41,32', $order->getFormattedShippingCostExcl());
        $this->assertEquals('€ 50', $order->getFormattedShippingCostIncl());
        $this->assertEquals('€ 16,53', $order->getFormattedPaymentCostExcl());
        $this->assertEquals('€ 20', $order->getFormattedPaymentCostIncl());
        $this->assertEquals('€ 57,85', $order->getFormattedDiscountTotalExcl());
        $this->assertEquals('€ 70', $order->getFormattedDiscountTotalIncl());
        $this->assertEquals('€ 825', $order->getFormattedTotalExcl());
        $this->assertEquals('€ 175', $order->getFormattedTotalVat());
        $this->assertEquals('€ 1.000', $order->getFormattedTotalIncl());
    }

    public function test_it_can_get_important_timestamps()
    {
        $gridItem = DefaultOrderGridItem::fromMappedData(array_merge($this->orderContext->createDefaultOrder()->getMappedData(), [
            'confirmed_at' => $confirmed_at = '2022-02-02 10:10:10',
            'paid_at' => $paid_at = '2022-02-03 10:10:10',
            'delivered_at' => $delivered_at = '2022-02-04 10:10:10',
        ]), []);

        $this->assertEquals(new DateTime($confirmed_at), $gridItem->getConfirmedAt());
        $this->assertEquals(new DateTime($paid_at), $gridItem->getPaidAt());
        $this->assertEquals(new DateTime($delivered_at), $gridItem->getDeliveredAt());
    }

    public function test_it_can_get_shopper_details()
    {
        $gridItem = DefaultOrderGridItem::fromMappedData(array_merge($this->orderContext->createDefaultOrder()->getMappedData(), [
        ]), [
            'email' => 'ben@thinktomorrow.be',
            'is_business' => true,
            'customer_id' => 'taxon-1',
        ]);

        $this->assertEquals('ben@thinktomorrow.be', $gridItem->getShopperTitle());
        $this->assertTrue($gridItem->isBusiness());
        $this->assertTrue($gridItem->hasCustomer());
        $this->assertEquals('/admin/customers/taxon-1', $gridItem->getCustomerUrl());
    }
}
