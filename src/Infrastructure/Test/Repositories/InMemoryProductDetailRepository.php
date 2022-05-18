<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Application\Product\GetProductOptions\ProductOption;
use Thinktomorrow\Trader\Application\Product\GetProductOptions\ProductOptions;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductDetail;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Application\Product\GetProductOptions\ProductOptionsRepository;

final class InMemoryProductDetailRepository implements ProductDetailRepository, ProductOptionsRepository
{
    public function findProductDetail(VariantId $variantId): DefaultProductDetail
    {
        $variant = InMemoryVariantRepository::$variants[$variantId->get()];
        $product = InMemoryProductRepository::$products[$variant->productId->get()];

        return DefaultProductDetail::fromMappedData(array_merge($variant->getMappedData(), [
            'product_data' => json_encode($product->getData()),
            'taxon_ids' => array_map(fn($taxonId) => $taxonId->get(), $product->getTaxonIds()),
        ]));
    }

    public function getProductOptions(ProductId $productId): ProductOptions
    {
        $product = InMemoryProductRepository::$products[$productId->get()];

        $optionValues = [];

        /** @var Option $option */
        foreach($product->getOptions() as $option) {
            /** @var OptionValue $optionValue */
            foreach($option->getOptionValues() as $optionValue) {
                $optionValues[] = $optionValue->getMappedData();
            }
        }

        $productOptions = [];
        foreach($optionValues as $optionValue) {
            $productOptions[] = ProductOption::fromMappedData($optionValue);
        }

        return ProductOptions::fromType($productOptions);
    }
}
