<?php

namespace Thinktomorrow\Trader\Application\Product\VariantProperties;

interface VariantPropertyRepository
{
    public function doesUniqueVariantPropertyCombinationExist(array $taxonIds, ?string $excludeVariantId = null): bool;
}
