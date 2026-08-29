<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Queries;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;

interface TaxonHierarchy
{
    /** @return list<TaxonNode> */
    public function descendants(TaxonNode $taxon, bool $includeSelf = false): array;

    /** @return list<TaxonNode> */
    public function ancestors(TaxonNode $taxon): array;

    public function isAncestorOf(TaxonNode $ancestor, TaxonNode $descendant): bool;

    /**
     * @param  list<string>  $taxonIds
     * @return list<string>
     */
    public function expandWithDescendants(array $taxonIds): array;

    /**
     * @param  list<string>  $configuredTaxonIds
     * @param  list<string>  $assignedTaxonIds
     */
    public function containsAny(array $configuredTaxonIds, array $assignedTaxonIds): bool;
}
