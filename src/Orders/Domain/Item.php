<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Tax\Domain\TaxId;

final class Item
{
    /**
     * Unique identifier of line item.
     *
     * @var itemId
     */
    private $id;

    /**
     * Indicate this model is already persisted
     * @var bool
     */
    private $persisted = false;

    /**
     * Quantity of item selection.
     *
     * @var int
     */
    private $quantity = 1;

    /**
     * Original purchasable product.
     *
     * @var Purchasable
     */
    private $purchasable;

    private $discounts;

    private $discountTotal;

    /**
     * @var Percentage
     */
    private $taxRate;

    public function __construct(ItemId $id, Purchasable $purchasable)
    {
        $this->id = $id;
        $this->purchasable = $purchasable; // The original product
        $this->discounts = new AppliedDiscountCollection();
        $this->discountTotal = Cash::make(0);
        $this->setTaxRate($purchasable->taxRate());
    }

    public static function fromPurchasable(Purchasable $purchasable)
    {
        // Note: ItemId is a reference to its persistence record so here we just pass
        // a dummy value instead which will be overwritten as soon as item is stored
        // TODO: actually refactor so that Item does not have a itemID needed anymore??? but instead an orderID offcourse
        return new self(ItemId::fromString('-'), $purchasable);
    }

    public function id(): ItemId
    {
        return $this->id;
    }

    public function persisted(): bool
    {
        return $this->persisted;
    }

    public function setPersisted()
    {
        $this->persisted = true;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function price(): Money
    {
        return $this->purchasable->price();
    }

    public function salePrice(): Money
    {
        return $this->purchasable->salePrice();
    }

    public function subtotal(): Money
    {
        return $this->purchasable->salePrice()->multiply($this->quantity());
    }

    public function total(): Money
    {
        return $this->subtotal()
                    ->subtract($this->discountTotal());
    }

    public function taxId(): TaxId
    {
        return $this->purchasable->taxId();
    }

    public function taxRate(): Percentage
    {
        return $this->taxRate;
    }

    public function setTaxRate(Percentage $taxRate)
    {
        $this->taxRate = $taxRate;
    }

    public function tax(): Money
    {
        return $this->total()->multiply($this->taxRate()->asFloat());
    }

    public function name()
    {
        return $this->getFromPurchasable('name');
    }

    public function description()
    {
        return $this->getFromPurchasable('description');
    }

    public function discounts(): AppliedDiscountCollection
    {
        return $this->discounts;
    }

    /**
     * Add applied discounts.
     *
     * @param $discount
     */
    public function addDiscount(AppliedDiscount $discount)
    {
        $this->discounts->add($discount);
    }

    public function discountTotal(): Money
    {
        return $this->discountTotal;
    }

    public function addToDiscountTotal(Money $addition)
    {
        $this->discountTotal = $this->discountTotal->add($addition);
    }

    public function add($quantity = 1)
    {
        $this->quantity += $quantity;
        $this->validateQuantity();

        return $this;
    }

    public function remove($quantity = 1)
    {
        $this->quantity -= $quantity;
        $this->validateQuantity();

        return $this;
    }

    public function purchasable(): Purchasable
    {
        return $this->purchasable;
    }

    public function purchasableId(): PurchasableId
    {
        return $this->purchasable()->purchasableId();
    }

    private function validateQuantity()
    {
        if ($this->quantity < 0) {
            $this->quantity = 0;
        }
    }

    private function getFromPurchasable($key)
    {
        $itemData = $this->purchasable->itemData();

        if (empty($itemData) || !isset($itemData[$key])) {
            return;
        }

        return $itemData[$key];
    }
}