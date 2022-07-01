<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartRepository;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;

final class MysqlCartRepository implements CartRepository
{
    private ContainerInterface $container;
    private OrderRepository $orderRepository;

    private static $orderTable = 'trader_orders';

    public function __construct(ContainerInterface $container, OrderRepository $orderRepository)
    {
        $this->container = $container;
        $this->orderRepository = $orderRepository;
    }

    public function findCart(OrderId $orderId): Cart
    {
        $order = $this->orderRepository->find($orderId);

        if (! $order->inCustomerHands()) {
            throw new \DomainException('Cannot fetch cart. Order is no longer in customer hands and has already the following state: ' . $order->getOrderState()->value);
        }

        $orderState = array_merge($order->getMappedData(), [
            'total' => $order->getTotal(),
            'taxTotal' => $order->getTaxTotal(),
            'subtotal' => $order->getSubTotal(),
            'discountTotal' => $order->getDiscountTotal(),
            'shippingCost' => $order->getShippingCost(),
            'paymentCost' => $order->getPaymentCost(),
        ]);

        // TODO: how to refresh data based on the latest variant price or actual discounts, ...? not on read but better on a dedicated time in the cart...
        // Need to make note of any change in that case.
        $lines = array_map(fn ($line) => $this->container->get(CartLine::class)::fromMappedData(
            array_merge($line->getMappedData(), [
                'total' => $line->getTotal(),
                'taxTotal' => $line->getTaxTotal(),
                'discountTotal' => $line->getDiscountTotal(),
                'linePrice' => $line->getLinePrice(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => $this->container->get(CartDiscount::class)::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($line->getSubTotal()),
            ]), $orderState), $line->getDiscounts()) // TODO: cartline discounts...
        ), $order->getLines());

        $shippingAddress = $order->getShippingAddress() ? $this->container->get(CartShippingAddress::class)::fromMappedData(
            $order->getShippingAddress()->getMappedData(),
            $orderState
        ) : null;

        $billingAddress = $order->getBillingAddress() ? $this->container->get(CartBillingAddress::class)::fromMappedData(
            $order->getBillingAddress()->getMappedData(),
            $orderState
        ) : null;

        $shippings = array_map(fn (Shipping $shipping) => $this->container->get(CartShipping::class)::fromMappedData(
            array_merge($shipping->getMappedData(), [
                'cost' => $shipping->getShippingCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => $this->container->get(CartDiscount::class)::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($shipping->getShippingCost()),
            ]), $orderState), $shipping->getDiscounts())// TODO: cart shipping discounts
        ), $order->getShippings());

        $payment = $order->getPayment() ? $this->container->get(CartPayment::class)::fromMappedData(
            array_merge($order->getPayment()->getMappedData(), [
                'cost' => $order->getPayment()->getPaymentCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => $this->container->get(CartDiscount::class)::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($order->getPayment()->getPaymentCost()),
            ]), $orderState), $order->getPayment()->getDiscounts()), // TODO: cart payment discounts
        ) : null;

        $shopper = $order->getShopper() ? $this->container->get(CartShopper::class)::fromMappedData(
            $order->getShopper()->getMappedData(),
            $orderState,
        ) : null;

        return $this->container->get(Cart::class)::fromMappedData(
            $orderState,
            [
                CartLine::class => $lines,
                CartShippingAddress::class => $shippingAddress,
                CartBillingAddress::class => $billingAddress,
                CartShipping::class => count($shippings) ? reset($shippings) : null, // In the cart we expect one shipping
                CartPayment::class => $payment,
                CartShopper::class => $shopper,
            ],
            array_map(fn (Discount $discount) => $this->container->get(CartDiscount::class)::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($order->getSubTotal()),
            ]), $orderState), $order->getDiscounts()),
        );
    }

    public function existsCart(OrderId $orderId): bool
    {
        return DB::table(static::$orderTable)
            ->where('order_id', $orderId->get())
            ->whereIn('state', array_map(fn (OrderState $state) => $state->value, OrderState::customerStates()))
            ->exists();
    }
}
