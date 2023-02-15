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
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Infrastructure\Vine\TaxonSource;
use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Vine\NodeCollectionFactory;

class MysqlTaxonTreeRepository implements TaxonTreeRepository, CategoryRepository
{
    /** @var TaxonTree[] tree per locale */
    private array $trees = [];
    private Locale $locale;

    private static $taxonTable = 'trader_taxa';
    private static $taxonKeysTable = 'trader_taxa_keys';

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

    /**
     * This searches the taxon by the localized key. Keep in mind that this only finds the taxon
     * if the key is present for the current set locale as getKey() returns the localized key.
     *
     * @param string $key
     * @return TaxonNode
     */
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
        $localeKey = $this->locale->get();

        if (isset($this->trees[$localeKey])) {
            return $this->trees[$localeKey];
        }

        $this->trees[$localeKey] = (new TaxonTree((new NodeCollectionFactory)->strict()->fromSource(
            new TaxonSource($this->getTaxonNodes())
        )->all()))
        ->eachRecursive(fn (TaxonNode $node) => $node->setLocale($this->locale));

        return $this->trees[$localeKey];
    }

    private function getTaxonNodes(): TaxonNodes
    {
        $taxonKeyResults = DB::table(static::$taxonKeysTable)->get();

        $results = DB::table(static::$taxonTable)
            ->leftJoin('trader_taxa_products', 'trader_taxa.taxon_id', 'trader_taxa_products.taxon_id')
            ->leftJoin('trader_products', function ($join) {
                $join->on('trader_taxa_products.product_id', '=', 'trader_products.product_id')
                    ->whereIn('trader_products.state', ProductState::onlineStates());
            })
            ->select(static::$taxonTable . '.*')
            ->addSelect(DB::raw('GROUP_CONCAT(trader_taxa_products.product_id) AS product_ids'))
            ->addSelect(DB::raw('GROUP_CONCAT(trader_products.product_id) AS online_product_ids'))
            ->groupBy(static::$taxonTable.'.taxon_id')
            ->orderBy(static::$taxonTable.'.order')
            ->get()
            ->map(function ($item) use ($taxonKeyResults) {
                $keys = $taxonKeyResults->filter(fn ($taxonKeyResult) => $taxonKeyResult->taxon_id == $item->taxon_id);
                $item->keys = $keys->values()->toJson();

                return $item;
            });

        $taxonNodeClass = $this->container->get(TaxonNode::class);

        return TaxonNodes::fromType(
            $results->map(fn ($row) => $taxonNodeClass::fromMappedData((array) $row))->all()
        );
    }
}
