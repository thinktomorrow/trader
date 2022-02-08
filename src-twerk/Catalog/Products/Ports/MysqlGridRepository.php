<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Thinktomorrow\Trader\Catalog\Products\Domain\ProductState;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Money\Money;
use Thinktomorrow\Trader\Catalog\Products\Domain\GridRepository;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupRepository;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\TaxonRepository;
use Thinktomorrow\Trader\Catalog\Taxa\Ports\TaxonModel;
use Thinktomorrow\Trader\Common\Domain\Context;

class MysqlGridRepository implements GridRepository
{
    private int $perPage = 20;

    protected Builder $builder;
    protected Context $context;
    private ProductGroupRepository $productGroupRepository;
    private TaxonRepository $taxonRepository;

    private static string $productGroupTable;
    protected static string $productTable;
    private static string $taxaTable;
    private static string $taxaPivotTable;

    public function __construct(ProductGroupRepository $productGroupRepository, TaxonRepository $taxonRepository, Context $context)
    {
        $this->productGroupRepository = $productGroupRepository;
        $this->taxonRepository = $taxonRepository;
        $this->context = $context;

        static::$productTable = (new ProductModel)->getTable();
        static::$productGroupTable = (new ProductGroupModel())->getTable();
        static::$taxaTable = (new TaxonModel())->getTable();
        static::$taxaPivotTable = 'trader_taxa_products';

        $this->builder = ProductGroupModel::query()->online()
            ->join(static::$productTable, static::$productGroupTable.'.id', '=', static::$productTable.'.productgroup_id')
            ->where(static::$productTable.'.is_grid_product', true)
            ->whereIn(static::$productTable.'.state', \Thinktomorrow\Trader\Catalog\Products\Ports\DefaultProductStateMachine::getAvailableStates()) // TODO: use statemachine contract instead
            ->groupBy(static::$productGroupTable.'.id')
            ->with(['products']); // Is this better???

        $this->builder->select([
            static::$productGroupTable.'.*',

            // TODO: add order by for the group_concat so the grid products are also sorted according to the expected order.
            // e.g. static::$productTable.'.id ORDER BY sale_price DESC'
            DB::raw($this->grammarGroupConcat(static::$productTable.'.id') . ' AS grid_product_ids'),
        ]);
    }

    public function all(): Collection
    {
        // Default ordering if non applied
        if (! $this->builder->getQuery()->orders || count($this->builder->getQuery()->orders) < 1) {
            $this->builder->orderBy(static::$productGroupTable.'.order_column', 'ASC');
        }

        return $this->builder->get()->map(fn ($model) => $this->productGroupRepository->composeProductGroup($model));
    }

    public function filterByTerm(string $term): GridRepository
    {
        $this->builder->where(function ($query) use ($term) {
            $query->whereRaw('LOWER(json_extract('.static::$productTable.'.data, "$.title.'.$this->context->getLocale()->getLanguage().'")) LIKE ?', '%'. trim(strtolower($term)) . '%');
            $query->orWhereRaw('LOWER(json_extract('.static::$productGroupTable.'.data, "$.title.'.$this->context->getLocale()->getLanguage().'")) LIKE ?', '%'. trim(strtolower($term)) . '%');
        });

        return $this;
    }

    public function filterByTaxa(array $taxa): GridRepository
    {
        return $this->filterByTaxonomy($taxa);
    }

    public function filterByTaxonIds(array $taxa): GridRepository
    {
        return $this->filterByTaxonomy($taxa, false);
    }

    /**
     * All taxa are grouped by their root. Taxa within the same root have an OR relation
     * in the search. Taxa of different roots will be searched as an AND operation
     *
     * @param array $taxa
     * @return GridRepository
     */
    private function filterByTaxonomy(array $taxa, bool $passedAsKeys = true): GridRepository
    {
        // Get all taxa including their grandchildren - remember that each taxon key
        // is unique across all the taxonomy entries so we can safely retrieve by key.
        $taxonIds = [];

        foreach ($taxa as $key) {
            if ($taxon = ($passedAsKeys ? $this->taxonRepository->findByKey($key) : $this->taxonRepository->findById($key))) {
                $rootId = ($taxon->getAncestorNodes()->isEmpty()) ? $taxon->getNodeId() : $taxon->getAncestorNodes()->first()->getNodeId();

                if (! isset($taxonIds[$rootId])) {
                    $taxonIds[$rootId] = [];
                }

                $taxonIds[$rootId] = array_merge($taxonIds[$rootId], $taxon->pluckChildNodes('id'));
            }
        }

        // inner join for each taxon root group so a AND relation can be setup for search within one query
        foreach ($taxonIds as $rootId => $ids) {
            $joinTable = 'join' . $rootId;
            $this->builder->join(static::$taxaPivotTable . ' AS ' . $joinTable, static::$productGroupTable.'.id', '=', $joinTable.'.productgroup_id')
                 ->whereIn($joinTable.'.taxon_id', array_unique($ids));
        }

        return $this;
    }

    public function filterByPrice(Money $minimumPrice = null, Money $maximumPrice = null): GridRepository
    {
        if ($minimumPrice) {
            $this->builder->where(static::$productTable . '.sale_price', '>=', $minimumPrice->getAmount());
        }

        if ($maximumPrice) {
            $this->builder->where(static::$productTable . '.sale_price', '<=', $maximumPrice->getAmount());
        }

        return $this;
    }

    public function filterByProductGroupIds(array $productGroupIds): GridRepository
    {
        $this->builder->whereIn(static::$productGroupTable.'.id', $productGroupIds);

        return $this;
    }

    public function sortByLabel(): GridRepository
    {
        return $this->addSortByLabel();
    }

    public function sortByLabelDesc(): GridRepository
    {
        return $this->addSortByLabel('DESC');
    }

    protected function addSortByLabel($order = 'ASC'): GridRepository
    {
        $labelField = 'LOWER(json_unquote(json_extract('.static::$productTable.'.data, "$.title.'.$this->context->getLocale()->getLanguage().'")))';

        $this->builder->addSelect(
            DB::raw($this->grammarGroupConcat($labelField . ' ORDER BY ' . $labelField . ' ' . $order) . ' AS labels'),
        );

        $this->builder->orderBy('labels', $order);

        return $this;
    }

    public function sortByPrice(): GridRepository
    {
        return $this->addSortByPrice();
    }

    public function sortByPriceDesc(): GridRepository
    {
        return $this->addSortByPrice('DESC');
    }

    private function addSortByPrice($order = 'ASC'): GridRepository
    {
        $this->builder->addSelect(
            $order == 'DESC'
                ? DB::raw('MAX('.static::$productTable.'.sale_price) AS sale_price_aggregate')
                : DB::raw('MIN('.static::$productTable.'.sale_price) AS sale_price_aggregate')
        );

        $this->builder->orderBy('sale_price_aggregate', $order);

        return $this;
    }

    public function paginate(int $perPage): GridRepository
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function limit(int $limit): GridRepository
    {
        $this->builder->limit($limit);

        return $this;
    }

    public function getResults(): LengthAwarePaginator
    {
        // Default ordering if non applied
        if (! $this->builder->getQuery()->orders || count($this->builder->getQuery()->orders) < 1) {
            $this->builder->orderBy(static::$productGroupTable.'.order_column', 'ASC');
        }

        // TODO: concat all gridProduct ids that match the filters
        $results = $this->builder->paginate($this->perPage)->withQueryString();

        return $results->setCollection(
            $results->getCollection()->map(fn ($model) => $this->productGroupRepository->composeProductGroup($model))
        );
    }

    /**
     * Group Concat grammar for mysql
     *
     * @param string $column
     * @param string $separator
     * @return \Illuminate\Database\Query\Expression
     */
    protected function grammarGroupConcat(string $column, string $separator = ','): Expression
    {
        return DB::raw('GROUP_CONCAT('.$column.' SEPARATOR "'.$separator.'")');
    }
}
