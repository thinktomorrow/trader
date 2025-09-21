<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Grid;

/**
 * When querying the catalog by taxa, you expect the results to include products belonging to
 * the child taxa as well. This provides the expected taxon ids for querying our catalog.
 */
interface FlattenedTaxonIds
{
    public function getGroupedByTaxonomyByKeys(array $taxonKeys): array;

    public function getGroupedByTaxonomyByIds(array $taxonIds): array;
}
