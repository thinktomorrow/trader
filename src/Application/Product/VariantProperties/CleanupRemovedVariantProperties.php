<?php

namespace Thinktomorrow\Trader\Application\Product\VariantProperties;

use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;

class CleanupRemovedVariantProperties
{
    public function handle(Product $product, array $formerVariantProperties): void
    {
        $newTaxonIds = array_map(fn(ProductTaxon $prod) => $prod->taxonId, $product->getProductTaxa());

        foreach ($formerVariantProperties as $existingVariantProperty) {
            if (in_array($existingVariantProperty->taxonId, $newTaxonIds)) {
                continue;
            }

            $formerTaxonIds = array_map(fn(ProductTaxon $prop) => $prop->taxonId, $formerVariantProperties);

            foreach ($product->getVariants() as $variant) {
                $variantProperties = $variant->getVariantTaxa();

                foreach ($variantProperties as $k => $v) {
                    if (in_array($v->taxonId, $formerTaxonIds)) {
                        unset($variantProperties[$k]);
                    }
                }

                if (count($variantProperties) !== count($variant->getVariantTaxa())) {
                    $variant->updateVariantTaxa(array_values($variantProperties));
                }
            }
        }
    }
}
