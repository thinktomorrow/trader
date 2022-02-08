<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Order\Domain\OrderProduct;
use Thinktomorrow\Trader\Order\Domain\OrderProductCollection;
use Thinktomorrow\Trader\Order\Domain\OrderProductRepository;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Taxes\TaxRate;

class DbOrderProductRepository implements OrderProductRepository
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function existsByReference(OrderReference $cartReference): bool
    {
        return (0 < $this->model->where('reference', $cartReference->get())->count());
    }

    public function getByOrder(OrderReference $orderReference): OrderProductCollection
    {
        $orderProducts = OrderProductModel::where('order_id', $orderReference->get())->get();

        return $this->container->make(OrderProductCollection::class, [
            'items' => $orderProducts->map(fn ($orderProduct) => $this->composeOrderProduct($orderProduct))->all(),
        ]);
    }

    private function composeOrderProduct(OrderProductModel $orderProductModel): OrderProduct
    {
        return $this->container->make(OrderProduct::class, [
            'id' => $orderProductModel->id,
            'productId' => $orderProductModel->product_id,
            'orderReference' => OrderReference::fromString($orderProductModel->order_id),
            'quantity' => $orderProductModel->quantity,
            'unitPrice' => Cash::make($orderProductModel->unit_price, $orderProductModel->currency),
            'taxRate' => TaxRate::fromInteger($orderProductModel->tax_rate),
            'isTaxApplicable' => true, // TODO: need to be set from orderCustomer context since it is stored there.
            'data' => [],
        ]);
    }

    public function save(OrderProduct $orderProduct): void
    {
//        $data = json_encode($cart->toArray());
//
//        $model = OrderModel::findByReference($cart->reference());
//
//        if ($model) {
//            $model->data = $data;
//            $model->save();
//
//            return;
//        }
//
//        OrderModel::create([
//            'reference' => $cart->reference()->get(),
//            'data'      => $data,
//        ]);
    }
}
