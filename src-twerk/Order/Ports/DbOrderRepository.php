<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Cart\Ports\CartModel;
use Thinktomorrow\Trader\Common\Domain\Locale;
use Thinktomorrow\Trader\Order\Domain\Exceptions\OrderModelNotFound;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderCustomer;
use Thinktomorrow\Trader\Order\Domain\OrderPayment;
use Thinktomorrow\Trader\Order\Domain\OrderProduct;
use Thinktomorrow\Trader\Order\Domain\OrderProductRepository;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;
use Thinktomorrow\Trader\Order\Domain\OrderShipping;

class DbOrderRepository implements OrderRepository
{
    private OrderFactory $orderFactory;
    private OrderProductRepository $orderProductRepository;

    public function __construct(OrderFactory $orderFactory, OrderProductRepository $orderProductRepository)
    {
        $this->orderFactory = $orderFactory;
        $this->orderProductRepository = $orderProductRepository;
    }

    public function existsByReference(OrderReference $orderReference): bool
    {
        return ! ! OrderModel::findByReference($orderReference);
    }

    public function findByReference(OrderReference $orderReference): Order
    {
        if (! $model = OrderModel::findByReference($orderReference)) {
            throw new OrderModelNotFound('No order model found in db storage by reference ' . $orderReference->get());
        }

        return $this->orderFactory->create($orderReference, $model, [
            // locale, channel and such metadata...
        ]);
    }

    public function emptyOrder(OrderReference $orderReference): Order
    {
        return $this->orderFactory->create($orderReference, new OrderModel(), []);
    }

    public function save(Order $order): void
    {
        if (! $orderModel = OrderModel::findByReference($order->getReference())) {
            $orderModel = new OrderModel();
        }

        $orderModel->id = $order->getReference()->get();
        $orderModel->order_state = $order->getOrderState();
        $orderModel->total = $order->getTotal()->getAmount();
        $orderModel->subtotal = $order->getSubTotal()->getAmount();
        $orderModel->discount_total = $order->getDiscountTotal()->getAmount();
        $orderModel->payment_total = $order->getPayment()->getTotal()->getAmount();
        $orderModel->shipping_total = $order->getShipping()->getTotal()->getAmount();
        $orderModel->tax_total = $order->getTaxTotal()->getAmount();
        $orderModel->tax_rates = $order->getTaxTotalPerRate()->get(); // json
        $orderModel->currency = $order->getTotal()->getCurrency()->getCode();
        $orderModel->save();

        /** @var DefaultOrderProduct $orderProduct */
        foreach ($order->getItems() as $orderProduct) {
            $this->saveOrderProduct($order, $orderProduct, $orderModel);
            // Orderproduct already exists? -> update
            // else create
        }

        $this->saveOrderShipping($order, $orderModel);
        $this->saveOrderPayment($order, $orderModel);
        $this->saveOrderCustomer($order, $orderModel);

        // TODO: dynamic values... , notes, data

        // Separate models...
        // 'items' => $order->getItems(),
        //            'orderShipping' => $order->getReference(),
        //            'orderPayment' => $order->getReference(),
        //            'orderCustomer' => $order->getReference(),
        //            'discounts' => $order->getReference(),
    }

    public function filterByState(string ...$state): self
    {
        $this->model = $this->model->whereIn('state', $state);

        return $this;
    }

    public function lastUpdatedBefore(\DateTime $threshold): self
    {
        $this->model = $this->model->where('updated_at', '<', $threshold);

        return $this;
    }

    public function get(): Collection
    {
        $carts = $this->model->get()->map(function ($model) {
            return $this->composeCart(OrderReference::fromString($model->reference), $model);
        });

        $this->reset();

        return $carts;
    }

    private function reset()
    {
        $this->stale = false;
        $this->model = new CartModel();
    }

    public function nextReference(): OrderReference
    {
        return OrderReference::fromString((string)$this->generateOrderReference());
    }

    private function generateOrderReference(): string
    {
        $prefix = app('trader_config')->orderReferencePrefix();
        $orderReference = $prefix . Uuid::uuid4();

        while (OrderModel::findByReference(OrderReference::fromString($orderReference))) {
            $orderReference = $prefix . Uuid::uuid4();
        }

        return $orderReference;
    }

    public function delete(Order $order): void
    {
        // TODO: Implement delete() method.
    }


    private function saveOrderProduct(Order $order, OrderProduct $orderProduct, OrderModel $orderModel)
    {
        $values = [
            'product_id' => $orderProduct->getProductId(),
            'quantity' => $orderProduct->getQuantity(),
            'total' => $orderProduct->getTotal()->getAmount(),
            'discount_total' => $orderProduct->getDiscountTotal()->getAmount(),
            'unit_price' => $orderProduct->getUnitPrice()->getAmount(),
            'currency' => $orderProduct->getTotal()->getCurrency()->getCode(),
            'tax_rate' => $orderProduct->getTaxRate()->toPercentage()->toInteger(),
            'data' => [
                // TODO: like name and such
                // for easy reads in admin ...
            ],
        ];

        if ($existingOrderProduct = $orderModel->products->first(fn ($item) => $item->id == $orderProduct->getId())) {
            if ($orderProduct->exists() && $orderProduct->getId() != $existingOrderProduct->id) {
                throw new \DomainException('Given product id [' . $orderProduct->getId() . '] does not match product id [' . $existingOrderProduct->id . '] connected to this order!');
            }

            $existingOrderProduct->update($values);
        } else {
            OrderProductModel::create(array_merge($values, [
                'order_id' => $order->getReference()->get(),
            ]));
        }
    }

    private function saveOrderShipping(Order $order, OrderModel $orderModel): void
    {
        $shipping = $order->getShipping();

        $values = [
            'shipping_state' => $shipping->getShippingState()->get(),
            'method' => $shipping->getMethod() ?: 'unknown',
            'total' => $shipping->getTotal()->getAmount(),
            'subtotal' => $shipping->getSubTotal()->getAmount(),
            'discount_total' => $shipping->getDiscountTotal()->getAmount(),
            'tax_total' => $shipping->getTaxTotal()->getAmount(),
            'currency' => $shipping->getTotal()->getCurrency()->getCode(),
            'tax_rate' => $shipping->getTaxRate()->toPercentage()->toInteger(),
            'address' => $shipping->getAddress()->toArray(),
            'country' => $shipping->getAddress()->getCountry(),
            'data' => [],
        ];

        if ($orderModel->shipping) {
            if ($shipping->exists() && $shipping->getId() != $orderModel->shipping->id) {
                throw new \DomainException('Given shipping id [' . $shipping->getId() . '] does not match shipping id [' . $orderModel->shipping->id . '] connected to this order!');
            }

            $orderModel->shipping->update($values);
        } else {
            OrderShippingModel::create(array_merge($values, [
                'order_id' => $order->getReference()->get(),
            ]));
        }
    }

    private function saveOrderPayment(Order $order, OrderModel $orderModel): void
    {
        $payment = $order->getPayment();

        $values = [
            'payment_state' => $payment->getPaymentState()->get(),
            'method' => $payment->getMethod() ?: 'unknown',
            'total' => $payment->getTotal()->getAmount(),
            'subtotal' => $payment->getSubTotal()->getAmount(),
            'discount_total' => $payment->getDiscountTotal()->getAmount(),
            'tax_total' => $payment->getTaxTotal()->getAmount(),
            'currency' => $payment->getTotal()->getCurrency()->getCode(),
            'tax_rate' => $payment->getTaxRate()->toPercentage()->toInteger(),
            'data' => [],
        ];

        if ($orderModel->payment) {
            if ($payment->exists() && $payment->getId() != $orderModel->payment->id) {
                throw new \DomainException('Given payment id [' . $payment->getId() . '] does not match payment id [' . $orderModel->payment->id . '] connected to this order!');
            }

            $orderModel->payment->update($values);
        } else {
            OrderPaymentModel::create(array_merge($values, [
                'order_id' => $order->getReference()->get(),
            ]));
        }
    }

    private function saveOrderCustomer(Order $order, OrderModel $orderModel): void
    {
        $customer = $order->getCustomer();

        $values = [
            'customer_id' => $customer->getCustomerId(),
            'email' => $customer->getEmail(),
            'billing_address' => $customer->getBillingAddress()->toArray(),
            'shipping_address' => $customer->getShippingAddress()->toArray(),
            'data' => [],
        ];

        if ($orderModel->customer) {
            if ($customer->exists() && $customer->getId() != $orderModel->customer->id) {
                throw new \DomainException('Given customer id [' . $customer->getId() . '] does not match customer id [' . $orderModel->customer->id . '] connected to this order!');
            }

            $orderModel->customer->update($values);
        } else {
            OrderCustomerModel::create(array_merge($values, [
                'order_id' => $order->getReference()->get(),
            ]));
        }
    }
}
