<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\GetProductOptions;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;

/**
 * DTO for composing the simple option array, ready for usage in an admin form select.
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
        $options = $product->getOptions();

        $output = [];

        foreach($options as $option) {
            $output[] = [
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

        return $output;
    }
}
