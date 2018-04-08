<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Price\Cash;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Tax\Domain\TaxId;
use Thinktomorrow\Trader\Tax\Domain\TaxRate;

final class Item implements EligibleForDiscount
{
    /**
     * Unique identifier of line item.
     *
     * @var itemId
     */
    private $id;

    /**
     * Indicate this model is already persisted.
     *
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

    private $discounts = [];

    private $discountTotal;

    /**
     * @var Percentage
     */
    private $taxRate;

    public function __construct(ItemId $id, Percentage $taxRate, Purchasable $purchasable)
    {
        $this->id = $id;
        $this->taxRate = $taxRate;
        $this->purchasable = $purchasable; // The original product
        $this->discountTotal = Cash::make(0);
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

    public function onSale(): bool
    {
        return $this->salePrice()->greaterThan($this->price());
    }

    public function price(): Money
    {
        return $this->purchasable->price();
    }

    public function salePrice(): Money
    {
        return $this->purchasable->salePrice()->isPositive() ? $this->purchasable->salePrice() : $this->price();
    }

    public function subtotal(): Money
    {
        return $this->salePrice()->multiply($this->quantity());
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

    public function taxTotal(): Money
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

    public function discountBasePrice(): Money
    {
        return $this->salePrice();
    }

    public function discounts(): array
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
        $this->discounts[] = $discount;
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

    public function purchasableId(): PurchasableId
    {
        return $this->purchasable()->purchasableId();
    }

    public function purchasableType(): string
    {
        return $this->purchasable()->purchasableType();
    }

    public function purchasable(): Purchasable
    {
        return $this->purchasable;
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
