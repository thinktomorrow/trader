<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartRepository;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderVatSnapshot;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\OrderAlreadyInMerchantHands;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartLinePersonalisation;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShopper;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class InMemoryCartRepository implements CartRepository, InMemoryRepository
{
    public function findCart(OrderId $orderId): Cart
    {
        if (! isset(InMemoryOrderRepository::$orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id '.$orderId);
        }

        $order = InMemoryOrderRepository::$orders[$orderId->get()];

        if (! $order->inCustomerHands()) {
            throw new OrderAlreadyInMerchantHands('Cannot fetch cart. Order is no longer in customer hands and has already the following state: '.$order->getOrderState()->value);
        }

        // Since we rely on the vat order snapshot for prices, we need to provide a vat snapshot state to the cart read models.
        (new TestContainer)->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $orderState = $order->getMappedData();

        $lines = array_map(fn (Line $line) => DefaultCartLine::fromMappedData(
            array_merge($line->getMappedData(), [
                'unit_price_incl' => $line->getUnitPrice()->getIncludingVat()->getAmount(),
                'unit_price_excl' => $line->getUnitPrice()->getExcludingVat()->getAmount(),
                'total_excl' => $line->getTotal()->getExcludingVat()->getAmount(),
                'total_incl' => $line->getTotal()->getIncludingVat()->getAmount(),
                'total_vat' => $line->getTotal()->getVatTotal()->getAmount(),
                'discount_excl' => $line->getDiscountPriceExcl()->getAmount(),
                'discount_incl' => $line->getDiscountPriceIncl()->getAmount(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultCartDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'percentage' => $discount->getPercentage($line->getSubtotal()->getExcludingVat()),
            ]), $orderState), $line->getDiscounts()),
            array_map(fn (LinePersonalisation $linePersonalisation) => DefaultCartLinePersonalisation::fromMappedData(array_merge($linePersonalisation->getMappedData(), [
                //
            ]), $line->getMappedData()), $line->getPersonalisations())
        ), $order->getLines());

        $shippingAddress = $order->getShippingAddress() ? DefaultCartShippingAddress::fromMappedData(
            $order->getShippingAddress()->getMappedData(),
            $orderState
        ) : null;

        $billingAddress = $order->getBillingAddress() ? DefaultCartBillingAddress::fromMappedData(
            $order->getBillingAddress()->getMappedData(),
            $orderState
        ) : null;

        $shippings = array_map(fn ($shipping) => DefaultCartShipping::fromMappedData(
            array_merge($shipping->getMappedData(), [
                'shipping_state' => $shipping->getShippingState(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultCartDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'percentage' => $discount->getPercentage($shipping->getShippingCost()->getExcludingVat()),
            ]), $orderState), $shipping->getDiscounts())// TODO: cart shipping discounts
        ), $order->getShippings());

        $payments = array_map(fn ($payment) => DefaultCartPayment::fromMappedData(
            array_merge($payment->getMappedData(), [
                'payment_state' => $payment->getPaymentState(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultCartDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'percentage' => $discount->getPercentage($payment->getPaymentCost()->getExcludingVat()),
            ]), $orderState), $payment->getDiscounts())// TODO: cart payment discounts
        ), $order->getPayments());

        $shopper = $order->getShopper() ? DefaultCartShopper::fromMappedData(
            $order->getShopper()->getMappedData(),
            $orderState,
        ) : null;

        return DefaultCart::fromMappedData(
            $orderState,
            [
                CartLine::class => $lines,
                CartShippingAddress::class => $shippingAddress,
                CartBillingAddress::class => $billingAddress,
                CartShipping::class => count($shippings) ? reset($shippings) : null, // In the cart we expect one shipping
                CartPayment::class => count($payments) ? reset($payments) : null, // In the cart we expect one payment
                CartShopper::class => $shopper,
            ],
            array_map(fn (Discount $discount) => DefaultCartDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'percentage' => $discount->getPercentage($order->getSubtotalExcl()),
            ]), $orderState), $order->getDiscounts()), // TODO: cart discounts
        );
    }

    public function existsCart(OrderId $orderId): bool
    {
        foreach (InMemoryOrderRepository::$orders as $order) {
            if ($order->orderId->equals($orderId) && $order->inCustomerHands()) {
                return true;
            }
        }

        return false;
    }
}
