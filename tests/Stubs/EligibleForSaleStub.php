<?php

namespace Thinktomorrow\Trader\Tests\Stubs;

use Money\Money;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Sales\Domain\AppliedSale;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;

class EligibleForSaleStub implements EligibleForSale
{
    protected $id;

    public $data;
    public $price;
    public $original_saleprice;
    public $saleTotal;
    public $taxRate;
    public $taxId;
    public $appliedSales = [];

    public function __construct($id = null, $data = [], Money $price = null, Percentage $taxRate = null)
    {
        $this->id = $id ?: rand(1, 99);
        $this->data = $data;
        $this->price = $price ?: Money::EUR(120);
        $this->original_saleprice = Money::EUR(0);
        $this->taxRate = !is_null($taxRate) ? $taxRate : Percentage::fromPercent(21);

        $this->saleTotal = Money::EUR(0);
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function hasOriginalSalePrice(): bool
    {
        return $this->original_saleprice && !$this->original_saleprice->isNegative();
    }

    public function originalSalePrice(): ?Money
    {
        return $this->original_saleprice;
    }

    public function salePrice(): Money
    {
        return $this->price()->subtract($this->saleTotal());
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
        return $this->appliedSales;
    }

    public function addSale(AppliedSale $sale)
    {
        $this->appliedSales[] = $sale;
    }
}
