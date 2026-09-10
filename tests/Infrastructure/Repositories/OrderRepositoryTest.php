<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Money\Money;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Order\State\Order\AbandonOrder;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\ShopperId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

final class OrderRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_an_order()
    {
        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $order = $orderContext->dontPersist()->createOrder();

            $repository = $orderContext->repos()->orderRepository();

            $repository->save($order);

            $order->releaseEvents();

            $this->assertEquals($order, $repository->find($order->orderId));
        }
    }

    public function test_it_can_delete_an_order()
    {
        $ordersNotFound = 0;

        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $order = $orderContext->createOrder();

            $repository = $orderContext->repos()->orderRepository();

            $repository->delete($order->orderId);

            try {
                $repository->find($order->orderId);
            } catch (CouldNotFindOrder $e) {
                $ordersNotFound++;
            }
        }

        $this->assertCount($ordersNotFound, OrderContext::drivers());
    }

    public function test_it_can_generate_a_next_reference()
    {
        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->orderRepository();

            $this->assertInstanceOf(OrderId::class, $repository->nextReference());
        }
    }

    public function test_it_can_generate_a_next_external_reference()
    {
        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->orderRepository();

            $this->assertInstanceOf(OrderReference::class, $repository->nextExternalReference());
        }
    }

    public function test_it_can_generate_a_next_shipping_and_shopper_reference()
    {
        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->orderRepository();

            $this->assertInstanceOf(ShippingId::class, $repository->nextShippingReference());
            $this->assertInstanceOf(ShopperId::class, $repository->nextShopperReference());
        }
    }

    public function test_it_repairs_stale_vat_snapshot_when_abandoning_order(): void
    {
        foreach ([OrderContext::mysql(), OrderContext::laravel()] as $orderContext) {
            $order = $orderContext->createDefaultOrder('vatfix-'.$orderContext->driverName);

            DB::table('trader_orders')
                ->where('order_id', $order->orderId->get())
                ->update([
                    'total_incl' => 0,
                    'total_vat' => 0,
                    'discount_incl' => 0,
                    'shipping_cost_incl' => 0,
                    'payment_cost_incl' => 0,
                    'vat_lines' => json_encode([]),
                ]);

            $orderContext->apps()->orderStateApplication()->abandonOrder(new AbandonOrder($order->orderId->get()));

            $savedOrder = $orderContext->findOrder($order->orderId);

            $this->assertEquals(DefaultOrderState::cart_abandoned, $savedOrder->getOrderState());
            $this->assertTrue($savedOrder->hasUpToDatePricingSnapshot());
        }
    }

    public function test_it_persists_net_service_totals_after_shipping_and_payment_discounts(): void
    {
        foreach ([OrderContext::mysql(), OrderContext::laravel()] as $orderContext) {
            $orderContext->createPromo('service-promo', [], [
                $orderContext->createPromoDiscount('service-promo', 'service-promo-discount'),
            ]);

            $orderId = $orderContext->driverName === 'mysql' ? 'srvdisc-mysql' : 'srvdisc-laravel';
            $order = $orderContext->createDefaultOrder($orderId);

            $order->getShippings()[0]->addDiscount(
                $orderContext->createShippingDiscount($order->orderId->get(), $order->getShippings()[0]->shippingId->get(), 'shipdisc', [
                    'promo_id' => 'service-promo',
                    'promo_discount_id' => 'service-promo-discount',
                ])
            );

            $order->getPayments()[0]->addDiscount(
                $orderContext->createPaymentDiscount($order->orderId->get(), $order->getPayments()[0]->paymentId->get(), 'paydisc', [
                    'promo_id' => 'service-promo',
                    'promo_discount_id' => 'service-promo-discount',
                ])
            );

            $orderContext->saveOrder($order);

            $savedOrder = $orderContext->findOrder($order->orderId);

            $this->assertEquals(35, (int) $savedOrder->getShippingCostExcl()->getAmount());
            $this->assertEquals(35, (int) $savedOrder->getPaymentCostExcl()->getAmount());
            $this->assertEquals(236, (int) $savedOrder->getTotalExcl()->getAmount());
            $this->assertCount(1, $savedOrder->getShippings()[0]->getDiscounts());
            $this->assertCount(1, $savedOrder->getPayments()[0]->getDiscounts());
            $this->assertTrue($savedOrder->hasUpToDatePricingSnapshot());
        }
    }

    public function test_it_preserves_including_vat_authority_for_services_and_discounts(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $orderId = 'gross-'.$orderContext->driverName;
            $order = $orderContext->createDefaultOrder($orderId);
            $order->getShippings()[0]->updateCost(DefaultServicePrice::fromIncludingVat(Money::EUR(700), Money::EUR(579)));
            $order->getPayments()[0]->updateCost(DefaultServicePrice::fromIncludingVat(Money::EUR(121), Money::EUR(100)));
            $orderContext->addDiscountToOrder($order, $orderContext->createOrderDiscount($orderId, 'gross-discount', [
                'total_excl' => '10',
                'total_incl' => '12',
                'tax_mode' => TaxMode::Inclusive->value,
                'promo_id' => null,
                'promo_discount_id' => null,
            ]));

            $orderContext->saveOrder($order);
            $savedOrder = $orderContext->findOrder($order->orderId);

            $this->assertSame(TaxMode::Inclusive, $savedOrder->getShippings()[0]->getShippingCost()->getTaxMode());
            $this->assertSame(TaxMode::Inclusive, $savedOrder->getPayments()[0]->getPaymentCost()->getTaxMode());
            $this->assertSame(TaxMode::Inclusive, $savedOrder->getDiscounts()[0]->getDiscountPrice()->getTaxMode());
            $this->assertEquals(Money::EUR(700), $savedOrder->getShippingCostIncl(), $orderContext->driverName);
            $this->assertEquals(Money::EUR(121), $savedOrder->getPaymentCostIncl(), $orderContext->driverName);
            $this->assertEquals(Money::EUR(12), $savedOrder->getDiscountTotalIncl(), $orderContext->driverName);
            $this->assertTrue($savedOrder->hasUpToDatePricingSnapshot());
        }
    }

    public function test_it_preserves_historical_pricing_when_a_confirmed_order_is_saved(): void
    {
        foreach ([OrderContext::mysql(), OrderContext::laravel()] as $orderContext) {
            $orderId = 'frozen-'.$orderContext->driverName;
            $order = $orderContext->createDefaultOrder($orderId);
            $order->updateState(DefaultOrderState::confirmed);
            $orderContext->saveOrder($order);

            $storedLinePricing = (array) DB::table('trader_order_lines')
                ->where('order_id', $orderId)
                ->where('line_id', $orderId.':line-aaa')
                ->first(['unit_price_excl', 'unit_price_incl', 'total_excl', 'total_incl', 'total_vat', 'quantity']);
            $storedShippingPricing = (array) DB::table('trader_order_shipping')
                ->where('order_id', $orderId)
                ->first(['cost_excl', 'cost_incl', 'cost_tax_mode', 'discount_excl', 'discount_incl', 'total_excl', 'total_incl']);

            $order->getLines()[0]->updatePrice(DefaultItemPrice::fromExcludingVat(Money::EUR(999), VatPercentage::fromString('21')));
            $order->getShippings()[0]->updateCost(DefaultServicePrice::fromExcludingVat(Money::EUR(999)));
            $orderContext->saveOrder($order);

            $this->assertSame($storedLinePricing, (array) DB::table('trader_order_lines')
                ->where('order_id', $orderId)
                ->where('line_id', $orderId.':line-aaa')
                ->first(array_keys($storedLinePricing)), $orderContext->driverName);
            $this->assertSame($storedShippingPricing, (array) DB::table('trader_order_shipping')
                ->where('order_id', $orderId)
                ->first(array_keys($storedShippingPricing)), $orderContext->driverName);

            $savedOrder = $orderContext->findOrder($order->orderId);

            $this->assertEquals(Money::EUR(166), $savedOrder->getSubtotalExcl(), $orderContext->driverName);
            $this->assertEquals(Money::EUR(50), $savedOrder->getShippingCostExcl(), $orderContext->driverName);
            $this->assertTrue($savedOrder->hasUpToDatePricingSnapshot(), $orderContext->driverName);
        }
    }
}
