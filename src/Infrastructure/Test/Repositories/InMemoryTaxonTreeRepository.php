<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Taxon\Category\CategoryRepository;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNodes;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Infrastructure\Vine\TaxonSource;
use Thinktomorrow\Vine\NodeCollectionFactory;

final class InMemoryTaxonTreeRepository implements TaxonTreeRepository, CategoryRepository
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getTree(): TaxonTree
    {
        return new TaxonTree((new NodeCollectionFactory)->strict()->fromSource(
            new TaxonSource($this->getTaxonNodes())
        )->all());
    }

    public function findTaxonById(string $id): TaxonNode
    {
        return $this->getTree()->find(fn (TaxonNode $taxonNode) => $taxonNode->getId() == $id);
    }

    public function findTaxonByKey(string $key): TaxonNode
    {
        return $this->getTree()->find(fn (TaxonNode $taxonNode) => $taxonNode->getKey() == $key);
    }

    private function getTaxonNodes(): TaxonNodes
    {
        $nodes = [];

        $taxonNodeClass = $this->container->get(TaxonNode::class);

        foreach (InMemoryTaxonRepository::$taxons as $taxon) {
            $nodes[] = $taxonNodeClass::fromMappedData([
                'taxon_id' => $taxon->taxonId->get(),
                'parent_id' => $taxon->getMappedData()['parent_id'],
                'key' => $taxon->getMappedData()['key'],
                'data' => json_encode($taxon->getData()),
                'state' => $taxon->getMappedData()['state'],
                'order' => $taxon->getMappedData()['order'],
                'product_ids' => $this->getCommaSeparatedProductIds($taxon->taxonId),
            ]);
        }

        return TaxonNodes::fromType($nodes);
    }

    private function getCommaSeparatedProductIds(TaxonId $taxonId): string
    {
        if (! isset(InMemoryTaxonRepository::$productIds[$taxonId->get()])) {
            return '';
        }

        return implode(',', InMemoryTaxonRepository::$productIds[$taxonId->get()]);
    }
}
