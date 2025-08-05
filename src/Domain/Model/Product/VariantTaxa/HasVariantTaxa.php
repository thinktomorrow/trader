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

    public function updateVariantTaxa(array $variantTaxa): void
    {
        Assertion::allIsInstanceOf($variantTaxa, VariantTaxon::class);

        $this->variantTaxa = $variantTaxa;
    }

    public function getVariantTaxonIds(): array
    {
        return array_map(
            fn (VariantTaxon $variantProperty) => $variantProperty->taxonId,
            $this->variantTaxa
        );
    }
}
