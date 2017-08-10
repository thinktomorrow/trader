<?php

namespace Thinktomorrow\Trader\Tests\Unit\Stubs;

use Money\Money;
use Thinktomorrow\Trader\Order\Domain\ItemId;
use Thinktomorrow\Trader\Order\Domain\Purchasable;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Tax\Domain\TaxId;

class ConcretePurchasable implements Purchasable
{
    protected $id;
    protected $data;
    protected $price;
    protected $salePrice;
    protected $taxRate;
    protected $taxId;

    public function __construct($id = null, $data = [], Money $price = null, Percentage $taxRate = null, Money $salePrice = null)
    {
        $this->id = $id ?: rand(1,99);
        $this->data = $data;
        $this->price = $price ?: Money::EUR(120);
        $this->taxRate = !is_null($taxRate) ? $taxRate : Percentage::fromPercent(21);

        $this->salePrice = $salePrice ?: null;
    }

    public function itemId(): ItemId
    {
        return ItemId::fromInteger($this->id);
    }

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
        // TODO: should this be set here on purchasable or only on Item?
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
     * Convenience method for testing
     *
     * @param $taxId
     */
    public function setTaxId($taxId)
    {
        $this->taxId = TaxId::fromInteger($taxId);
    }
}