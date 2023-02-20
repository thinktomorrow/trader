<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Filter;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Domain\Common\Locale;

interface TaxonFilterTreeComposer
{
    public function getAvailableFilters(Locale $locale, string $mainTaxonFilterKey): TaxonTree;

    public function getActiveFilters(Locale $locale, string $mainTaxonFilterKey, array $activeKeys): TaxonTree;

    /**
     * Get all product ids belonging to this taxon filter and all its children
     */
    public function getProductIds(string $taxonId): array;

    public function getOnlineProductIds(string $taxonId): array;
}
