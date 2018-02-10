<?php

namespace Thinktomorrow\Trader\Sales\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;

class AppliedSale
{
    private $id;
    private $type;
    private $saleAmount;
    private $salePercentage;
    private $data;

    public function __construct(SaleId $id, string $type, Money $saleAmount, Percentage $salePercentage, array $data = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->saleAmount = $saleAmount;
        $this->salePercentage = $salePercentage;
        $this->data = $data;
    }

    public function saleId(): SaleId
    {
        return $this->id;
    }

    public function saleType(): string
    {
        return $this->type;
    }

    public function saleAmount(): Money
    {
        return $this->saleAmount;
    }

    public function salePercentage(): Percentage
    {
        return $this->salePercentage;
    }

    public function data($key = null)
    {
        if (!is_null($key)) {
            return $this->data[$key];
        }

        return $this->data;
    }
}
