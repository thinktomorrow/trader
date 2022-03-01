<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Grid;

interface NestedTaxonIdsComposer
{
    public function getGroupedByRootByKeys(array $taxonKeys): array;

    public function getGroupedByRootByIds(array $taxonIds): array;
}
