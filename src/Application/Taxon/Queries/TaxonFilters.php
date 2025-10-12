<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Queries;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Domain\Common\Locale;

interface TaxonFilters
{
    /**
     * Return an array divided by taxonomy:
     * ['taxonomy' => <Taxonomy>, 'taxa' => [<TaxonNode>, ...]]
     */
    public function getAvailableFilters(Locale $locale, array $scopedTaxonIds): array;

    public function getActiveFilters(Locale $locale, array $scopedTaxonIds, array $activeTaxonKeys): TaxonTree;

    public function getFilterIdsFromKeys(Locale $locale, array $taxonKeys): array;

    /**
     * Get all product ids belonging to this taxon filter and all its children
     */
    public function getProductIds(array $taxonIds): array;

    public function getGridProductIds(array $taxonIds): array;
}
