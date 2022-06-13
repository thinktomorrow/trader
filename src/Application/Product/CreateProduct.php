<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

class CreateProduct
{
    private array $taxonIds;
    private string $unitPrice;
    private string $taxRate;
    private array $data;

    public function __construct(array $taxonIds, string $unitPrice, string $taxRate, array $data)
    {
        $this->taxonIds = $taxonIds;
        $this->unitPrice = $unitPrice;
        $this->taxRate = $taxRate;
        $this->data = $data;
    }

    public function getTaxonIds(): array
    {
        return array_map(fn ($taxonId) => TaxonId::fromString($taxonId), $this->taxonIds);
    }

    public function getUnitPrice(bool $doesPriceInputIncludesVat, string $currency): VariantUnitPrice
    {
        return VariantUnitPrice::fromScalars($this->unitPrice, $currency, $this->taxRate, $doesPriceInputIncludesVat);
    }

    public function getSalePrice(bool $doesPriceInputIncludesVat, string $currency): VariantSalePrice
    {
        return VariantSalePrice::fromScalars($this->unitPrice, $currency, $this->taxRate, $doesPriceInputIncludesVat);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
