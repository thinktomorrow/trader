<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;

class CreateProduct
{
    private array $taxonIds;
    private string $unitPrice;
    private array $data;

    public function __construct(array $taxonIds, string $unitPrice, array $data)
    {
        $this->taxonIds = $taxonIds;
        $this->unitPrice = $unitPrice;
        $this->data = $data;
    }

    public function getTaxonIds(): array
    {
        return array_map(fn($taxonId) => TaxonId::fromString($taxonId), $this->taxonIds);
    }

    public function getUnitPrice(string $taxRate): VariantUnitPrice
    {
        return VariantUnitPrice::fromScalars($this->unitPrice, 'EUR', $taxRate, true);
    }

    public function getSalePrice(string $taxRate): VariantSalePrice
    {
        return VariantSalePrice::fromScalars($this->unitPrice, 'EUR', $taxRate, true);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
