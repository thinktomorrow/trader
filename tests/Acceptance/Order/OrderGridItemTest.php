<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use DateTime;
use Money\Money;
use Tests\Acceptance\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderVatSnapshot;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultOrderGridItem;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class OrderGridItemTest extends TestCase
{
    use TestHelpers;

    public function test_it_can_create_a_order_grid_item()
    {
        $order = $this->orderContext->createDefaultDiscountedOrder();

        // Refresh Vat snapshot
        (new TestContainer())->get(AdjustOrderVatSnapshot::class)->adjust($order);
        $this->orderContext->saveOrder($order);
        $order = $this->orderContext->findOrder($order->orderId);

        $order = DefaultOrderGridItem::fromMappedData($order->getMappedData(), []);

        $this->assertEquals('order-aaa', $order->getOrderId());
        $this->assertEquals('order-aaa-ref', $order->getOrderReference());
        $this->assertEquals('order-aaa-invoice-ref', $order->getInvoiceReference());
        $this->assertEquals(DefaultOrderState::cart_pending->value, $order->getOrderState());

        $this->assertEquals('order-aaa-ref', $order->getTitle());
        $this->assertEquals('', $order->getDescription());
        $this->assertEquals('/admin/orders/order-aaa', $order->getUrl());

        $this->assertEquals(Money::EUR('166'), $order->getSubtotalExcl());
        $this->assertEquals(Money::EUR('200'), $order->getSubtotalIncl());
        $this->assertEquals(Money::EUR('50'), $order->getShippingCostExcl());
        $this->assertEquals(Money::EUR('61'), $order->getShippingCostIncl());
        $this->assertEquals(Money::EUR('50'), $order->getPaymentCostExcl());
        $this->assertEquals(Money::EUR('61'), $order->getPaymentCostIncl());
        $this->assertEquals(Money::EUR('15'), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR('18'), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR('251'), $order->getTotalExcl());
        $this->assertEquals(Money::EUR('54'), $order->getTotalVat());
        $this->assertEquals(Money::EUR('305'), $order->getTotalIncl());
    }

    public function test_it_can_return_formatted_prices()
    {
        $order = $this->orderContext->createDefaultDiscountedOrder();

        // Refresh Vat snapshot
        (new TestContainer())->get(AdjustOrderVatSnapshot::class)->adjust($order);
        $this->orderContext->saveOrder($order);
        $order = $this->orderContext->findOrder($order->orderId);

        $order = DefaultOrderGridItem::fromMappedData($order->getMappedData(), []);

        $this->assertEquals('€ 1,66', $order->getFormattedSubtotalExcl());
        $this->assertEquals('€ 2', $order->getFormattedSubtotalIncl());
        $this->assertEquals('€ 0,50', $order->getFormattedShippingCostExcl());
        $this->assertEquals('€ 0,61', $order->getFormattedShippingCostIncl());
        $this->assertEquals('€ 0,50', $order->getFormattedPaymentCostExcl());
        $this->assertEquals('€ 0,61', $order->getFormattedPaymentCostIncl());
        $this->assertEquals('€ 0,15', $order->getFormattedDiscountTotalExcl());
        $this->assertEquals('€ 0,18', $order->getFormattedDiscountTotalIncl());
        $this->assertEquals('€ 2,51', $order->getFormattedTotalExcl());
        $this->assertEquals('€ 0,54', $order->getFormattedTotalVat());
        $this->assertEquals('€ 3,05', $order->getFormattedTotalIncl());
    }

    public function test_it_can_get_important_timestamps()
    {
        $order = $this->orderContext->createDefaultOrder();
        $this->orderContext->refreshOrder($order->orderId); // Because we get mapped data

        $gridItem = DefaultOrderGridItem::fromMappedData(array_merge($order->getMappedData(), [
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
        $order = $this->orderContext->createDefaultOrder();
        $this->orderContext->refreshOrder($order->orderId); // Because we get mapped data

        $gridItem = DefaultOrderGridItem::fromMappedData(array_merge($order->getMappedData(), [
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
