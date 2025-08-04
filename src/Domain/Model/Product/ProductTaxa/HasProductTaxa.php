<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\ProductVariantProperty;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

trait HasProductTaxa
{
    /** @var ProductTaxon[] */
    private array $productTaxa = [];

    /** @return ProductTaxon[] */
    public function getProductTaxa(): array
    {
        return $this->productTaxa;
    }

    public function updateProductTaxa(array $productTaxa): void
    {
        Assertion::allIsInstanceOf($productTaxa, ProductTaxon::class);

        $this->productTaxa = $this->enforceUniqueProductTaxa($productTaxa);

        $this->cleanupRemovedVariantPropertiesOnVariants($this->productVariantProperties, $this->getAvailableVariantProperties());

        $this->recordEvent(new ProductTaxaUpdated($this->productId));
    }

    private function cleanupRemovedVariantPropertiesOnVariants(array $existingProductVariantProperties, array $newProductVariantProperties)
    {
        $newTaxonIds = array_map(fn(ProductVariantProperty $productVariantProperty) => $productVariantProperty->taxonId, $newProductVariantProperties);

        foreach ($existingProductVariantProperties as $existingVariantProperty) {
            if (in_array($existingVariantProperty->taxonId, $newTaxonIds)) {
                continue;
            }

            $existingTaxonIds = array_map(fn(ProductVariantProperty $prop) => $prop->taxonId, $existingProductVariantProperties);

            foreach ($this->getVariants() as $variant) {
                $variantProperties = $variant->getVariantTaxa();

                foreach ($variantProperties as $k => $v) {
                    if (in_array($v->taxonId, $existingTaxonIds)) {
                        unset($variantProperties[$k]);
                    }
                }

                if (count($variantProperties) !== count($variant->getVariantTaxa())) {
                    $variant->updateVariantTaxa(array_values($variantProperties));
                }
            }
        }
    }

    private function enforceUniqueProductTaxa(array $productTaxa): array
    {
        $uniqueProperties = [];

        foreach ($productTaxa as $property) {
            if (!isset($uniqueProperties[$property->taxonId->get()])) {
                $uniqueProperties[$property->taxonId->get()] = $property;
            }
        }

        return array_values($uniqueProperties);
    }

    /** @return ProductTaxon[] */
    private function getAvailableVariantProperties(): array
    {
        return array_filter($this->productTaxa, fn(ProductTaxon $property) => $property->taxonomyType === TaxonomyType::variant_property);
    }
}
