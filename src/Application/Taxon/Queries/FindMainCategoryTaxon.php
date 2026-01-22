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

    public function __construct(TraderConfig $traderConfig, TaxonTreeRepository $taxonTreeRepository)
    {
        $this->traderConfig = $traderConfig;
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    public function get(): TaxonTree
    {
        if (! $categoryTaxonomyId = $this->traderConfig->getMainCategoryTaxonomyId()) {
            return new TaxonTree();
        }

        return $this->taxonTreeRepository->setLocale($this->getLocale())->getTreeByTaxonomy($categoryTaxonomyId);
    }

    /** @return null|TaxonNode */
    public function findFirstByTaxonIds(array $taxonIds): ?TaxonNode
    {
        $taxonTree = $this->get();

        foreach ($taxonTree->all() as $categoryRootTaxon) {

            $matchingTaxonIds = [$categoryRootTaxon->getNodeId(), ...$categoryRootTaxon->pluckChildNodes('id')];

            foreach ($taxonIds as $taxonId) {
                if (in_array($taxonId, $matchingTaxonIds)) {
                    return $taxonTree->find(fn ($node) => $node->getId() == $taxonId);
                }
            }
        }

        return null;
    }
}
