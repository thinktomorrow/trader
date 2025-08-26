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
        return array_filter($this->variantTaxa, fn(VariantTaxon $property) => $property instanceof VariantProperty);
    }

    public function updateVariantTaxa(array $variantTaxa): void
    {
        Assertion::allIsInstanceOf($variantTaxa, VariantTaxon::class);

        $this->variantTaxa = $variantTaxa;
    }

    public function getVariantTaxonIds(): array
    {
        return array_map(
            fn(VariantTaxon $variantProperty) => $variantProperty->taxonId,
            $this->variantTaxa
        );
    }
}
