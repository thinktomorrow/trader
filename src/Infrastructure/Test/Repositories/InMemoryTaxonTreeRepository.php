<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Taxon\Queries\CategoryRepository;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNodes;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\TraderConfig;

final class InMemoryTaxonTreeRepository implements TaxonTreeRepository, CategoryRepository, InMemoryRepository
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
        return TaxonTree::fromIterable($this->getTaxonNodes())
            ->sort('order')
            ->eachRecursive(fn ($node) => $node->setLocale($this->locale));
    }

    public function getTreeByTaxonomies(array $taxonomyIds): TaxonTree
    {
        $nodes = [];

        foreach ($taxonomyIds as $taxonomyId) {
            $nodes = array_merge($nodes, $this->getTaxonNodes($taxonomyId)->toArray());
        }

        return TaxonTree::fromIterable(TaxonNodes::fromType($nodes))
            ->sort('order')
            ->eachRecursive(fn ($node) => $node->setLocale($this->locale));
    }

    public function getTreeByTaxonomy(string $taxonomyId): TaxonTree
    {
        return TaxonTree::fromIterable($this->getTaxonNodes($taxonomyId))
            ->sort('order')
            ->eachRecursive(fn ($node) => $node->setLocale($this->locale));
    }

    public function findTaxonById(string $taxonId): TaxonNode
    {
        $taxonNode = $this->getTree()->find(fn (TaxonNode $taxonNode) => $taxonNode->getId() == $taxonId);

        if (! $taxonNode) {
            throw new \RuntimeException('No taxon record found by id ' . $taxonId);
        }

        return $taxonNode;
    }

    public function findTaxonByKey(string $key): TaxonNode
    {
        return $this->getTree()->find(fn (TaxonNode $taxonNode) => $taxonNode->getKey() == $key);
    }

    private function getTaxonNodes(?string $taxonomyId = null): TaxonNodes
    {
        $nodes = [];

        $taxonNodeClass = $this->container->get(TaxonNode::class);

        foreach (InMemoryTaxonRepository::$taxons as $taxon) {

            if ($taxonomyId && $taxon->taxonomyId->get() !== $taxonomyId) {
                continue;
            }
            $nodes[] = $taxonNodeClass::fromMappedData([
                'taxon_id' => $taxon->taxonId->get(),
                'taxonomy_id' => $taxon->taxonomyId->get(),
                'parent_id' => $taxon->getMappedData()['parent_id'],
                'data' => json_encode($taxon->getData()),
                'state' => $taxon->getMappedData()['state'],
                'order' => $taxon->getMappedData()['order'],
                'product_ids' => $this->getProductIds($taxon->taxonId),
                'grid_product_ids' => $this->getGridProductPairs($taxon->taxonId),
                'grid_variant_ids' => $this->getGridVariantPairs($taxon->taxonId),
            ], $taxon->getTaxonKeys());
        }

        return TaxonNodes::fromType($nodes);
    }

    private function getProductIds(TaxonId $taxonId): string
    {
        $productIds = InMemoryProductRepository::getProductsFromLookup($taxonId->get());

        return implode(',', $productIds);
    }

    private function getGridProductPairs(TaxonId $taxonId): string
    {
        $pairs = InMemoryProductRepository::getGridProductVariantPairsFromLookup($taxonId->get());

        return implode(',', array_map(fn ($pair) => implode(':', $pair), $pairs));
    }

    private function getGridVariantPairs(TaxonId $taxonId): string
    {
        $pairs = InMemoryVariantRepository::getGridProductVariantPairsFromLookup($taxonId->get());

        return implode(',', array_map(fn ($pair) => implode(':', $pair), $pairs));
    }
}
