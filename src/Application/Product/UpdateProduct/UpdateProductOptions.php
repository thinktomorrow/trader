<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

class UpdateProductOptions
{
    private string $productId;
    private array $options;

    public function __construct(string $productId, array $options)
    {
        $this->productId = $productId;
        $this->options = $options;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    /**
     * @return UpdateProductOptionItem[]
     */
    public function getOptions(): array
    {
        return array_map(function($option){
            $values = array_map(fn($optionValue) => new UpdateProductOptionValueItem($optionValue['id'], $optionValue['data']), $option['values']);
            return new UpdateProductOptionItem($option['id'], $values);
        }, $this->options);
    }
}
