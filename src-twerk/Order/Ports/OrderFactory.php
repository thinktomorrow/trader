<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Thinktomorrow\Trader\Order\Domain\OrderState;
use Thinktomorrow\Trader\Order\Domain\PaymentState;
use Thinktomorrow\Trader\Order\Domain\ShippingState;
use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Common\Address\Address;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Common\Domain\ChannelId;
use Thinktomorrow\Trader\Common\Domain\Locale;
use Thinktomorrow\Trader\Common\Notes\NoteCollection;
use Thinktomorrow\Trader\Common\State\StateException;
use Thinktomorrow\Trader\Order\Domain\OrderStateMachine;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderCustomer;
use Thinktomorrow\Trader\Order\Domain\OrderPayment;
use Thinktomorrow\Trader\Order\Domain\OrderProductCollection;
use Thinktomorrow\Trader\Order\Domain\OrderProductRepository;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Order\Domain\OrderShipping;
use Thinktomorrow\Trader\Taxes\TaxRate;

class OrderFactory
{
    private Container $container;
    private OrderProductRepository $orderProductRepository;

    public function __construct(Container $container, OrderProductRepository $orderProductRepository)
    {
        $this->container = $container;
        $this->orderProductRepository = $orderProductRepository;
    }

    public function create(OrderReference $orderReference, OrderModel $orderModel, array $data): Order
    {
        $orderProducts = ($orderModel->exists && $orderModel->products)
            ? $this->orderProductRepository->getByOrder($orderReference)
            : $this->container->make(OrderProductCollection::class);

        $order = $this->container->make(Order::class, [
            'reference' => $orderReference,
            'orderState' => $orderModel->getState(OrderState::$KEY),
            'items' => $orderProducts,
            'orderShipping' => $orderModel->shipping ? $this->createOrderShipping($orderModel->shipping) : OrderShipping::empty(),
            'orderPayment' => $orderModel->payment ? $this->createOrderPayment($orderModel->payment) : OrderPayment::empty(),
            'orderCustomer' => $orderModel->customer ? $this->createOrderCustomer($orderModel->customer) : OrderCustomer::empty(),
            'discounts' => new AppliedDiscountCollection(),
            'notes' => $this->container->make(NoteCollection::class),
            'data' => [
                // TODO: detect and get current channel
                'channel' => ChannelId::fromString('lu'),

                // TODO: detect and get current locale
                'locale' => Locale::default(),
            ],
        ]);

        return $order;
    }

    private function createOrderShipping(OrderShippingModel $model): OrderShipping
    {
        return new OrderShipping(
            (string) $model->id,
            $model->method,
            ShippingState::fromString($model->shipping_state),
            Cash::make($model->total, $model->currency),
            TaxRate::fromInteger($model->tax_rate),
            new AppliedDiscountCollection(),
            Address::empty(),
            []
            // Discounts,
            // Address
            // Data
        );
    }

    private function createOrderPayment(OrderPaymentModel $model): OrderPayment
    {
        return new OrderPayment(
            (string) $model->id,
            $model->method,
            PaymentState::fromString($model->payment_state),
            Cash::make($model->total, $model->currency),
            TaxRate::fromInteger($model->tax_rate),
            new AppliedDiscountCollection(),
            []
        // Discounts,
        // Data
        );
    }

    private function createOrderCustomer(OrderCustomerModel $model): OrderCustomer
    {
        return new OrderCustomer(
            (string) $model->id,
            $model->customer_id ? (string) $model->customer_id : null,
            $model->email,
            $model->billing_address ? Address::fromArray($model->billing_address) : Address::empty(),
            $model->shipping_address ? Address::fromArray($model->shipping_address) : Address::empty(),
            []
        // Discounts,
        // Address
        // Data
        );
    }
}
