<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Vine\NodeCollectionFactory;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Infrastructure\Vine\TaxonSource;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNodes;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Application\Taxon\Category\CategoryRepository;

final class InMemoryTaxonTreeRepository implements TaxonTreeRepository, CategoryRepository
{
    public function getTree(): TaxonTree
    {
        return new TaxonTree((new NodeCollectionFactory)->strict()->fromSource(
            new TaxonSource($this->getTaxonNodes())
        )->all());
    }

    public function findTaxonByKey(string $key): TaxonNode
    {
        return $this->getTree()->find(fn (TaxonNode $taxonNode) => $taxonNode->getKey() == $key);
    }

    private function getTaxonNodes(): TaxonNodes
    {
        $nodes = [];

        foreach(InMemoryTaxonRepository::$taxons as $taxon) {
            $nodes[] = TaxonNode::fromMappedData([
                'taxon_id'    => $taxon->taxonId->get(),
                'parent_id'   => $taxon->getMappedData()['parent_id'],
                'key'         => $taxon->getMappedData()['key'],
                'data' => json_encode($taxon->getData()),
                'state'       => $taxon->getMappedData()['state'],
                'order'       => $taxon->getMappedData()['order'],
                'product_ids' => $this->getCommaSeparatedProductIds($taxon->taxonId),
            ]);
        }

        return TaxonNodes::fromType($nodes);
    }

    private function getCommaSeparatedProductIds(TaxonId $taxonId): string
    {
        if (!isset(InMemoryTaxonRepository::$productIds[$taxonId->get()])) {
            return '';
        }

        return implode(',', InMemoryTaxonRepository::$productIds[$taxonId->get()]);
    }
}
