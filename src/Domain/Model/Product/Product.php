<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\ProductGroup\ProductGroupId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductUpdated;

final class Product implements Aggregate
{
    use RecordsEvents;

    public readonly ProductId $productId;
    private ProductGroupId $productGroupId;
    private ProductUnitPrice $productUnitPrice;
    private ProductSalePrice $productSalePrice;
    private array $data = [];

    private function __construct(){}

    public function getSalePrice(): ProductSalePrice
    {
        return $this->productSalePrice;
    }

    public static function create(ProductId $productId, ProductGroupId $productGroupId, ProductUnitPrice $productUnitPrice, ProductSalePrice $productSalePrice): static
    {
        $product = new static();
        $product->productId = $productId;
        $product->productGroupId = $productGroupId;
        $product->productUnitPrice = $productUnitPrice;
        $product->productSalePrice = $productSalePrice;

        $product->recordEvent(new ProductCreated($product->productId));

        return $product;
    }

    public function update(ProductGroupId $productGroupId, ProductUnitPrice $productUnitPrice, ProductSalePrice $productSalePrice, array $data): void
    {
        $this->productGroupId = $productGroupId;
        $this->productUnitPrice = $productUnitPrice;
        $this->productSalePrice = $productSalePrice;
        $this->data = array_merge($this->data, $data);

        $this->recordEvent(new ProductUpdated($this->productId));
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'product_group_id' => $this->productGroupId->get(),

            'product_unit_price' => $this->productUnitPrice->getMoney()->getAmount(),
            'product_sale_price' => $this->productSalePrice->getMoney()->getAmount(),
            'tax_rate' => $this->productUnitPrice->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $this->productUnitPrice->includesTax(),

            'data' => $this->data,
        ];
    }

    public function getChildEntities(): array
    {
        return [];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $product = new static();

        $product->productId = ProductId::fromString($state['product_id']);
        $product->productGroupId = ProductGroupId::fromString($state['product_group_id']);
        $product->productUnitPrice = ProductUnitPrice::fromScalars($state['product_unit_price'], 'EUR', $state['tax_rate'], $state['includes_vat']);
        $product->productSalePrice = ProductSalePrice::fromScalars($state['product_sale_price'], 'EUR', $state['tax_rate'], $state['includes_vat']);

        $product->data = $state['data'];

        return $product;
    }
}
