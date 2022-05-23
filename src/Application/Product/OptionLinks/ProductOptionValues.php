<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\OptionLinks;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;

/**
 * DTO for composing the simple option array,
 * ready for usage in an admin form select
 */
class ProductOptionValues
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function get(string $product_id): array
    {
        $product = $this->productRepository->find(ProductId::fromString($product_id));

        return array_map(fn($option) => $this->convertToArrayItem($option), $product->getOptions());
    }

    private function convertToArrayItem(Option $option): array
    {
        return [
            'option_id' => $option->optionId->get(),
            'data' => $option->getData(),
            'values' => array_map(function($optionValue){
                return [
                    'option_value_id' => $optionValue->optionValueId->get(),
                    'data' => $optionValue->getData(),
                ];
            }, $option->getOptionValues()),
        ];
    }
}
