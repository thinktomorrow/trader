<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderDiscount;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderRepository;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;

class MysqlMerchantOrderRepository implements MerchantOrderRepository
{
    private ContainerInterface $container;
    private OrderRepository $orderRepository;

    private static $orderTable = 'trader_orders';

    public function __construct(ContainerInterface $container, OrderRepository $orderRepository)
    {
        $this->container = $container;
        $this->orderRepository = $orderRepository;
    }

    public function findMerchantOrder(OrderId $orderId): MerchantOrder
    {
        $order = $this->orderRepository->find($orderId);

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
        $lines = array_map(fn ($line) => $this->container->get(MerchantOrderLine::class)::fromMappedData(
            array_merge($line->getMappedData(), [
                'total' => $line->getTotal(),
                'taxTotal' => $line->getTaxTotal(),
                'discountTotal' => $line->getDiscountTotal(),
                'linePrice' => $line->getLinePrice(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => $this->container->get(MerchantOrderDiscount::class)::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($line->getSubTotal()),
            ]), $orderState), $line->getDiscounts()) // TODO: cartline discounts...
        ), $order->getLines());

        $shippingAddress = $order->getShippingAddress() ? $this->container->get(MerchantOrderShippingAddress::class)::fromMappedData(
            $order->getShippingAddress()->getMappedData(),
            $orderState
        ) : null;

        $billingAddress = $order->getBillingAddress() ? $this->container->get(MerchantOrderBillingAddress::class)::fromMappedData(
            $order->getBillingAddress()->getMappedData(),
            $orderState
        ) : null;

        $shippings = array_map(fn (Shipping $shipping) => $this->container->get(MerchantOrderShipping::class)::fromMappedData(
            array_merge($shipping->getMappedData(), [
                'cost' => $shipping->getShippingCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => $this->container->get(MerchantOrderDiscount::class)::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($shipping->getShippingCost()),
            ]), $orderState), $shipping->getDiscounts())
        ), $order->getShippings());

        $payments = array_map(fn (Payment $payment) => $this->container->get(MerchantOrderPayment::class)::fromMappedData(
            array_merge($payment->getMappedData(), [
                'cost' => $payment->getPaymentCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => $this->container->get(MerchantOrderDiscount::class)::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($payment->getPaymentCost()),
            ]), $orderState), $payment->getDiscounts())
        ), $order->getPayments());

        $shopper = $order->getShopper() ? $this->container->get(MerchantOrderShopper::class)::fromMappedData(
            $order->getShopper()->getMappedData(),
            $orderState,
        ) : null;

        return $this->container->get(MerchantOrder::class)::fromMappedData(
            $orderState,
            [
                MerchantOrderLine::class => $lines,
                MerchantOrderShippingAddress::class => $shippingAddress,
                MerchantOrderBillingAddress::class => $billingAddress,
                MerchantOrderShipping::class => $shippings,
                MerchantOrderPayment::class => $payments,
                MerchantOrderShopper::class => $shopper,
            ],
            array_map(fn (Discount $discount) => $this->container->get(MerchantOrderDiscount::class)::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($order->getSubTotal()),
            ]), $orderState), $order->getDiscounts()),
        );
    }
}
