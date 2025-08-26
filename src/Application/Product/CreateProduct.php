<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

class CreateProduct
{
    private array $taxonIds;
    private string $unitPrice;
    private string $taxRate;
    private string $sku;
    private array $data;
    private array $variantData;

    public function __construct(array $taxonIds, string $unitPrice, string $taxRate, string $sku, array $data, array $variantData)
    {
        $this->taxonIds = $taxonIds;
        $this->unitPrice = $unitPrice;
        $this->taxRate = $taxRate;
        $this->sku = $sku;
        $this->data = $data;
        $this->variantData = $variantData;
    }

    public function getProductTaxa(ProductId $productId): array
    {
        return array_map(function ($taxonId) use ($productId) {
            return ProductTaxon::create($productId, $taxonId);
        }, $this->getTaxonIds());
    }

    private function getTaxonIds(): array
    {
        return array_map(fn ($taxonId) => TaxonId::fromString($taxonId), $this->taxonIds);
    }

    public function getUnitPrice(bool $doesPriceInputIncludesVat): VariantUnitPrice
    {
        return VariantUnitPrice::fromScalars($this->unitPrice, $this->taxRate, $doesPriceInputIncludesVat);
    }

    public function getSalePrice(bool $doesPriceInputIncludesVat): VariantSalePrice
    {
        return VariantSalePrice::fromScalars($this->unitPrice, $this->taxRate, $doesPriceInputIncludesVat);
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getVariantData(): array
    {
        return $this->variantData;
    }
}
