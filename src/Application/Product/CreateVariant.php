<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

class CreateVariant
{
    private string $productId;
    private string $unitPrice;
    private string $taxRate;
    private array $data;

    public function __construct(string $productId, string $unitPrice, string $taxRate, array $data)
    {
        $this->productId = $productId;
        $this->unitPrice = $unitPrice;
        $this->taxRate = $taxRate;
        $this->data = $data;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    public function getUnitPrice(bool $doesPriceInputIncludesVat): VariantUnitPrice
    {
        return VariantUnitPrice::fromScalars($this->unitPrice, $this->taxRate, $doesPriceInputIncludesVat);
    }

    public function getSalePrice(bool $doesPriceInputIncludesVat): VariantSalePrice
    {
        return VariantSalePrice::fromScalars($this->unitPrice, $this->taxRate, $doesPriceInputIncludesVat);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
