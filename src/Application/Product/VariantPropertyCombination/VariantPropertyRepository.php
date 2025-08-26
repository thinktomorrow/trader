<?php

namespace Thinktomorrow\Trader\Application\Product\VariantPropertyCombination;

interface VariantPropertyRepository
{
    public function doesUniqueVariantPropertyCombinationExist(array $taxonIds, ?string $excludeVariantId = null): bool;
}
