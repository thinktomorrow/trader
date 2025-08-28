<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Category;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\TraderConfig;

class FindMainCategoryTaxonRoots
{
    use HasLocale;

    private TaxonTreeRepository $taxonTreeRepository;

    private TraderConfig $traderConfig;

    public function __construct(TraderConfig $traderConfig, TaxonTreeRepository $taxonTreeRepository)
    {
        $this->traderConfig = $traderConfig;
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    public function get(): ?TaxonTree
    {
        if (!$categoryTaxonomyId = $this->traderConfig->getMainCategoryTaxonomyId()) {
            return new TaxonTree();
        }

        return $this->taxonTreeRepository->setLocale($this->getLocale())->getTreeByTaxonomy($categoryTaxonomyId);
    }

    /** @return array<TaxonNode> */
    public function byTaxonIds(array $taxonIds): array
    {
        $categoryRootTaxa = $this->get();

        if (count($categoryRootTaxa) < 1) {
            return [];
        }

        $tree = $this->taxonTreeRepository->setLocale($this->getLocale())->getTree();

        $result = [];

        foreach ($taxonIds as $taxonId) {
            /** @var TaxonNode $taxonNode */
            $taxonNode = $tree->find(fn(TaxonNode $node) => $node->getId() == $taxonId);

            foreach ($categoryRootTaxa as $categoryRootTaxon) {
                if (in_array($categoryRootTaxon->getId(), [$taxonId, $taxonNode->getRootNode()->getId()])) {

                    if (!in_array($categoryRootTaxon->getId(), array_map(fn(TaxonNode $node) => $node->getId(), $result))) {
                        $result[] = $categoryRootTaxon;
                    }
                }
            }
        }

        return $result;
    }
}
