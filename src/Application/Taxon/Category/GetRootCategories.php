<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Category;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\TraderConfig;

class GetRootCategories
{
    use HasLocale;

    private TaxonTreeRepository $taxonTreeRepository;

    private TraderConfig $traderConfig;

    public function __construct(TraderConfig $traderConfig, TaxonTreeRepository $taxonTreeRepository)
    {
        $this->traderConfig = $traderConfig;
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    //    public function byTaxonIds(array $taxon_ids): ?TaxonNode
    //    {
    //        if (! $categoryRootTaxa = $this->getCategoryRootTaxa()) {
    //            return null;
    //        }
    //
    //        $tree = $this->taxonTreeRepository->setLocale($this->getLocale())->getTree();
    //
    //        foreach ($taxon_ids as $taxon_id) {
    //            /** @var TaxonNode $taxonNode */
    //            $taxonNode = $tree->find(fn (TaxonNode $node) => $node->getId() == $taxon_id);
    //
    //            if (in_array($categoryRootTaxa->getId(), [$taxon_id, $taxonNode->getRootNode()->getId()])) {
    //                return $taxonNode;
    //            }
    //        }
    //
    //        return null;
    //    }

    public function get(): ?TaxonTree
    {
        if (!$categoryTaxonomyId = $this->traderConfig->getMainCategoryTaxonomyId()) {
            return new TaxonTree();
        }

        return $this->taxonTreeRepository->setLocale($this->getLocale())->getTreeByTaxonomy($categoryTaxonomyId);
    }
}
