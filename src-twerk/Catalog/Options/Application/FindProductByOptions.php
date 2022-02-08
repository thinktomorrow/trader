<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Application;

use Thinktomorrow\Trader\Catalog\Options\Domain\Option;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroup;

class FindProductByOptions
{
    /**
     * Find the product that has the same options as the referred current product but for a specific option alteration.
     * This is the action used to determine the links behind each option on the product page.
     *
     * @param Product $currentProduct
     * @param Option $option
     * @return Product|null
     */
    public function find(ProductGroup $productGroup, Product $currentProduct, Option $option): ?Product
    {
        $desiredOptionIds = [];

        foreach ($currentProduct->getOptions() as $currentOption) {
            $desiredOptionIds[] = ($option->getOptionTypeId() === $currentOption->getOptionTypeId())
                ? $option->getId()
                : $currentOption->getId();
        }

        // If present product does not have an option value for the targeted option, we cannot provide a product
        if (! in_array($option->getId(), $desiredOptionIds)) {
            return null;
        }

        return $productGroup->getProducts()->first(function (Product $product) use ($desiredOptionIds) {
            $fullMatch = true;

            foreach ($desiredOptionIds as $optionId) {
                if (! $product->hasOption($optionId)) {
                    $fullMatch = false;
                }
            }

            return $fullMatch;
        });
    }
}
