<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Category;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\TraderConfig;

class FindCategoryTaxon
{
    private TaxonTreeRepository $taxonTreeRepository;
    private TraderConfig $traderConfig;

    public function __construct(TraderConfig $traderConfig, TaxonTreeRepository $taxonTreeRepository)
    {
        $this->traderConfig = $traderConfig;
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    public function byTaxonIds(array $taxon_ids): ?TaxonNode
    {
        if (! $categoryRoot = $this->getCategoryRoot()) {
            return null;
        }

        $tree = $this->taxonTreeRepository->getTree();

        foreach ($taxon_ids as $taxon_id) {
            /** @var TaxonNode $taxonNode */
            $taxonNode = $tree->find(fn (TaxonNode $node) => $node->getId() == $taxon_id);

            if (in_array($categoryRoot->getId(), [$taxon_id, $taxonNode->getRootNode()->getId()])) {
                return $taxonNode;
            }
        }

        return null;
    }

    public function getCategoryRoot(): ?TaxonNode
    {
        $tree = $this->taxonTreeRepository->getTree();

        if (! $categoryRootId = $this->traderConfig->getCategoryRootId()) {
            if (! $categoryRootId = $tree->first()?->getId()) {
                return null;
            }
        }

        /** @return TaxonNode */
        return $tree->find(fn (TaxonNode $node) => $node->getId() == $categoryRootId);
    }
}
