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
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\OrderAlreadyInMerchantHands;
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

final class InMemoryCartRepository implements CartRepository, InMemoryRepository
{
    public function findCart(OrderId $orderId): Cart
    {
        if (! isset(InMemoryOrderRepository::$orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        $order = InMemoryOrderRepository::$orders[$orderId->get()];

        if (! $order->inCustomerHands()) {
            throw new OrderAlreadyInMerchantHands('Cannot fetch cart. Order is no longer in customer hands and has already the following state: ' . $order->getOrderState()->value);
        }

        $orderState = array_merge(InMemoryOrderRepository::$orders[$orderId->get()]->getMappedData(), [
            'total' => $order->getTotal(),
            'taxTotal' => $order->getTaxTotal(),
            'subtotal' => $order->getSubTotal(),
            'discountTotal' => $order->getDiscountTotal(),
            'shippingCost' => $order->getShippingCost(),
            'paymentCost' => $order->getPaymentCost(),
        ]);

        $lines = array_map(fn ($line) => DefaultCartLine::fromMappedData(
            array_merge($line->getMappedData(), [
                'total' => $line->getTotal(),
                'taxTotal' => $line->getTaxTotal(),
                'discountTotal' => $line->getDiscountTotal(),
                'linePrice' => $line->getLinePrice(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultCartDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($line->getSubTotal()),
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
                'cost' => $shipping->getShippingCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultCartDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($shipping->getShippingCost()),
            ]), $orderState), $shipping->getDiscounts())// TODO: cart shipping discounts
        ), $order->getShippings());

        $payments = array_map(fn ($payment) => DefaultCartPayment::fromMappedData(
            array_merge($payment->getMappedData(), [
                'payment_state' => $payment->getPaymentState(),
                'cost' => $payment->getPaymentCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultCartDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($payment->getPaymentCost()),
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
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($order->getSubTotal()),
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
