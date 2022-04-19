<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Application\Product\Grid\FlattenedTaxonIdsComposer;

class VineFlattenedTaxonIdsComposer implements FlattenedTaxonIdsComposer
{
    use UsesTaxonTree;

    private TaxonTreeRepository $taxonTreeRepository;

    public function __construct(TaxonTreeRepository $taxonTreeRepository)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    public function getGroupedByRootByKeys(array $taxonKeys): array
    {
        return $this->getNestedTaxonIds($taxonKeys);
    }

    public function getGroupedByRootByIds(array $taxonIds): array
    {
        return $this->getNestedTaxonIds($taxonIds, false);
    }

    private function getNestedTaxonIds(array $taxonKeys, bool $passedAsKeys = true): array
    {
        // Get all taxa including their grandchildren - remember that each taxon key
        // is unique across all the taxonomy entries so we can safely retrieve by key.
        $taxonIds = [];

        foreach ($taxonKeys as $key) {

            $node = ($passedAsKeys)
                ? $this->getTree()->find(fn(TaxonNode $node) => $node->getKey() == $key)
                : $this->getTree()->find(fn(TaxonNode $node) => $node->getNodeId() == $key);

            if(!$node) continue;

            $rootId = ($node->getAncestorNodes()->isEmpty())
                ? $node->getNodeId()
                : $node->getAncestorNodes()->first()->getNodeId();

            if (! isset($taxonIds[$rootId])) {
                $taxonIds[$rootId] = [];
            }

            $taxonIds[$rootId] = array_merge($taxonIds[$rootId], $node->pluckChildNodes('id'));
        }

        foreach($taxonIds as $rootId => $ids) {
            $taxonIds[$rootId] = array_unique($ids);
        }

        return $taxonIds;
    }
}
