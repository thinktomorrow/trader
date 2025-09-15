<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\TraderConfig;

class VineTaxonFilterTreeComposer implements TaxonFilterTreeComposer
{
    private TaxonTreeRepository $taxonTreeRepository;

    private TraderConfig $traderConfig;
    private TaxonomyRepository $taxonomyRepository;

    public function __construct(TraderConfig $traderConfig, TaxonTreeRepository $taxonTreeRepository, TaxonomyRepository $taxonomyRepository)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
        $this->traderConfig = $traderConfig;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    public function getAvailableFiltersExcludingRoots(Locale $locale, array $rootTaxonKeys): TaxonTree
    {
        return $this->getAvailableFilters($locale, $rootTaxonKeys, false);
    }

    public function getAvailableFilters(Locale $locale, array $scopedTaxonIds): array
    {
        // The entire tree
        $taxonTree = $this->taxonTreeRepository->setLocale($locale)->getTree();
        $taxonomies = $this->taxonomyRepository->getForFilter();

        // The taxa that are scoped (the main taxa selected by the user)
        $scopedTaxa = $taxonTree->findMany(fn (TaxonNode $node) => in_array($node->getId(), $scopedTaxonIds));
        $scopedTaxonIds = array_map(fn (TaxonNode $node) => $node->getId(), $scopedTaxa->all());
        $scopedAncestorTaxonIds = [];

        foreach ($scopedTaxa as $taxon) {
            $scopedAncestorTaxonIds = array_merge($scopedAncestorTaxonIds, $taxon->pluckAncestorNodes('id'));
        }

        // All the products belonging to the scoped taxa that serve as the basis for the filter
        $productIds = $this->getGridProductIds($scopedTaxonIds);

        /**
         * The products belonging to the main taxon determine which taxa will
         * be returned as filters. Here we shake out the taxon tree so there
         * are only taxa left that match one or more of the same products
         */
        $taxonTree = $taxonTree

            // TODO: this is only for the taxons that belong to the same taxonomy as the scoped taxa

            // Remove ancestor nodes that are above the given root taxa
//            ->prune(fn(TaxonNode $node) => !in_array($node->getNodeId(), $scopedAncestorTaxonIds))

            // For the category taxa, only return the taxa that belong to one of the given main taxa
//            ->shake(fn(TaxonNode $node) => count(array_intersect($scopedTaxonIds, [$node->getNodeId(), ...$node->pluckAncestorNodes('id')])) > 0)

            // Only fetch taxa that are related to the given listing of products
            ->shake(function (TaxonNode $node) use ($productIds) {
                return count(array_intersect($node->getGridProductIds(), $productIds)) > 0;
            })
//            ->shake(fn(TaxonNode $node) => count(array_intersect($node->getOnlineProductIds(), $productIds)) > 0)

            // Remove offline taxa
            ->remove(fn (TaxonNode $node) => ! $node->showOnline());

        // b591c1f6-23c6-4f71-bf11-c60df6e36a23 - large

        // get online variants based on scope taxa (and showed in grid)
        // only show variant property taxonomies if there are online variants matching

        $result = array_map(fn (Taxonomy $taxonomy) => [
            'taxonomy' => $taxonomy,
            'taxa' => [],
        ], $taxonomies);

        /** @var TaxonNode $taxon */
        foreach ($taxonTree->all() as $taxon) {
            foreach ($result as $i => $item) {
                if ($item['taxonomy']->taxonomyId->get() == $taxon->getTaxonomyId()) {

                    // For the taxonomy type variant_property, we want to shake on the online variants instead of products
                    if ($item['taxonomy']->getType() == TaxonomyType::variant_property) {
                        $shakenTaxa = TaxonTree::fromIterable([$taxon])->shake(function (TaxonNode $node) use ($productIds) {
                            return count(array_intersect($node->getGridProductIds(), $productIds)) > 0 && count($node->getGridVariantIds()) > 0;
                        })->all();

                        if (count($shakenTaxa) > 0) {
                            $result[$i]['taxa'] = array_merge($result[$i]['taxa'], $shakenTaxa);
                        }
                    } else {
                        $result[$i]['taxa'][] = $taxon;
                    }
                }
            }
        }
        // Sort by order of taxonomy,
        // sort taxa per taxonomy by order
        // Get only for taxonomy that is set to be used as filter
        // ... and only if there are products for these taxa


        //        $rootTaxa = $this->taxonTreeRepository->setLocale($locale)->getTree()
        //            ->findMany(fn(TaxonNode $node) => in_array($node->getKey(), $scopedTaxonIds));

        //        /** @var TaxonNode $mainTaxonNode */
        //        $mainTaxonNode = $this->taxonTreeRepository->setLocale($locale)->getTree()->find(fn(TaxonNode $node) => $node->getKey() == $mainTaxonFilterKey);

        //        if (!$mainTaxonNode) {
        //            return new TaxonTree();
        //        }

        //        $categoryRootId = $this->traderConfig->getMainCategoryTaxonomyId();
        //        $rootTaxonIds = array_map(fn($taxon) => $taxon->getNodeId(), $rootTaxa->all());


        // For a better filter representation, we want to start from the given taxon as the root - and not the 'real' root.
        // Therefor we exclude all ancestors from the given taxon which allows to only show the
        // nested taxa. This is purely a visual improvement for the filter.

        //        // TODO: Get any ancestor nodes of the main taxa, so we can prune the tree to only show the main taxon and its children, not any of the ancestors
        //        if (!$keepRootTaxa) {
        //            $childrenTree = new TaxonTree();
        //            foreach ($rootTaxa as $rootTaxon) {
        //                $childrenTree = $childrenTree->merge($rootTaxon->getChildNodes());
        //            }
        //            $taxonTree = $childrenTree;
        //        }
        //        dd($taxonTree);
        return $result;
    }

    public function getActiveFilters(Locale $locale, array $rootTaxonKeys, array $activeKeys): TaxonTree
    {
        /** @var TaxonTree $rootTaxa */
        $rootTaxa = $this->taxonTreeRepository->setLocale($locale)->getTree()
            ->findMany(fn (TaxonNode $node) => in_array($node->getKey(), $rootTaxonKeys));

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
                ->findMany(fn ($node) => in_array($node->getKey(), $activeKeys));

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
                    if (in_array($rootTaxon->getNodeId(), $selectedTaxon->pluckAncestorNodes('id'))) {
                        $taxonTree = $taxonTree->removeNode($rootTaxon);
                    }
                }
            }

            $taxonTree = $taxonTree->merge($selectedTaxa);
        }

        return $taxonTree;
    }

    public function getFiltersFromKeys(Locale $locale, array $taxonKeys): TaxonTree
    {
        $taxonTree = $this->taxonTreeRepository->setLocale($locale)->getTree()
            ->findMany(fn ($node) => in_array($node->getKey(), $taxonKeys));

        return $taxonTree;
    }

    /**
     * Get all online product ids belonging to this taxon filter and all its children
     */
    public function getGridProductIds(array $taxonIds): array
    {
        return $this->getProductIds($taxonIds, true);
    }

    /**
     * Get all product ids belonging to this taxon filter and all its children
     */
    public function getProductIds(array $taxonIds, bool $onlineOnly = false): array
    {
        $nodes = $this->taxonTreeRepository->getTree()->findMany(fn (TaxonNode $node) => in_array($node->getId(), $taxonIds));

        $productIds = [];

        foreach ($nodes as $node) {
            $productIds = array_merge($productIds, ($onlineOnly ? $node->getGridProductIds() : $node->getProductIds()));

            $node->getChildNodes()->flatten()->each(function ($childNode) use (&$productIds, $onlineOnly) {
                $productIds = array_merge($productIds, ($onlineOnly ? $childNode->getGridProductIds() : $childNode->getProductIds()));
            });
        }

        return array_values(array_unique($productIds));
    }

    private function getOnlineVariantIds(array $taxonIds): array
    {
        return $this->getVariantIds($taxonIds, true);
    }

    private function getVariantIds(TaxonTree $taxonTree): array
    {
        $variantIds = [];
        dd($taxonTree);
        $taxonTree->eachRecursive(function (TaxonNode $node) use (&$variantIds) {
            if (count($node->getGridVariantIds()) > 0) {
                dd($node->getGridVariantIds());
            }
            $variantIds = array_merge($variantIds, $node->getGridVariantIds());
        });

        return array_values(array_unique($variantIds));
    }
}
