<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;

trait HasProductTaxa
{
    /** @var ProductTaxon[] */
    private array $productTaxa = [];

    /** @return ProductTaxon[] */
    public function getProductTaxa(): array
    {
        return $this->productTaxa;
    }

    /** @return array<VariantProperty> */
    public function getVariantProperties(): array
    {
        return array_filter($this->productTaxa, fn (ProductTaxon $property) => $property instanceof VariantProperty);
    }

    public function updateProductTaxa(array $productTaxa): void
    {
        Assertion::allIsInstanceOf($productTaxa, ProductTaxon::class);

        // TODO/ move this checks to the APPLICATION so this domein is much simpler!!!!
        $oldVariantProperties = $this->getVariantProperties();

        $this->productTaxa = $this->enforceUniqueProductTaxa($productTaxa);

        $this->cleanupRemovedVariantPropertiesOnVariants($oldVariantProperties, $this->getVariantProperties());

        $this->recordEvent(new ProductTaxaUpdated($this->productId));
    }

    private function cleanupRemovedVariantPropertiesOnVariants(array $oldAvailableVariantProperties, array $newAvailableVariantProperties)
    {
        $newTaxonIds = array_map(fn (VariantProperty $prop) => $prop->taxonId, $newAvailableVariantProperties);

        // bepaal welke taxonIds zijn verdwenen
        $removedTaxonIds = [];
        foreach ($oldAvailableVariantProperties as $oldVariantProperty) {
            if (! in_array($oldVariantProperty->taxonId, $newTaxonIds)) {
                $removedTaxonIds[] = $oldVariantProperty->taxonId;
            }
        }

        if (empty($removedTaxonIds)) {
            return; // niets verwijderd
        }

        foreach ($this->getVariants() as $variant) {
            $variantProperties = $variant->getVariantProperties();

            foreach ($variantProperties as $k => $v) {
                if (in_array($v->taxonId, $removedTaxonIds)) {
                    unset($variantProperties[$k]);
                }
            }

            if (count($variantProperties) !== count($variant->getVariantProperties())) {
                $variant->updateVariantProperties(array_values($variantProperties));
            }
        }
    }

    private function enforceUniqueProductTaxa(array $productTaxa): array
    {
        $uniqueProperties = [];

        foreach ($productTaxa as $property) {
            if (! isset($uniqueProperties[$property->taxonId->get()])) {
                $uniqueProperties[$property->taxonId->get()] = $property;
            }
        }

        return array_values($uniqueProperties);
    }

    /** @return ProductTaxon[] */
    private function getAvailableVariantProperties(): array
    {
        return $this->getVariantProperties();
    }
}
