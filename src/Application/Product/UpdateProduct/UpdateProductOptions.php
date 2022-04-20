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
            $values = array_map(function($optionValue) {
                return new UpdateProductOptionValueItem($optionValue['option_value_id'], $optionValue['data'] );
            }, $option['values'] ?? []);

            return new UpdateProductOptionItem($option['option_id'], $option['data'], $values);
        }, $this->options);
    }
}
