<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;

class VineTaxonFilterTreeComposer implements TaxonFilterTreeComposer
{
    private TaxonTreeRepository $taxonTreeRepository;

    public function __construct(TaxonTreeRepository $taxonTreeRepository)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    public function getAvailableFilters(string $mainTaxonFilterKey): TaxonTree
    {
        /** @var TaxonNode $mainTaxonNode */
        $mainTaxonNode = $this->taxonTreeRepository->getTree()->find(fn(TaxonNode $node) => $node->getKey() == $mainTaxonFilterKey);

        if(!$mainTaxonNode) {
            return new TaxonTree();
        }

        $productIds = $this->getProductIds($mainTaxonNode);

        /**
         * The products belonging to the main taxon determine which taxons will
         * be returned as filters. Here we shake out the taxon tree so there
         * are only taxa left that match one or more of the same products
         */
        $taxonTree = $this->taxonTreeRepository->getTree()
            ->shake(fn (TaxonNode $node) => array_intersect($node->getProductIds(), $productIds))
            ->prune(fn (TaxonNode $node) => $node->showOnline());

        // For a better filter representation, we want to start from the given taxon as the root - and not the 'real' root.
        // Therefor we exclude all ancestors from the given taxon which allows to only show the
        // nested taxa. This is purely a visual improvement for the filter.
        if (! $mainTaxonNode->isRootNode() && ($ancestorIds = $mainTaxonNode->pluckAncestorNodes('id'))) {

            // Keep the root node in the filter in order to keep our structure intact
            array_pop($ancestorIds);

            $taxonTree = $taxonTree
                ->prune(fn ($node) => ! in_array($node->getNodeId(), [$mainTaxonNode->getNodeId(), ...$ancestorIds]));
        }

        return $taxonTree;
//        return new TaxonFilters($this->convertNodeCollectionToArray($filterTaxons));
    }

    public function getActiveFilters(string $mainTaxonFilterKey, array $activeKeys): TaxonTree
    {
        $mainTaxonNode = $this->taxonTreeRepository->getTree()->find(fn($node) => $node->getKey() == $mainTaxonFilterKey);

        if(!$mainTaxonNode) {
            return new TaxonTree();
        }

        // The main category is always considered 'active' as a grid filter, with or without any other filtering active
        $taxonTree = new TaxonTree([$mainTaxonNode]);

        /** Used filters from current request */
        if (count($activeKeys) > 0) {

            $selectedTaxons = $this->taxonTreeRepository->getTree()->findMany(fn($node) => in_array($node->getKey(), $activeKeys));

            /**
             * Subfiltering
             * If any of the selected taxa belong to the same root as the main taxon, we filter down into the main taxon
             * and therefore omit the main taxon as filter and use the selected taxa as the only active filters instead.
             */
            foreach ($selectedTaxons as $selectedTaxon) {
                if ($selectedTaxon->getRootNode()->getNodeId() === $mainTaxonNode->getRootNode()->getNodeId()) {
                    $taxonTree = new TaxonTree();
                }
            }

            $taxonTree = $taxonTree->merge($selectedTaxons);
        }

        return $taxonTree;

//        return TaxonFilters::fromType(
//            $this->convertNodeCollectionToArray($collection)
//        );
    }

//    private function convertNodeCollectionToArray(NodeCollection $collection): array
//    {
//        $filters = [];
//
//        $collection->each(function(Node $node) use(&$filters) {
//
//            /** @var TaxonFilter $taxonFilter */
//            $taxonFilter = $node->getNodeEntry();
//
//            if($node->hasChildNodes()) {
//                $taxonFilter->setChildren(
//                    $this->convertNodeCollectionToArray($node->getChildNodes())
//                );
//            }
//
//            $filters[] = $taxonFilter;
//        });
//
//        return $filters;
//    }

    /**
     * Get all product ids belonging to this taxon filter and all its children
     *
     * @return array
     */
    private function getProductIds(TaxonNode $node): array
    {
        $productIds = $node->getProductIds();

        $node->getChildNodes()->flatten()->each(function ($childNode) use (&$productIds) {
            $productIds = array_merge($productIds, $childNode->getProductIds());
        });

        return array_values(array_unique($productIds));
    }
}
