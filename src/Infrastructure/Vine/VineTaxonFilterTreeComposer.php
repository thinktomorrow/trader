<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\TraderConfig;

class VineTaxonFilterTreeComposer implements TaxonFilterTreeComposer
{
    private TaxonTreeRepository $taxonTreeRepository;
    private TraderConfig $traderConfig;

    public function __construct(TraderConfig $traderConfig, TaxonTreeRepository $taxonTreeRepository)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
        $this->traderConfig = $traderConfig;
    }

    public function getAvailableFilters(Locale $locale, array $rootTaxonKeys): TaxonTree
    {
        /** @var TaxonTree $rootTaxa */
        $rootTaxa = $this->taxonTreeRepository->setLocale($locale)->getTree()
            ->findMany(fn(TaxonNode $node) => in_array($node->getKey(), $rootTaxonKeys));

        //        /** @var TaxonNode $mainTaxonNode */
//        $mainTaxonNode = $this->taxonTreeRepository->setLocale($locale)->getTree()->find(fn(TaxonNode $node) => $node->getKey() == $mainTaxonFilterKey);

//        if (!$mainTaxonNode) {
//            return new TaxonTree();
//        }

//        $categoryRootId = $this->traderConfig->getCategoryTaxonomyId();

        $rootTaxonIds = $rootTaxa->pluck('id');
        $productIds = $this->getOnlineProductIds($rootTaxonIds);

        /**
         * The products belonging to the main taxon determine which taxons will
         * be returned as filters. Here we shake out the taxon tree so there
         * are only taxa left that match one or more of the same products
         */
        $taxonTree = $this->taxonTreeRepository->getTree()
            // For the category taxa, only return the taxa that belong to one of the given main taxa
            ->shake(fn(TaxonNode $node) => count(array_intersect($rootTaxonIds, [$node->getNodeId(), ...$node->pluckAncestorNodes('id')])) > 0)

            // Only fetch taxa that are related to the given listing of products
            ->shake(fn(TaxonNode $node) => count(array_intersect($node->getOnlineProductIds(), $productIds)) > 0)

            // Remove offline taxa
            ->remove(fn(TaxonNode $node) => !$node->showOnline());

        // For a better filter representation, we want to start from the given taxon as the root - and not the 'real' root.
        // Therefor we exclude all ancestors from the given taxon which allows to only show the
        // nested taxa. This is purely a visual improvement for the filter.

        // TODO: Get any ancestor nodes of the main taxa, so we can prune the tree to only show the main taxon and its children, not any of the ancestors

//        if (!$mainTaxonNode->isRootNode() && ($ancestorIds = $mainTaxonNode->pluckAncestorNodes('id'))) {
//            // Keep the root node in the filter in order to keep our structure intact
//            array_pop($ancestorIds);
//
//            $taxonTree = $taxonTree
//                ->prune(fn($node) => !in_array($node->getNodeId(), [$mainTaxonNode->getNodeId(), ...$ancestorIds]));
//        }

        return $taxonTree;
    }

    public function getActiveFilters(Locale $locale, array $rootTaxonKeys, array $activeKeys): TaxonTree
    {
        /** @var TaxonTree $rootTaxa */
        $rootTaxa = $this->taxonTreeRepository->setLocale($locale)->getTree()
            ->findMany(fn(TaxonNode $node) => in_array($node->getKey(), $rootTaxonKeys));

//        $mainTaxonNode = $this->taxonTreeRepository->setLocale($locale)->getTree()->find(fn($node) => $node->getKey() == $mainTaxonFilterKey);
//
//        if (!$mainTaxonNode) {
//            return new TaxonTree();
//        }

        // The main category is always considered 'active' as a grid filter, with or without any other filtering active
        $taxonTree = $rootTaxa;

        /** Used filters from current request */
        if (count($activeKeys) > 0) {
            $selectedTaxa = $this->taxonTreeRepository->getTree()
                ->findMany(fn($node) => in_array($node->getKey(), $activeKeys));

            // Remove any parents where the child taxon is present in the payload.
            // We want to filter on the more specific child taxon - and not in combination with its parent.
            //                ->remove(function (TaxonNode $node) use ($activeKeys) {
            //                    return count(array_intersect($activeKeys, $node->pluckChildNodes('getKey'))) > 0;
            //                });

            /**
             * Subfiltering
             * If any of the selected taxa belong to the same root as the main taxon, we filter down into the main taxon
             * and therefore omit the main taxon as filter and use the selected taxa as the only active filters instead.
             */
            foreach ($taxonTree->all() as $rootTaxon) {
                foreach ($selectedTaxa as $selectedTaxon) {
                    if (in_array($selectedTaxon->getRootNode()->getNodeId(), $rootTaxa->pluck('id'))) {
                        $taxonTree = $taxonTree->removeNode($rootTaxon);
                    }
                }
            }


            $taxonTree = $taxonTree->merge($selectedTaxa);
        }

        return $taxonTree;
    }

    /**
     * Get all online product ids belonging to this taxon filter and all its children
     *
     * @return array
     */
    public function getOnlineProductIds(array $taxonIds): array
    {
        return $this->getProductIds($taxonIds, true);
    }

    /**
     * Get all product ids belonging to this taxon filter and all its children
     *
     * @return array
     */
    public function getProductIds(array $taxonIds, bool $onlineOnly = false): array
    {
        $nodes = $this->taxonTreeRepository->getTree()->findMany(fn(TaxonNode $node) => in_array($node->getId(), $taxonIds));

        $productIds = [];

        foreach ($nodes as $node) {
            $productIds = array_merge($productIds, ($onlineOnly ? $node->getOnlineProductIds() : $node->getProductIds()));

            $node->getChildNodes()->flatten()->each(function ($childNode) use (&$productIds, $onlineOnly) {
                $productIds = array_merge($productIds, ($onlineOnly ? $childNode->getOnlineProductIds() : $childNode->getProductIds()));
            });
        }

        return array_values(array_unique($productIds));
    }
}
