<?php

namespace Thinktomorrow\Trader\Order\Ports\Web\Shop;

use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Common\Domain\Price\MoneyRender;

/**
 * Class CartItem
 * Read-only Data object for item information in cart.
 * Safe to use in your views and documents.
 * This object has no behaviour and should already be localised.
 *
 * @package Thinktomorrow\Trader\Order
 */
class CartItem
{
    /**
     * @var Item
     */
    private $item;

    public function __construct(Item $item)
    {
        $this->item = $item;
    }

    public function id()
    {
        return $this->item->id();
    }

    public function quantity(): int
    {
        return $this->item->quantity();
    }

    public function name()
    {
        return $this->item->name();
    }

    public function description()
    {
        return $this->item->description();
    }

    public function price(): string
    {
        return (new MoneyRender())->locale($this->item->price());
    }

    public function subtotal(): string
    {
        return (new MoneyRender())->locale($this->item->subtotal());
    }

    public function total(): string
    {
        return (new MoneyRender())->locale($this->item->total());
    }

    public function discounts(): AppliedDiscountCollection
    {
        return $this->item->discounts();
    }

}