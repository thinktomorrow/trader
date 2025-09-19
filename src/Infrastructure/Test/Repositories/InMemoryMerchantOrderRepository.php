<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderEvent;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderRepository;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEvent;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrder;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderEvent;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLinePersonalisation;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShopper;

class InMemoryMerchantOrderRepository implements MerchantOrderRepository, InMemoryRepository
{
    public function findMerchantOrder(OrderId $orderId): MerchantOrder
    {
        if (! isset(InMemoryOrderRepository::$orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        $order = InMemoryOrderRepository::$orders[$orderId->get()];

        $orderState = array_merge(InMemoryOrderRepository::$orders[$orderId->get()]->getMappedData(), [
            'order_state' => $order->getOrderState(),
            'total' => $order->getTotal(),
            'taxTotal' => $order->getTaxTotal(),
            'subtotal' => $order->getSubTotal(),
            'discountTotal' => $order->getDiscountTotal(),
            'shippingCost' => $order->getShippingCost(),
            'paymentCost' => $order->getPaymentCost(),
        ]);

        $lines = array_map(fn ($line) => DefaultMerchantOrderLine::fromMappedData(
            array_merge($line->getMappedData(), [
                'total' => $line->getTotal(),
                'taxTotal' => $line->getTaxTotal(),
                'discountTotal' => $line->getDiscountTotal(),
                'linePrice' => $line->getLinePrice(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultMerchantOrderDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($line->getSubTotal()),
            ]), $orderState), $line->getDiscounts()),
            array_map(fn (LinePersonalisation $linePersonalisation) => DefaultMerchantOrderLinePersonalisation::fromMappedData(array_merge($linePersonalisation->getMappedData(), [
                //
            ]), $line->getMappedData()), $line->getPersonalisations())
        ), $order->getLines());

        $shippingAddress = $order->getShippingAddress() ? DefaultMerchantOrderShippingAddress::fromMappedData(
            $order->getShippingAddress()->getMappedData(),
            $orderState
        ) : null;

        $billingAddress = $order->getBillingAddress() ? DefaultMerchantOrderBillingAddress::fromMappedData(
            $order->getBillingAddress()->getMappedData(),
            $orderState
        ) : null;

        $shippings = array_map(fn ($shipping) => DefaultMerchantOrderShipping::fromMappedData(
            array_merge($shipping->getMappedData(), [
                'shipping_state' => $shipping->getShippingState(),
                'cost' => $shipping->getShippingCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultMerchantOrderDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($shipping->getShippingCost()),
            ]), $orderState), $shipping->getDiscounts())// TODO: cart shipping discounts
        ), $order->getShippings());

        $payments = array_map(fn ($payment) => DefaultMerchantOrderPayment::fromMappedData(
            array_merge($payment->getMappedData(), [
                'payment_state' => $payment->getPaymentState(),
                'cost' => $payment->getPaymentCost(),
            ]),
            $orderState,
            array_map(fn (Discount $discount) => DefaultMerchantOrderDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($payment->getPaymentCost()),
            ]), $orderState), $payment->getDiscounts())// TODO: cart payment discounts
        ), $order->getPayments());

        $shopper = $order->getShopper() ? DefaultMerchantOrderShopper::fromMappedData(
            $order->getShopper()->getMappedData(),
            $orderState,
        ) : null;

        $logEntries = array_map(fn (OrderEvent $logEntry) => DefaultMerchantOrderEvent::fromMappedData(
            $logEntry->getMappedData(),
            $orderState,
        ), $order->getOrderEvents());

        return DefaultMerchantOrder::fromMappedData(
            $orderState,
            [
                MerchantOrderLine::class => $lines,
                MerchantOrderShippingAddress::class => $shippingAddress,
                MerchantOrderBillingAddress::class => $billingAddress,
                MerchantOrderShipping::class => $shippings,
                MerchantOrderPayment::class => $payments,
                MerchantOrderShopper::class => $shopper,
                MerchantOrderEvent::class => $logEntries,
            ],
            array_map(fn (Discount $discount) => DefaultMerchantOrderDiscount::fromMappedData(array_merge($discount->getMappedData(), [
                'total' => $discount->getTotal(),
                'percentage' => $discount->getPercentage($order->getSubTotal()),
            ]), $orderState), $order->getDiscounts()), // TODO: cart discounts
        );
    }

    public function findMerchantOrderByReference(OrderReference $orderReference): MerchantOrder
    {
        foreach (InMemoryOrderRepository::$orders as $order) {
            if ($order->orderReference->equals($orderReference)) {
                return $this->findMerchantOrder($order->orderId);
            }
        }
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
