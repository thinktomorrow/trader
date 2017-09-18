<?php

namespace Thinktomorrow\Trader\Orders\Ports\Read;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Ports\Web\GetDynamicValue;
use Thinktomorrow\Trader\Orders\Domain\Read\CartItem\CartItem as CartItemContract;
use Thinktomorrow\Trader\Orders\Domain\Item;

/**
 * Class CartItem
 * Read-only Data object for item information in cart.
 * Safe to use in your views and documents.
 * This object has no behaviour and should already be localised.
 */
class CartItem implements CartItemContract
{
    use GetDynamicValue;

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

    public function purchasableId()
    {
        return $this->item->purchasableId();
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
        return Cash::from($this->item->price())->locale();
    }

    public function saleprice(): string
    {
        return Cash::from($this->item->salePrice())->locale();
    }

    public function subtotal(): string
    {
        return Cash::from($this->item->subtotal())->locale();
    }

    public function total(): string
    {
        return Cash::from($this->item->total())->locale();
    }

    public function discounts(): array
    {
        return $this->item->discounts();
    }

    public function taxRate(): string
    {
        // TODO: Implement taxRate() method.
    }
}
