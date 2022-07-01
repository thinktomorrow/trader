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
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShopper;

final class InMemoryCartRepository implements CartRepository
{
    public function findCart(OrderId $orderId): Cart
    {
        if (! isset(InMemoryOrderRepository::$orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        $order = InMemoryOrderRepository::$orders[$orderId->get()];

        if(!$order->inCustomerHands()) {
            throw new \DomainException('Cannot fetch cart. Order is no longer in customer hands and has already the following state: ' . $order->getOrderState()->value);
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
            ]), $orderState), $line->getDiscounts()) // TODO: cartline discounts...
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
                'cost' => $shipping->getShippingCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultCartDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($shipping->getShippingCost()),
            ]), $orderState), $shipping->getDiscounts())// TODO: cart shipping discounts
        ), $order->getShippings());

        $payment = $order->getPayment() ? DefaultCartPayment::fromMappedData(
            array_merge($order->getPayment()->getMappedData(), [
                'cost' => $order->getPayment()->getPaymentCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultCartDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($order->getPayment()->getPaymentCost()),
            ]), $orderState), $order->getPayment()->getDiscounts()), // TODO: cart payment discounts
        ) : null;

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
                CartPayment::class => $payment,
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
            if ($order->orderId->equals($orderId)) {
                return true;
            }
        }

        return false;
    }
}
