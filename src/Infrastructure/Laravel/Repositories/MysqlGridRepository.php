<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Thinktomorrow\Trader\TraderConfig;
use Illuminate\Database\Query\Expression;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Application\Product\Grid\GridItem;
use Thinktomorrow\Trader\Application\Product\Grid\GridRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Application\Product\Grid\FlattenedTaxonIdsComposer;

class MysqlGridRepository implements GridRepository
{
    protected Builder $builder;
    private FlattenedTaxonIdsComposer $flattenedTaxonIds;

    private int $perPage = 20;
    private Locale $locale;

    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $taxonTable = 'trader_taxa';
    private static string $taxonPivotTable = 'trader_taxa_products';

    public function __construct(TraderConfig $traderConfig, FlattenedTaxonIdsComposer $flattenedTaxonIds)
    {
        $this->traderConfig = $traderConfig;
        $this->flattenedTaxonIds = $flattenedTaxonIds;
        $this->locale = $traderConfig->getDefaultLocale();

        // Basic builder query
        $this->builder = DB::table(static::$variantTable)
            ->join(static::$productTable, static::$variantTable . '.product_id', '=', static::$productTable . '.product_id')
            ->where(static::$variantTable . '.show_in_grid', true)
            ->whereIn(static::$productTable . '.state', ProductState::onlineStates())
            ->whereIn(static::$variantTable . '.state', VariantState::availableStates())
            ->select([
                static::$variantTable . '.*',
                static::$productTable . '.data AS product_data',
                static::$productTable . '.order_column AS product_order_column',
            ]);
    }

    public function filterByTerm(string $term): static
    {
        // TODO: Implement filterByTerm() method.
    }

    public function filterByTaxonKeys(array $taxonKeys): static
    {
        /**
         * All taxa are grouped by their root. Taxa within the same root have an OR relation
         * in the search. Taxa of different roots will be searched as an AND operation
         */
        $taxonIds = $this->flattenedTaxonIds->getGroupedByRootByKeys($taxonKeys);

        return $this->filterByTaxonIds($taxonIds, true);
    }

    public function filterByTaxonIds(array $taxon_ids, bool $already_grouped = false): static
    {
        $taxonIds = $already_grouped ? $taxon_ids : $this->flattenedTaxonIds->getGroupedByRootByIds($taxon_ids);

        foreach ($taxonIds as $rootId => $ids) {
            $joinTable = 'join' . $rootId;
            $this->builder->join(static::$taxonPivotTable . ' AS ' . $joinTable, static::$productTable . '.product_id', '=', $joinTable . '.product_id')
                ->whereIn($joinTable . '.taxon_id', array_unique($ids));
        }

        return $this;
    }

    public function filterByProductIds(array $product_ids): static
    {
        $this->builder->whereIn(static::$productTable.'.id', $product_ids);

        return $this;
    }

    public function filterByPrice(?string $minimumPriceAmount = null, ?string $maximumPriceAmount = null): static
    {
        if (!is_null($minimumPriceAmount)) {
            $this->builder->where(static::$variantTable . '.sale_price', '>=', $minimumPriceAmount);
        }

        if (!is_null($maximumPriceAmount)) {
            $this->builder->where(static::$variantTable . '.sale_price', '<=', $maximumPriceAmount);
        }

        return $this;
    }

    public function sortByLabel(): static
    {
        return $this->addSortByLabel();
    }

    public function sortByLabelDesc(): static
    {
        return $this->addSortByLabel('DESC');
    }

    protected function addSortByLabel($order = 'ASC'): static
    {
        $labelField = 'LOWER(json_unquote(json_extract('.static::$variantTable.'.data, "$.title.'.$this->locale->getLanguage().'")))';

        $this->builder->addSelect(
            DB::raw($this->grammarGroupConcat($labelField . ' ORDER BY ' . $labelField . ' ' . $order) . ' AS labels'),
        );

        $this->builder->orderBy('labels', $order);

        return $this;
    }

    public function sortByPrice(): static
    {
        return $this->addSortByPrice();
    }

    public function sortByPriceDesc(): static
    {
        return $this->addSortByPrice('DESC');
    }

    private function addSortByPrice($order = 'ASC'): static
    {
        $this->builder->addSelect(
            $order == 'DESC'
                ? DB::raw('MAX('.static::$variantTable.'.sale_price) AS sale_price_aggregate')
                : DB::raw('MIN('.static::$variantTable.'.sale_price) AS sale_price_aggregate')
        );

        $this->builder->orderBy('sale_price_aggregate', $order);

        return $this;
    }

    public function paginate(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->builder->limit($limit);

        return $this;
    }

    public function setLocale(Locale $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getResults(): LengthAwarePaginator
    {
        // Default ordering if no ordering has been applied yet.
        if (!$this->builder->orders || count($this->builder->orders) < 1) {
            $this->builder->orderBy(static::$productTable . '.order_column', 'ASC');
        }

        // TODO: concat all gridProduct ids that match the filters
        $results = $this->builder->paginate($this->perPage)->withQueryString();

        return $results->setCollection(
            $results->getCollection()
                ->map(fn ($state) => get_object_vars($state))
                ->map(fn ($state) => GridItem::fromMappedData(array_merge($state, [
                    'includes_vat' => (bool) $state['includes_vat'],
                ]), $this->locale))
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
        return DB::raw('GROUP_CONCAT(' . $column . ' SEPARATOR "' . $separator . '")');
    }
}
