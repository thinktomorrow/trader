<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\VariantLinks;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;

/**
 * DTO for composing the simple option array,
 * ready for usage in an admin form select
 */
class VariantPropertiesForSelect
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function get(string $productId): array
    {
        $product = $this->productRepository->find(ProductId::fromString($productId));

        return array_map(fn(VariantProperty $variantProperty) => $this->convertToArrayItem($variantProperty), $product->getVariantProperties());
    }

    // How do we want the array to look like? ...
    private function convertToArrayItem(VariantProperty $property): array
    {
        return [
            'taxon_id' => $property->taxonId->get(),
            'data' => $property->getData(),
            'values' => array_map(function ($value) {
                return [
                    'option_value_id' => $value->optionValueId->get(),
                    'data' => $value->getData(),
                ];
            }, $property->getOptionValues()),
        ];
    }
}
