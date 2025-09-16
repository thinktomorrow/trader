<?php

namespace Thinktomorrow\Trader\Application\Product\VariantProperties;

interface VariantPropertyRepository
{
    public function doesUniqueVariantPropertyCombinationExist(string $productId, array $taxonIds, ?string $excludeVariantId = null): bool;
}
