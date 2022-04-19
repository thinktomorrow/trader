<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Grid;

/**
 * When querying the catalog by taxa, you expect the results to include products belonging to
 * the child taxa as well. This provides the expected taxon ids for querying our catalog.
 */
interface FlattenedTaxonIdsComposer
{
    public function getGroupedByRootByKeys(array $taxonKeys): array;

    public function getGroupedByRootByIds(array $taxonIds): array;
}
