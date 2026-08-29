<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Queries;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\TraderConfig;

class FindMainCategoryTaxon
{
    use HasLocale;

    private TaxonTreeRepository $taxonTreeRepository;

    private TraderConfig $traderConfig;

    private ?TaxonHierarchy $taxonHierarchy;

    public function __construct(TraderConfig $traderConfig, TaxonTreeRepository $taxonTreeRepository, ?TaxonHierarchy $taxonHierarchy = null)
    {
        $this->traderConfig = $traderConfig;
        $this->taxonTreeRepository = $taxonTreeRepository;
        $this->taxonHierarchy = $taxonHierarchy;
    }

    public function get(): TaxonTree
    {
        if (! $categoryTaxonomyId = $this->traderConfig->getMainCategoryTaxonomyId()) {
            return new TaxonTree;
        }

        return $this->taxonTreeRepository->setLocale($this->getLocale())->getTreeByTaxonomy($categoryTaxonomyId);
    }

    public function findFirstByTaxonIds(array $taxonIds): ?TaxonNode
    {
        $taxonTree = $this->get();

        foreach ($taxonTree->all() as $categoryRootTaxon) {

            $matchingTaxonIds = $this->taxonHierarchy
                ? array_map(
                    fn (TaxonNode $taxon): string => $taxon->getId(),
                    $this->taxonHierarchy->descendants($categoryRootTaxon, true)
                )
                : [$categoryRootTaxon->getNodeId(), ...$categoryRootTaxon->pluckChildNodes('id')];

            foreach ($taxonIds as $taxonId) {
                if (in_array($taxonId, $matchingTaxonIds)) {
                    return $taxonTree->find(fn ($node) => $node->getId() == $taxonId);
                }
            }
        }

        return null;
    }
}
