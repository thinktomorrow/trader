<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Vine\Node;
use Thinktomorrow\Vine\NodeCollection;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilter;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilters;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonTreeRepository;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;

class VineTaxonFilterTreeComposer implements TaxonFilterTreeComposer
{
    use UsesTaxonFilterTree;

    private TaxonTreeRepository $taxonTreeRepository;

    public function __construct(TaxonTreeRepository $taxonTreeRepository)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    public function getAvailableFilters(string $mainTaxonFilterKey): TaxonFilters
    {
        $mainTaxonNode = $this->getTree()->find(fn($node) => $node->getNodeEntry()->getKey() == $mainTaxonFilterKey);

        if(!$mainTaxonNode) {
            return new TaxonFilters([]);
        }

        $productIds = $this->getProductIds($mainTaxonNode);

        $filterTaxons = $this->getTree()
            ->shake(fn ($node) => array_intersect($node->getNodeEntry()->getProductIds(), $productIds))
            ->prune(fn ($node) => $node->getNodeEntry()->showOnline());

        // For a better filter representation, we want to start from the given taxon as the root - and not the 'real' root.
        // Therefor we exclude all ancestors from the given taxon which allows to only show the
        // nested taxa. This is purely a visual improvement for the filter.
        if (! $mainTaxonNode->isRootNode() && ($ancestorIds = $mainTaxonNode->pluckAncestorNodes('id'))) {

            // Keep the root node in the filter in order to keep our structure intact
            array_pop($ancestorIds);

            $filterTaxons = $filterTaxons
                ->prune(fn ($node) => ! in_array($node->getNodeId(), [$mainTaxonNode->getNodeId(), ...$ancestorIds]));
        }

        return new TaxonFilters($this->convertNodeCollectionToArray($filterTaxons));
    }

    public function getActiveFilters(string $mainTaxonFilterKey, array $activeKeys): TaxonFilters
    {
        $mainTaxonNode = $this->getTree()->find(fn($node) => $node->getNodeEntry()->getKey() == $mainTaxonFilterKey);

        if(!$mainTaxonNode) {
            return new TaxonFilters([]);
        }

        // The main category is always considered 'active' as a grid filter, with or without any other filtering active
        $collection = new NodeCollection([$mainTaxonNode]);

        /** Used filters from current request */
        if (count($activeKeys) > 0) {

            $selectedTaxons = $this->getTree()->findMany(fn($node) => in_array($node->getNodeEntry()->getKey(), $activeKeys));

            /**
             * Subfiltering
             * If any of the selected taxa belong to the same root as the main taxon, we filter down into the main taxon
             * and therefore omit the main taxon as filter and use the selected taxa as the only active filters instead.
             */
            foreach ($selectedTaxons as $selectedTaxon) {
                if ($selectedTaxon->getRootNode()->getNodeId() === $mainTaxonNode->getRootNode()->getNodeId()) {
                    $collection = new NodeCollection();
                }
            }

            $collection = $collection->merge($selectedTaxons);
        }

        return new TaxonFilters(
            $this->convertNodeCollectionToArray($collection)
        );
    }

    private function convertNodeCollectionToArray(NodeCollection $collection): array
    {
        $filters = [];

        $collection->each(function(Node $node) use(&$filters) {

            /** @var TaxonFilter $taxonFilter */
            $taxonFilter = $node->getNodeEntry();

            if($node->hasChildNodes()) {
                $taxonFilter->setChildren(
                    $this->convertNodeCollectionToArray($node->getChildNodes())
                );
            }

            $filters[] = $taxonFilter;
        });

        return $filters;
    }

    /**
     * Get all product ids belonging to this taxon filter and all its children
     *
     * @return array
     */
    private function getProductIds(Node $node): array
    {
        $productIds = $node->getNodeEntry()->getProductIds();

        $node->getChildNodes()->flatten()->each(function ($childNode) use (&$productIds) {
            $productIds = array_merge($productIds, $childNode->getNodeEntry()->getProductIds());
        });

        return array_values(array_unique($productIds));
    }
}
