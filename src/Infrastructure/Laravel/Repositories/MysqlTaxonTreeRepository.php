<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Taxon\Queries\CategoryRepository;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNodes;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\TraderConfig;

class MysqlTaxonTreeRepository implements TaxonTreeRepository, CategoryRepository
{
    use WithTaxonKeysSelection;

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
        return $this->composeTree();
    }

    public function getTreeByTaxonomy(string $taxonomyId): TaxonTree
    {
        return $this->composeTree([$taxonomyId]);
    }

    public function getTreeByTaxonomies(array $taxonomyIds): TaxonTree
    {
        return $this->composeTree($taxonomyIds);
    }

    private function composeTree(?array $taxonomyIds = null): TaxonTree
    {
        $memoizeKey = $this->locale->get() . ($taxonomyIds ? '_' . implode('_', $taxonomyIds) : '');

        if (isset($this->trees[$memoizeKey])) {
            return $this->trees[$memoizeKey];
        }

        $this->trees[$memoizeKey] = TaxonTree::fromIterable($this->getTaxonNodes($taxonomyIds))
            ->sort('order')
            ->eachRecursive(fn (TaxonNode $node) => $node->setLocale($this->locale));

        return $this->trees[$memoizeKey];
    }

    private function getTaxonNodes(?array $taxonomyIds = null): TaxonNodes
    {
        //        $results = DB::table(static::$taxonTable)
        //            ->when($taxonomyIds, function ($query) use ($taxonomyIds) {
        //                return $query->whereIn(static::$taxonTable . '.taxonomy_id', (array)$taxonomyIds);
        //            })
        //            ->leftJoin(static::$taxonKeysTable, static::$taxonTable . '.taxon_id', '=', static::$taxonKeysTable . '.taxon_id')
        //            ->leftJoin('trader_taxa_products', 'trader_taxa.taxon_id', 'trader_taxa_products.taxon_id')
        //            ->leftJoin('trader_taxa_variants', 'trader_taxa.taxon_id', 'trader_taxa_variants.taxon_id')
        //            ->leftJoin('trader_products', function ($join) {
        //                $join->on('trader_taxa_products.product_id', '=', 'trader_products.product_id')
        //                    ->whereIn('trader_products.state', ProductState::onlineStates());
        //            })
        //            ->select(static::$taxonTable . '.*')
        //            ->addSelect(DB::raw("GROUP_CONCAT( DISTINCT {$this->composeTaxonKeysSelect()}) AS taxon_keys"))
        //            ->addSelect(DB::raw('GROUP_CONCAT(DISTINCT trader_taxa_products.product_id) AS product_ids'))
        //            ->addSelect(DB::raw('GROUP_CONCAT(DISTINCT trader_products.product_id) AS online_product_ids'))
        //            ->addSelect(DB::raw('GROUP_CONCAT(DISTINCT trader_taxa_variants.variant_id) AS online_variant_ids'))
        //            ->groupBy(static::$taxonTable . '.taxon_id')
        //            ->orderBy(static::$taxonTable . '.order')
        //            ->get();

        $results = DB::table(static::$taxonTable)
            ->when($taxonomyIds, function ($query) use ($taxonomyIds) {
                return $query->whereIn(static::$taxonTable . '.taxonomy_id', (array)$taxonomyIds);
            })
            ->leftJoin(static::$taxonKeysTable, static::$taxonTable . '.taxon_id', '=', static::$taxonKeysTable . '.taxon_id')
            ->select(static::$taxonTable . '.*')
            ->addSelect(DB::raw("GROUP_CONCAT(DISTINCT {$this->composeTaxonKeysSelect()}) AS taxon_keys"))
            ->addSelect(DB::raw('(
                SELECT GROUP_CONCAT(DISTINCT CONCAT_WS(":", p.product_id, v.variant_id))
                FROM trader_products p
                JOIN trader_product_variants v ON p.product_id = v.product_id
                JOIN trader_taxa_products tp ON tp.product_id = p.product_id
                JOIN trader_taxa t ON t.taxon_id = tp.taxon_id
                JOIN trader_taxonomies tax ON t.taxonomy_id = tax.taxonomy_id
                WHERE t.taxon_id = trader_taxa.taxon_id
                  AND p.state IN (' . implode(',', array_map(fn ($s) => DB::getPdo()->quote($s->value), ProductState::onlineStates())) . ')
                    AND tax.type <> ' . DB::getPdo()->quote(TaxonomyType::variant_property->value) . '
                    AND v.show_in_grid = 1
            ) AS grid_product_ids'))
            ->addSelect(DB::raw('(
                SELECT GROUP_CONCAT(DISTINCT CONCAT_WS(":", v.product_id, v.variant_id))
                FROM trader_product_variants v
                JOIN trader_products p ON v.product_id = p.product_id
                JOIN trader_taxa_variants tv ON tv.variant_id = v.variant_id
                JOIN trader_taxa t ON t.taxon_id = tv.taxon_id
                JOIN trader_taxonomies tax ON t.taxonomy_id = tax.taxonomy_id
                WHERE t.taxon_id = trader_taxa.taxon_id
                  AND p.state IN (' . implode(',', array_map(fn ($s) => DB::getPdo()->quote($s->value), ProductState::onlineStates())) . ')
                    AND v.show_in_grid = 1
            ) AS grid_variant_ids'))
            ->addSelect(DB::raw('(
                SELECT GROUP_CONCAT(DISTINCT tp.product_id)
                FROM trader_taxa_products tp
                JOIN trader_taxa t ON tp.taxon_id = t.taxon_id
                JOIN trader_taxonomies tax ON t.taxonomy_id = tax.taxonomy_id
                WHERE tp.taxon_id = trader_taxa.taxon_id
                  AND tax.type <> ' . DB::getPdo()->quote(TaxonomyType::variant_property->value) . '
            ) AS product_ids'))
//            ->addSelect(DB::raw('(
//                SELECT GROUP_CONCAT(DISTINCT p.product_id)
//                FROM trader_taxa_products tp
//                JOIN trader_products p ON tp.product_id = p.product_id
//                JOIN trader_taxa t ON tp.taxon_id = t.taxon_id
//                JOIN trader_taxonomies tax ON t.taxonomy_id = tax.taxonomy_id
//                WHERE tp.taxon_id = trader_taxa.taxon_id
//                  AND p.state IN (' . implode(',', array_map(fn($state) => DB::getPdo()->quote($state->value), ProductState::onlineStates())) . ')
//                  AND tax.type <> ' . DB::getPdo()->quote(TaxonomyType::variant_property->value) . '
//            ) AS online_product_ids'))
//            ->addSelect(DB::raw('(
//                SELECT GROUP_CONCAT(DISTINCT v.variant_id)
//                FROM trader_taxa_variants tv
//                JOIN trader_product_variants v ON tv.variant_id = v.variant_id
//                JOIN trader_products p ON v.product_id = p.product_id
//                JOIN trader_taxa t ON tv.taxon_id = t.taxon_id
//                JOIN trader_taxonomies tax ON t.taxonomy_id = tax.taxonomy_id
//                WHERE tv.taxon_id = trader_taxa.taxon_id
//                  AND p.state IN (' . implode(',', array_map(fn($state) => DB::getPdo()->quote($state->value), ProductState::onlineStates())) . ')
//                  AND tax.type = ' . DB::getPdo()->quote(TaxonomyType::variant_property->value) . '
//            ) AS online_variant_ids'))
            ->groupBy(static::$taxonTable . '.taxon_id')
            ->orderBy(static::$taxonTable . '.order')
            ->get();

        $taxonNodeClass = $this->container->get(TaxonNode::class);

        return TaxonNodes::fromType(
            $results->map(fn ($row) => $taxonNodeClass::fromMappedData((array)$row, $this->extractTaxonKeys((array)$row)))->all()
        );
    }
}
