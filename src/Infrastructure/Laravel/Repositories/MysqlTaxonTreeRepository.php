<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Taxon\Category\CategoryRepository;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNodes;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Infrastructure\Vine\TaxonSource;
use Thinktomorrow\Vine\NodeCollectionFactory;

class MysqlTaxonTreeRepository implements TaxonTreeRepository, CategoryRepository
{
    private ?TaxonTree $tree = null;

    private static $taxonTable = 'trader_taxa';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function findTaxonById(string $taxonId): TaxonNode
    {
        /** @var TaxonNode $taxonNode */
        $taxonNode = $this->getTree()->find(fn (TaxonNode $taxonNode) => $taxonNode->getId() == $taxonId);

        if (! $taxonNode) {
            throw new CouldNotFindTaxon('No taxon record found by id ' . $taxonId);
        }

        return $taxonNode;
    }

    public function findTaxonByKey(string $key): TaxonNode
    {
        /** @var TaxonNode $taxonNode */
        $taxonNode = $this->getTree()->find(fn (TaxonNode $taxonNode) => $taxonNode->getKey() == $key);

        if (! $taxonNode) {
            throw new CouldNotFindTaxon('No taxon record found by key ' . $key);
        }

        return $taxonNode;
    }

    public function getTree(): TaxonTree
    {
        if ($this->tree) {
            return $this->tree;
        }

        $this->tree = new TaxonTree((new NodeCollectionFactory)->strict()->fromSource(
            new TaxonSource($this->getTaxonNodes())
        )->all());

        return $this->tree;
    }

    private function getTaxonNodes(): TaxonNodes
    {
        $results = DB::table(static::$taxonTable)
            ->leftJoin('trader_taxa_products', 'trader_taxa.taxon_id', 'trader_taxa_products.taxon_id')
            ->select(static::$taxonTable .'.*', DB::raw('GROUP_CONCAT(product_id) AS product_ids'))
            ->groupBy(static::$taxonTable.'.taxon_id')
            ->orderBy(static::$taxonTable.'.order')
            ->get();

        $taxonNodeClass = $this->container->get(TaxonNode::class);

        return TaxonNodes::fromType(
            $results->map(fn ($row) => $taxonNodeClass::fromMappedData((array) $row))->all()
        );
    }
}
