<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Product\Grid\FlattenedTaxonIds;
use Thinktomorrow\Trader\Application\Taxon\Queries\TaxonHierarchy;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;

/**
 * Compose a list of taxon ids including all children and grandchildren
 * for a given list of taxon ids or keys. This is internally used
 * for filtering the grid on taxa.
 */
class VineFlattenedTaxonIds implements FlattenedTaxonIds
{
    private TaxonTreeRepository $taxonTreeRepository;

    private TaxonHierarchy $taxonHierarchy;

    public function __construct(TaxonTreeRepository $taxonTreeRepository, ?TaxonHierarchy $taxonHierarchy = null)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
        $this->taxonHierarchy = $taxonHierarchy ?? new VineTaxonHierarchy($taxonTreeRepository);
    }

    /**
     * Compose a list of taxon ids including all children and
     * grandchildren for a given list of taxon keys.
     *
     * It returns an array grouped by taxonomy id:
     * [
     *   'taxonomyId1' => [1, 2, 3, 4],
     *   'taxonomyId2' => [5, 6, 7],
     * ]
     */
    public function getGroupedByTaxonomyByKeys(array $taxonKeys): array
    {
        return $this->getNestedTaxonIds($taxonKeys);
    }

    /**
     * Compose a list of taxon ids including all children and
     * grandchildren for a given list of taxon ids.
     *
     * It returns an array grouped by taxonomy id:
     * [
     *   'taxonomyId1' => [1, 2, 3, 4],
     *   'taxonomyId2' => [5, 6, 7],
     * ]
     */
    public function getGroupedByTaxonomyByIds(array $taxonIds): array
    {
        return $this->getNestedTaxonIds($taxonIds, false);
    }

    private function getNestedTaxonIds(array $taxonKeys, bool $passedAsKeys = true): array
    {
        // Get all taxa including their grandchildren - remember that each taxon key
        // is unique across all the taxonomy entries so we can safely retrieve by key.
        $taxonIds = [];

        foreach ($taxonKeys as $key) {
            /** @var ?TaxonNode $node */
            $node = ($passedAsKeys)
                ? $this->taxonTreeRepository->getTree()->find(fn (TaxonNode $node) => $node->getKey() == $key)
                : $this->taxonTreeRepository->getTree()->find(fn (TaxonNode $node) => $node->getNodeId() == $key);

            if (! $node) {
                continue;
            }

            $taxonomyId = $node->getTaxonomyId();

            if (! isset($taxonIds[$taxonomyId])) {
                $taxonIds[$taxonomyId] = [];
            }

            $taxonIds[$taxonomyId] = array_merge(
                $taxonIds[$taxonomyId],
                array_map(fn (TaxonNode $taxon): string => $taxon->getId(), $this->taxonHierarchy->descendants($node, true))
            );
        }

        foreach ($taxonIds as $taxonomyId => $ids) {
            $taxonIds[$taxonomyId] = array_unique($ids);
        }

        return $taxonIds;
    }
}
