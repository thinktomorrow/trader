<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Queries\TaxonFilters;
use Thinktomorrow\Trader\Application\Taxon\Queries\TaxonHierarchy;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Application\Taxonomy\TaxonomyItem;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\TraderConfig;

class VineTaxonFilters implements TaxonFilters
{
    private TaxonTreeRepository $taxonTreeRepository;

    private TaxonomyRepository $taxonomyRepository;

    private Locale $locale;

    private TaxonHierarchy $taxonHierarchy;

    public function __construct(TraderConfig $traderConfig, TaxonTreeRepository $taxonTreeRepository, TaxonomyRepository $taxonomyRepository, ?TaxonHierarchy $taxonHierarchy = null)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
        $this->taxonomyRepository = $taxonomyRepository;
        $this->taxonHierarchy = $taxonHierarchy ?? new VineTaxonHierarchy($taxonTreeRepository);

        $this->locale = $traderConfig->getDefaultLocale();
    }

    public function getAvailableFiltersByRoots(string $taxonomyId): array
    {
        // The entire tree
        $taxonTree = $this->taxonTreeRepository->setLocale($this->locale)->getTree();

        $rootTaxa = collect($taxonTree->all())
            ->filter(fn (TaxonNode $node) => $node->getTaxonomyId() === $taxonomyId)
            ->values()->all();

        return [[
            'taxonomy' => $this->taxonomyRepository->findForFilter(TaxonomyId::fromString($taxonomyId)),
            'taxa' => $rootTaxa,
        ]];
    }

    public function getAvailableFilters(array $scopedTaxonIds = []): array
    {
        // The entire tree
        $taxonTree = $this->taxonTreeRepository->setLocale($this->locale)->getTree();

        [$taxonTree, $productIds] = $this->buildFilterContext($taxonTree, $scopedTaxonIds);
        $taxonomies = $this->taxonomyRepository->getForFilter();

        $result = array_values(array_map(fn (TaxonomyItem $taxonomy) => [
            'taxonomy' => $taxonomy,
            'taxa' => [],
        ], $taxonomies));

        /** @var TaxonNode $taxon */
        foreach ($taxonTree->all() as $taxon) {
            foreach ($result as $i => $item) {

                if ($item['taxonomy']->getTaxonomyId() !== $taxon->getTaxonomyId()) {
                    continue;
                }

                $result[$i]['taxa'] = array_merge(
                    $result[$i]['taxa'],
                    $this->resolveFilterTaxaForTaxon(
                        $taxon,
                        $item['taxonomy'],
                        $productIds,
                        $scopedTaxonIds
                    )
                );

            }
        }

        return $result;
    }

    private function resolveFilterTaxaForTaxon(TaxonNode $taxon, TaxonomyItem $taxonomy, array $productIds, array $scopedTaxonIds): array
    {
        // 1. Variant property taxonomy → shake on variants
        // For the taxonomy type variant_property, we want to shake on the online variants instead of products
        if ($taxonomy->getTaxonomyType() === TaxonomyType::variant_property->value) {

            $shaken = TaxonTree::fromIterable([$taxon])
                ->shake(
                    fn (TaxonNode $node) => is_callable([$node, 'getGridVariantIds'])
                        && count(array_intersect($node->getGridProductIds(), $productIds)) > 0
                        && count($node->getGridVariantIds()) > 0
                )->all();

            return count($shaken) > 0 ? $shaken : [];
        }

        // 2. Taxon is scoped → show children
        // If the taxon is the scoped taxon itself, we want to show its children as filter options
        if (in_array($taxon->getId(), $scopedTaxonIds)) {
            return $taxon->getChildNodes()->all();
        }

        // 3. Taxon is ancestor of scoped → show matching scoped children
        // If the taxon is an ancestor of the scoped taxa, we don't show it
        // but rather show the children of the scoped taxa instead
        if (count(array_intersect($this->taxonIds($this->taxonHierarchy->descendants($taxon)), $scopedTaxonIds)) > 0) {
            return $taxon->findChildNodes('id', $scopedTaxonIds)->all();
        }

        // 4. Default → show taxon itself
        return [$taxon];
    }

    /** @return array{TaxonTree, list<string>} */
    private function buildFilterContext(TaxonTree $taxonTree, array $scopedTaxonIds): array
    {
        // Any taxa that the page is scoped to (the main taxa scope on the page)
        $scopedTaxa = $taxonTree->findMany(fn (TaxonNode $node) => in_array($node->getId(), $scopedTaxonIds));
        $scopedTaxonIds = array_map(fn (TaxonNode $node) => $node->getId(), $scopedTaxa->all());
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

        return [
            $taxonTree,
            $productIds,
        ];
    }

    public function getActiveFilters(array $scopedTaxonIds, array $activeTaxonKeys): TaxonTree
    {
        /** @var TaxonTree $taxonTree */
        $taxonTree = $this->taxonTreeRepository->setLocale($this->locale)->getTree()
            ->findMany(fn (TaxonNode $node) => in_array($node->getId(), $scopedTaxonIds));

        /**
         * Subfiltering from current request
         *
         *  If any of the selected taxa belong to the same root as the scoped taxon, we filter down into the scoped taxon
         *  and therefore omit the scoped taxon as filter and use the selected nested taxa as the active filters.
         */
        if (count($activeTaxonKeys) > 0) {
            $selectedTaxa = $this->taxonTreeRepository->getTree()
                ->findMany(fn ($node) => in_array($node->getKey(), $activeTaxonKeys) && ! in_array($node->getId(), $scopedTaxonIds));

            foreach ($taxonTree->all() as $scopedTaxon) {
                foreach ($selectedTaxa as $selectedTaxon) {
                    if ($this->taxonHierarchy->isAncestorOf($scopedTaxon, $selectedTaxon)) {
                        $taxonTree = $taxonTree->removeNode($scopedTaxon);
                    }
                }
            }

            $taxonTree = $taxonTree->merge($selectedTaxa);
        }

        return $taxonTree;
    }

    /**
     * Get expanded filter ids from given taxon ids (including all children)
     */
    public function getFilterIds(array $taxonIds): array
    {
        $nodes = $this->taxonTreeRepository->setLocale($this->locale)->getTree()
            ->findMany(fn ($node) => in_array($node->getId(), $taxonIds));

        $expandedIds = [];

        foreach ($nodes as $node) {
            $expandedIds = array_merge($expandedIds, $this->taxonIds($this->taxonHierarchy->descendants($node)));
            $expandedIds[] = $node->getId();
        }

        return $expandedIds;
    }

    /**
     * Get expanded filter ids from given taxon keys (including all children)
     */
    public function getFilterIdsFromKeys(array $taxonKeys): array
    {
        $nodes = $this->taxonTreeRepository->setLocale($this->locale)->getTree()
            ->findMany(fn ($node) => in_array($node->getKey(), $taxonKeys));

        $expandedIds = [];

        foreach ($nodes as $node) {
            $expandedIds = array_merge($expandedIds, $this->taxonIds($this->taxonHierarchy->descendants($node)));
            $expandedIds[] = $node->getId();
        }

        return $expandedIds;
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
     *
     * forGrid: when true, fetch all grid related product ids.
     * This also matches variants in case of variant property taxonomies.
     */
    public function getProductIds(array $taxonIds, bool $forGrid = false): array
    {
        $nodes = $this->taxonTreeRepository->getTree()->findMany(fn (TaxonNode $node) => in_array($node->getId(), $taxonIds));

        $productIds = [];

        foreach ($nodes as $node) {
            foreach ($this->taxonHierarchy->descendants($node, true) as $descendant) {
                $productIds = array_merge($productIds, ($forGrid ? $descendant->getGridProductIds() : $descendant->getProductIds()));
            }
        }

        return array_values(array_unique($productIds));
    }

    public function setLocale(Locale $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param  list<TaxonNode>  $taxa
     * @return list<string>
     */
    private function taxonIds(array $taxa): array
    {
        return array_map(fn (TaxonNode $taxon): string => $taxon->getId(), $taxa);
    }
}
