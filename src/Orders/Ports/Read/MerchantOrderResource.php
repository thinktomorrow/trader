<?php

namespace Thinktomorrow\Trader\Orders\Ports\Read;

use Thinktomorrow\Trader\Orders\Application\Reads\Merchant\MerchantOrderResource as MerchantOrderResourceContract;
use Thinktomorrow\Trader\Orders\Domain\Order;

class MerchantOrderResource implements MerchantOrderResourceContract
{
    /**
     * @var Order
     */
    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function merchantValues(): array
    {
        return [
            'total'          => $this->order->total(),
            'subtotal'       => $this->order->subtotal(),
            'discount_total' => $this->order->discountTotal(),
            'payment_total'  => $this->order->paymentTotal(),
            'shipment_total' => $this->order->shippingTotal(),
            'tax'            => $this->order->tax(),
            'tax_rates'      => $this->order->taxRates(),
            'reference'      => $this->order->id()->get(), // This should be something business unique; not the id.
            'confirmed_at'   => new \DateTime('@'.strtotime('-1day')), // TODO datestamps of states should be held elsewhere no?
            'state'          => $this->order->state(),
        ];
    }

    public function merchantItemValues(): array
    {
        $items = [];

        foreach ($this->order->items() as $item) {
            $items[] = [
                'name'          => $item->name(),
                'sku'           => '392939', // TODO
                'stock'         => 1, // TODO
                'stock_warning' => false, // TODO feature for later on
                'saleprice'     => $item->salePrice(),
                'quantity'      => $item->quantity(),
                'total'         => $item->total(),
                'taxRate'       => $item->taxRate(),
            ];
        }

        return $items;
    }

    public function merchantDiscountValues(): array
    {
        $appliedDiscounts = [];

        foreach ($this->order->discounts() as $discount) {
            $appliedDiscounts[] = [
                'id'          => $discount->id(),
                'type'        => $discount->type(),
                'amount'      => $discount->amount(),
                'description' => $discount->description(),
            ];
        }

        return $appliedDiscounts;
    }
}