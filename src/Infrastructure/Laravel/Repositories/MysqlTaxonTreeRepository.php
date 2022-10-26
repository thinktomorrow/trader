<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Trader\Domain\Common\Locale;
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
    /** @var TaxonTree[] tree per locale */
    private array $trees = [];
    private Locale $locale;

    private static $taxonTable = 'trader_taxa';

    private ContainerInterface $container;

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
        $localeKey = $this->locale->toIso15897();

        if (isset($this->trees[$localeKey])) {
            return $this->trees[$localeKey];
        }

        $this->trees[$localeKey] = (new TaxonTree((new NodeCollectionFactory)->strict()->fromSource(
            new TaxonSource($this->getTaxonNodes())
        )->all()))
        ->eachRecursive(fn(TaxonNode $node) => $node->setLocale($this->locale));

        return $this->trees[$localeKey];
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
