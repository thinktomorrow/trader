<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderPricingSnapshot;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\PricingSnapshotMismatchException;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\PricingSnapshotNotCalculated;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class OrderTotalsTest extends TestCase
{
    public function test_it_can_get_default_totals()
    {
        $order = Order::create(
            OrderId::fromString('order-aaa'),
            OrderReference::fromString('ORDER-0001'),
            DefaultOrderState::confirmed,
        );

        $this->assertEquals(Cash::zero(), $order->getSubtotalExcl());
        $this->assertEquals(Cash::zero(), $order->getSubtotalIncl());
        $this->assertEquals(Cash::zero(), $order->getShippingCostExcl());
        $this->assertEquals(Cash::zero(), $order->getPaymentCostExcl());
        $this->assertEquals(Cash::zero(), $order->getDiscountTotalExcl());
        $this->assertEquals(Cash::zero(), $order->getTotalExcl());
    }

    public function test_it_can_get_calculated_totals()
    {
        $order = $this->orderContext->createDefaultOrder();
        $this->orderContext->addDiscountToOrder($order, $this->orderContext->createOrderDiscount());

        $this->assertEquals(Money::EUR('166'), $order->getSubtotalExcl());
        $this->assertEquals(Money::EUR('200'), $order->getSubtotalIncl());
        $this->assertEquals(Money::EUR('50'), $order->getShippingCostExcl());
        $this->assertEquals(Money::EUR('50'), $order->getPaymentCostExcl());
        $this->assertEquals(Money::EUR('15'), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR('251'), $order->getTotalExcl());
    }

    public function test_it_can_get_totals_incl_from_snapshot()
    {
        $order = $this->orderContext->createDefaultOrder();
        $this->orderContext->addDiscountToOrder($order, $this->orderContext->createOrderDiscount());

        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);

        $this->assertEquals(Money::EUR('200'), $order->getSubtotalIncl());
        $this->assertEquals(Money::EUR('61'), $order->getShippingCostIncl());
        $this->assertEquals(Money::EUR('61'), $order->getPaymentCostIncl());
        $this->assertEquals(Money::EUR('18'), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR('53'), $order->getTotalVat());
        $this->assertEquals(Money::EUR('304'), $order->getTotalIncl());

        $vatLines = $order->getVatLines();

        $this->assertEquals(1, count($vatLines));

        $vatLine = $vatLines[21]; // Keys are vat percentages

        $this->assertEquals(Money::EUR(251), $vatLine->getTaxableBase());
        $this->assertEquals(Money::EUR(53), $vatLine->getVatAmount());
        $this->assertEquals(VatPercentage::fromString('21'), $vatLine->getVatPercentage());
    }

    public function test_it_subtracts_shipping_discounts_from_shipping_and_order_totals(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $order->getShippings()[0]->addDiscount($this->orderContext->createShippingDiscount());

        $this->assertEquals(Money::EUR('35'), $order->getShippingCostExcl());
        $this->assertEquals(Money::EUR('251'), $order->getTotalExcl());

        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);

        $this->assertEquals(Money::EUR('43'), $order->getShippingCostIncl());
        $this->assertEquals(Money::EUR('61'), $order->getPaymentCostIncl());
        $this->assertEquals(Money::EUR('0'), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR('304'), $order->getTotalIncl());
    }

    public function test_it_subtracts_payment_discounts_from_payment_and_order_totals(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $order->getPayments()[0]->addDiscount($this->orderContext->createPaymentDiscount());

        $this->assertEquals(Money::EUR('35'), $order->getPaymentCostExcl());
        $this->assertEquals(Money::EUR('251'), $order->getTotalExcl());

        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);

        $this->assertEquals(Money::EUR('61'), $order->getShippingCostIncl());
        $this->assertEquals(Money::EUR('43'), $order->getPaymentCostIncl());
        $this->assertEquals(Money::EUR('0'), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR('304'), $order->getTotalIncl());
    }

    public function test_it_combines_service_and_order_discounts_without_double_counting(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $order->getShippings()[0]->addDiscount($this->orderContext->createShippingDiscount());
        $order->getPayments()[0]->addDiscount($this->orderContext->createPaymentDiscount());
        $this->orderContext->addDiscountToOrder($order, $this->orderContext->createOrderDiscount());

        $this->assertEquals(Money::EUR('35'), $order->getShippingCostExcl());
        $this->assertEquals(Money::EUR('35'), $order->getPaymentCostExcl());
        $this->assertEquals(Money::EUR('15'), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR('221'), $order->getTotalExcl());

        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);

        $this->assertEquals(Money::EUR('43'), $order->getShippingCostIncl());
        $this->assertEquals(Money::EUR('43'), $order->getPaymentCostIncl());
        $this->assertEquals(Money::EUR('18'), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR('268'), $order->getTotalIncl());
    }

    public function test_full_including_vat_shipping_discount_reaches_exact_zero(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $shipping = $order->getShippings()[0];
        $shipping->updateCost(DefaultServicePrice::fromIncludingVat(Money::EUR(700), Money::EUR(579)));
        $shipping->addDiscount($this->orderContext->createShippingDiscount('order-aaa', $shipping->shippingId->get(), 'gross-shipping-discount', [
            'total_excl' => '700',
            'total_incl' => '800',
            'tax_mode' => TaxMode::Inclusive->value,
        ]));

        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);

        $this->assertEquals(Money::EUR(0), $order->getShippingCostExcl());
        $this->assertEquals(Money::EUR(0), $order->getShippingCostIncl());
    }

    public function test_snapshot_becomes_stale_when_authority_changes_without_changing_net_total(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $order->getShippings()[0]->updateCost(DefaultServicePrice::fromIncludingVat(Money::EUR(61), Money::EUR(50)));

        $this->assertFalse($order->hasUpToDatePricingSnapshot());
        $this->expectException(PricingSnapshotMismatchException::class);

        $order->getShippingCostIncl();
    }

    public function test_snapshot_becomes_stale_when_shipping_and_payment_prices_are_swapped(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $order->getShippings()[0]->updateCost(DefaultServicePrice::fromIncludingVat(Money::EUR(61), Money::EUR(50)));
        $order->getPayments()[0]->updateCost(DefaultServicePrice::fromIncludingVat(Money::EUR(121), Money::EUR(100)));
        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);

        $order->getShippings()[0]->updateCost(DefaultServicePrice::fromIncludingVat(Money::EUR(121), Money::EUR(100)));
        $order->getPayments()[0]->updateCost(DefaultServicePrice::fromIncludingVat(Money::EUR(61), Money::EUR(50)));

        $this->assertFalse($order->hasUpToDatePricingSnapshot());
    }

    public function test_legacy_snapshot_without_fingerprint_remains_readable_when_pricing_is_frozen(): void
    {
        $order = Order::create(
            OrderId::fromString('legacy-order'),
            OrderReference::fromString('LEGACY-ORDER'),
            DefaultOrderState::confirmed,
        );
        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);
        $state = array_merge($order->getMappedData(), [
            'order_state' => $order->getOrderState(),
            'pricing_fingerprint' => null,
        ]);
        $hydratedOrder = Order::fromMappedData($state);

        $this->assertTrue($hydratedOrder->hasUpToDatePricingSnapshot());
        $this->assertEquals($order->getTotalIncl(), $hydratedOrder->getTotalIncl());
    }

    public function test_legacy_snapshot_without_fingerprint_is_not_readable_while_pricing_is_mutable(): void
    {
        $order = Order::create(
            OrderId::fromString('legacy-cart'),
            OrderReference::fromString('LEGACY-CART'),
            DefaultOrderState::cart_pending,
        );
        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);
        $state = array_merge($order->getMappedData(), [
            'order_state' => $order->getOrderState(),
            'pricing_fingerprint' => null,
        ]);
        $hydratedOrder = Order::fromMappedData($state);

        $this->assertFalse($hydratedOrder->hasUpToDatePricingSnapshot());
        $this->expectException(PricingSnapshotMismatchException::class);

        $hydratedOrder->getTotalIncl();
    }

    public function test_malformed_persisted_vat_lines_invalidate_snapshot_instead_of_blocking_hydration(): void
    {
        $order = Order::create(
            OrderId::fromString('malformed-order'),
            OrderReference::fromString('MALFORMED-ORDER'),
            DefaultOrderState::confirmed,
        );
        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);
        $state = array_merge($order->getMappedData(), [
            'order_state' => $order->getOrderState(),
            'vat_lines' => json_encode([[
                'taxable_base' => '0',
                'vat_amount' => '0',
                'vat_percentage' => '21.1234567',
            ]]),
        ]);

        $hydratedOrder = Order::fromMappedData($state);

        $this->assertFalse($hydratedOrder->hasUpToDatePricingSnapshot());
        $this->expectException(PricingSnapshotNotCalculated::class);

        $hydratedOrder->getTotalIncl();
    }

    public function test_invalid_vat_lines_json_invalidates_snapshot_instead_of_blocking_hydration(): void
    {
        $order = Order::create(
            OrderId::fromString('invalid-json-order'),
            OrderReference::fromString('INVALID-JSON-ORDER'),
            DefaultOrderState::confirmed,
        );
        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);
        $state = array_merge($order->getMappedData(), [
            'order_state' => $order->getOrderState(),
            'vat_lines' => '{invalid',
        ]);

        $hydratedOrder = Order::fromMappedData($state);

        $this->assertFalse($hydratedOrder->hasUpToDatePricingSnapshot());
        $this->expectException(PricingSnapshotNotCalculated::class);

        $hydratedOrder->getTotalIncl();
    }
}
