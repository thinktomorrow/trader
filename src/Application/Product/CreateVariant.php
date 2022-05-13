<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;

class CreateVariant
{
    private string $productId;
    private string $unitPrice;
    private array $data;

    public function __construct(string $productId, string $unitPrice, array $data)
    {
        $this->productId = $productId;
        $this->unitPrice = $unitPrice;
        $this->data = $data;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
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
