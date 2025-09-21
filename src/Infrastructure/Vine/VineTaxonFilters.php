<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Queries\TaxonFilters;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Application\Taxonomy\TaxonomyItem;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\TraderConfig;

class VineTaxonFilters implements TaxonFilters
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

    public function getAvailableFilters(Locale $locale, array $scopedTaxonIds): array
    {
        $mainCategoryTaxonomyId = $this->traderConfig->getMainCategoryTaxonomyId();

        // The entire tree
        $taxonTree = $this->taxonTreeRepository->setLocale($locale)->getTree();
        $taxonomies = $this->taxonomyRepository->getForFilter();

        // Any taxa that the page is scoped to (the main taxa scope on the page)
        $scopedTaxa = $taxonTree->findMany(fn (TaxonNode $node) => in_array($node->getId(), $scopedTaxonIds));
        $scopedTaxonIds = array_map(fn (TaxonNode $node) => $node->getId(), $scopedTaxa->all());
        $scopedAncestorTaxonIds = [];

        foreach ($scopedTaxa as $taxon) {
            $scopedAncestorTaxonIds = array_merge($scopedAncestorTaxonIds, $taxon->pluckAncestorNodes('id'));
        }

        // All the products belonging to the scoped taxa that serve as the base reference for the filter
        $productIds = $this->getGridProductIds($scopedTaxonIds);

        /**
         * The products belonging to the main taxon determine which taxa will
         * be returned as filters. Here we shake out the taxon tree so there
         * are only taxa left that match one or more of the same products
         *
         * - Only fetch taxa that are related to the given listing of products
         * - Remove offline taxa
         */
        $taxonTree = $taxonTree
            ->shake(fn (TaxonNode $node) => count(array_intersect($node->getGridProductIds(), $productIds)) > 0)
            ->remove(fn (TaxonNode $node) => ! $node->showOnline());

        $result = array_values(array_map(fn (TaxonomyItem $taxonomy) => [
            'taxonomy' => $taxonomy,
            'taxa' => [],
        ], $taxonomies));

        /** @var TaxonNode $taxon */
        foreach ($taxonTree->all() as $taxon) {
            foreach ($result as $i => $item) {
                if ($item['taxonomy']->getTaxonomyId() == $taxon->getTaxonomyId()) {

                    // For the taxonomy type variant_property, we want to shake on the online variants instead of products
                    if ($item['taxonomy']->getTaxonomyType() == TaxonomyType::variant_property->value) {
                        $shakenTaxa = TaxonTree::fromIterable([$taxon])->shake(function (TaxonNode $node) use ($productIds) {
                            return count(array_intersect($node->getGridProductIds(), $productIds)) > 0 && count($node->getGridVariantIds()) > 0;
                        })->all();

                        if (count($shakenTaxa) > 0) {
                            $result[$i]['taxa'] = array_merge($result[$i]['taxa'], $shakenTaxa);
                        }
                    }

                    // For a main category taxon page, we only want to show the
                    // children of the scoped taxa (if only one is selected)
                    elseif ($item['taxonomy']->getTaxonomyId() == $mainCategoryTaxonomyId && count($scopedTaxonIds) == 1 && in_array($taxon->getId(), $scopedTaxonIds)) {
                        foreach ($taxon->getChildNodes() as $childTaxon) {
                            $result[$i]['taxa'][] = $childTaxon;
                        }
                    } else {
                        $result[$i]['taxa'][] = $taxon;
                    }
                }
            }
        }

        return $result;
    }

    public function getActiveFilters(Locale $locale, array $scopedTaxonKeys, array $activeTaxonKeys): TaxonTree
    {
        /** @var TaxonTree $taxonTree */
        $taxonTree = $this->taxonTreeRepository->setLocale($locale)->getTree()
            ->findMany(fn (TaxonNode $node) => in_array($node->getKey(), $scopedTaxonKeys));

        /**
         * Subfiltering from current request
         *
         *  If any of the selected taxa belong to the same root as the scoped taxon, we filter down into the scoped taxon
         *  and therefore omit the scoped taxon as filter and use the selected nested taxa as the active filters.
         */
        if (count($activeTaxonKeys) > 0) {
            $selectedTaxa = $this->taxonTreeRepository->getTree()
                ->findMany(fn ($node) => in_array($node->getKey(), $activeTaxonKeys) && ! in_array($node->getKey(), $scopedTaxonKeys));

            foreach ($taxonTree->all() as $scopedTaxon) {
                foreach ($selectedTaxa as $selectedTaxon) {
                    if (in_array($scopedTaxon->getNodeId(), $selectedTaxon->pluckAncestorNodes('id'))) {
                        $taxonTree = $taxonTree->removeNode($scopedTaxon);
                    }
                }
            }

            $taxonTree = $taxonTree->merge($selectedTaxa);
        }

        return $taxonTree;
    }

    public function getFiltersFromKeys(Locale $locale, array $taxonKeys): TaxonTree
    {
        return $this->taxonTreeRepository->setLocale($locale)->getTree()
            ->remove(fn ($node) => ! in_array($node->getKey(), $taxonKeys))
            // Only get the grandchild nodes of the given keys
            ->prune(fn (TaxonNode $node) => $node->isLeafNode());
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
}
