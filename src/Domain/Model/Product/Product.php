<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
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
    private array $data = [];

    private function __construct(){}

    public static function create(ProductId $productId, ProductGroupId $productGroupId, ProductUnitPrice $productUnitPrice): static
    {
        $product = new static();
        $product->productId = $productId;
        $product->productGroupId = $productGroupId;
        $product->productUnitPrice = $productUnitPrice;

        $product->recordEvent(new ProductCreated($product->productId));

        return $product;
    }

    public function update(ProductGroupId $productGroupId, ProductUnitPrice $productUnitPrice, array $data): void
    {
        $this->productGroupId = $productGroupId;
        $this->productUnitPrice = $productUnitPrice;
        $this->data = array_merge($this->data, $data);

        $this->recordEvent(new ProductUpdated($this->productId));
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'product_group_id' => $this->productGroupId->get(),

            'product_unit_price' => $this->productUnitPrice->includesTax()
                ? $this->productUnitPrice->getIncludingVat()->getAmount()
                : $this->productUnitPrice->getExcludingVat()->getAmount(),
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
        $product->productUnitPrice = $state['includes_vat']
            ? ProductUnitPrice::fromMoneyIncludingVat(Cash::make($state['product_unit_price']), TaxRate::fromString($state['tax_rate']))
            : ProductUnitPrice::fromMoneyExcludingVat(Cash::make($state['product_unit_price']), TaxRate::fromString($state['tax_rate']));

        $product->data = $state['data'];

        return $product;
    }
}
