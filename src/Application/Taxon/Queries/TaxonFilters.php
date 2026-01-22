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
    public function getAvailableFilters(array $scopedTaxonIds): array;

    /**
     * Get all root taxa as available filters for the given taxonomy
     *
     * @param string $taxonomyId
     * @return array
     */
    public function getAvailableFiltersByRoots(string $taxonomyId): array;

    public function getActiveFilters(array $scopedTaxonIds, array $activeTaxonKeys): TaxonTree;

    /**
     * Get expanded filter ids from given taxon ids (including all children)
     */
    public function getFilterIds(array $taxonIds): array;

    /**
     * Get expanded filter ids from given taxon keys (including all children)
     */
    public function getFilterIdsFromKeys(array $taxonKeys): array;

    /**
     * Get all product ids belonging to this taxon filter and all its children
     */
    public function getProductIds(array $taxonIds): array;

    public function getGridProductIds(array $taxonIds): array;

    public function setLocale(Locale $locale): static;
}
