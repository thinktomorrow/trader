<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa;

use Assert\Assertion;

trait HasVariantTaxa
{
    /** @var VariantTaxon[] */
    private array $variantTaxa = [];

    /** @return VariantTaxon[] */
    public function getVariantTaxa(): array
    {
        return $this->variantTaxa;
    }

    /** @return array<VariantProperty> */
    public function getVariantProperties(): array
    {
        return array_filter($this->variantTaxa, fn (VariantTaxon $property) => $property instanceof VariantProperty);
    }

    public function updateVariantTaxa(array $variantTaxa): void
    {
        Assertion::allIsInstanceOf($variantTaxa, VariantTaxon::class);

        $this->variantTaxa = $variantTaxa;
    }

    public function updateVariantProperties(array $variantProperties): void
    {
        Assertion::allIsInstanceOf($variantProperties, VariantProperty::class);

        // Remove all existing variant properties first
        $variantTaxa = array_filter($this->variantTaxa, fn (VariantTaxon $taxon) => ! ($taxon instanceof VariantProperty));

        // Merge with the new ones
        $this->variantTaxa = array_merge($variantTaxa, $variantProperties);
    }

    public function getVariantTaxonIds(): array
    {
        return array_map(
            fn (VariantTaxon $variantProperty) => $variantProperty->taxonId,
            $this->variantTaxa
        );
    }
}
