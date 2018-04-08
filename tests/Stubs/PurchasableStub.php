<?php

namespace Thinktomorrow\Trader\Tests\Stubs;

use Money\Money;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Orders\Domain\Purchasable;
use Thinktomorrow\Trader\Orders\Domain\PurchasableId;
use Thinktomorrow\Trader\Sales\Domain\AppliedSale;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;
use Thinktomorrow\Trader\Tax\Domain\TaxId;

class PurchasableStub implements Purchasable, EligibleForSale
{
    protected $id;
    protected $data;
    protected $price;
    protected $salePrice;
    protected $saleTotal;
    protected $taxRate;
    protected $taxId;

    public function __construct($id = null, $data = [], Money $price = null, Percentage $taxRate = null, Money $salePrice = null)
    {
        $id = $id ?: rand(1, 99);
        $this->id = PurchasableId::fromString($id);

        $this->data = $data;
        $this->price = $price ?: Money::EUR(120);
        $this->taxRate = !is_null($taxRate) ? $taxRate : Percentage::fromPercent(21);

        $this->salePrice = $salePrice ?: null;
        $this->saleTotal = Money::EUR(0);
    }

    public function purchasableId(): PurchasableId
    {
        return $this->id;
    }

    public function purchasableType(): string
    {
        return get_class($this);
    }

//    public function itemId(): ItemId
//    {
//        return ItemId::fromInteger($this->id);
//    }

    public function itemData(): array
    {
        return $this->data;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function salePrice(): Money
    {
        // TODO: should this be set here on purchasable or only on MerchantItem?
        // If set here, the salePrice can be displayed on index as well, right?
        // Also it can be optimized for reads? Keep in mind that we should also need the applied Sales description as well
        // For specific text representations on productpages.
        return $this->salePrice ?: $this->price;
    }

    public function taxRate(): Percentage
    {
        return $this->taxRate;
    }

    public function tax(): Money
    {
        return $this->salePrice()->multiply($this->taxRate->asFloat());
    }

    /**
     * @return TaxId
     */
    public function taxId(): TaxId
    {
        return $this->taxId;
    }

    /**
     * Convenience method for testing.
     *
     * @param $taxId
     */
    public function setTaxId($taxId)
    {
        $this->taxId = TaxId::fromInteger($taxId);
    }

    public function hasOriginalSalePrice(): bool
    {
        return !$this->salePrice()->isNegative();
    }

    public function originalSalePrice(): ?Money
    {
        return $this->salePrice();
    }

    public function saleTotal(): Money
    {
        return $this->saleTotal;
    }

    public function addToSaleTotal(Money $addition)
    {
        $this->saleTotal = $this->saleTotal->add($addition);
    }

    public function sales(): array
    {
        return [];
    }

    public function addSale(AppliedSale $sale)
    {
        //
    }
}
