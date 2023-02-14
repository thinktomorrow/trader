<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Taxon\Category\CategoryRepository;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNodes;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Infrastructure\Vine\TaxonSource;
use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Vine\NodeCollectionFactory;

final class InMemoryTaxonTreeRepository implements TaxonTreeRepository, CategoryRepository
{
    private ContainerInterface $container;
    private Locale $locale;

    public function __construct(ContainerInterface $container, TraderConfig $traderConfig)
    {
        $this->container = $container;

        $this->locale = $traderConfig->getDefaultLocale();
    }

    public function setLocale(Locale $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTree(): TaxonTree
    {
        return (new TaxonTree((new NodeCollectionFactory)->strict()->fromSource(
            new TaxonSource($this->getTaxonNodes())
        )->all()))->eachRecursive(fn ($node) => $node->setLocale($this->locale));
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
                'data' => json_encode($taxon->getData()),
                'state' => $taxon->getMappedData()['state'],
                'order' => $taxon->getMappedData()['order'],
                'product_ids' => $this->getCommaSeparatedProductIds($taxon->taxonId),
                'online_product_ids' => $this->getCommaSeparatedOnlineProductIds($taxon->taxonId),
                'keys' => json_encode(array_map(fn ($taxonKey) => $taxonKey->getMappedData(), $taxon->getTaxonKeys())),
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

    private function getCommaSeparatedOnlineProductIds(TaxonId $taxonId): string
    {
        if (! isset(InMemoryTaxonRepository::$onlineProductIds[$taxonId->get()])) {
            return '';
        }

        return implode(',', InMemoryTaxonRepository::$onlineProductIds[$taxonId->get()]);
    }
}
