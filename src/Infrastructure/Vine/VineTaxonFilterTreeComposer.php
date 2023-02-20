<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;

class VineTaxonFilterTreeComposer implements TaxonFilterTreeComposer
{
    private TaxonTreeRepository $taxonTreeRepository;

    public function __construct(TaxonTreeRepository $taxonTreeRepository)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    public function getAvailableFilters(Locale $locale, string $mainTaxonFilterKey): TaxonTree
    {
        /** @var TaxonNode $mainTaxonNode */
        $mainTaxonNode = $this->taxonTreeRepository->setLocale($locale)->getTree()->find(fn (TaxonNode $node) => $node->getKey() == $mainTaxonFilterKey);

        if (! $mainTaxonNode) {
            return new TaxonTree();
        }

        $productIds = $this->getOnlineProductIds($mainTaxonNode->getId());

        /**
         * The products belonging to the main taxon determine which taxons will
         * be returned as filters. Here we shake out the taxon tree so there
         * are only taxa left that match one or more of the same products
         */
        $taxonTree = $this->taxonTreeRepository->getTree()
            ->shake(fn (TaxonNode $node) => count(array_intersect($node->getOnlineProductIds(), $productIds)) > 0)
            ->remove(fn (TaxonNode $node) => ! $node->showOnline());

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
    }

    public function getActiveFilters(Locale $locale, string $mainTaxonFilterKey, array $activeKeys): TaxonTree
    {
        $mainTaxonNode = $this->taxonTreeRepository->setLocale($locale)->getTree()->find(fn ($node) => $node->getKey() == $mainTaxonFilterKey);

        if (! $mainTaxonNode) {
            return new TaxonTree();
        }

        // The main category is always considered 'active' as a grid filter, with or without any other filtering active
        $taxonTree = new TaxonTree([$mainTaxonNode]);

        /** Used filters from current request */
        if (count($activeKeys) > 0) {
            $selectedTaxons = $this->taxonTreeRepository->getTree()
                ->findMany(fn ($node) => in_array($node->getKey(), $activeKeys))

                // Remove any parents where the child taxon is present in the payload.
                // We want to filter on the more specific child taxon - and not in combination with its parent.
                ->remove(function (TaxonNode $node) use ($activeKeys) {
                    return count(array_intersect($activeKeys, $node->pluckChildNodes('getKey'))) > 0;
                });

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

            if (count($selectedTaxons) > 0) {
                return $selectedTaxons;
            }
        }

        return $taxonTree;
    }

    /**
     * Get all online product ids belonging to this taxon filter and all its children
     *
     * @return array
     */
    public function getOnlineProductIds(string $taxonId): array
    {
        return $this->getProductIds($taxonId, true);
    }

    /**
     * Get all product ids belonging to this taxon filter and all its children
     *
     * @return array
     */
    public function getProductIds(string $taxonId, bool $onlineOnly = false): array
    {
        $node = $this->taxonTreeRepository->getTree()->find(fn (TaxonNode $node) => $node->getId() == $taxonId);

        if (! $node) {
            throw new \InvalidArgumentException('Cannot retrieve product ids from taxon. No Taxon found by id ' . $taxonId);
        }

        $productIds = $onlineOnly ? $node->getOnlineProductIds() : $node->getProductIds();

        $node->getChildNodes()->flatten()->each(function ($childNode) use (&$productIds, $onlineOnly) {
            $productIds = array_merge($productIds, ($onlineOnly ? $childNode->getOnlineProductIds() : $childNode->getProductIds()));
        });

        return array_values(array_unique($productIds));
    }
}
