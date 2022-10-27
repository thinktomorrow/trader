<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Product\Grid\FlattenedTaxonIdsComposer;
use Thinktomorrow\Trader\Application\Product\Grid\GridItem;
use Thinktomorrow\Trader\Application\Product\Grid\GridRepository;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\TraderConfig;

class MysqlGridRepository implements GridRepository
{
    private ContainerInterface $container;
    private FlattenedTaxonIdsComposer $flattenedTaxonIds;

    private int $perPage = 20;
    private Locale $locale;
    protected Builder $builder;

    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $taxonTable = 'trader_taxa';
    private static string $taxonPivotTable = 'trader_taxa_products';
    private TraderConfig $traderConfig;
    private ?int $limit;

    public function __construct(ContainerInterface $container, TraderConfig $traderConfig, FlattenedTaxonIdsComposer $flattenedTaxonIds)
    {
        $this->container = $container;
        $this->flattenedTaxonIds = $flattenedTaxonIds;
        $this->traderConfig = $traderConfig;
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
        $this->whereJsonLike($term);

        return $this;
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
        $this->builder->whereIn(static::$productTable.'.product_id', $product_ids);

        return $this;
    }

    public function filterByPrice(?string $minimumPriceAmount = null, ?string $maximumPriceAmount = null): static
    {
        if (! is_null($minimumPriceAmount)) {
            // Match input with expected vat inclusion
            $minimumPriceAmount = ($this->traderConfig->doesPriceInputIncludesVat() && ! $this->traderConfig->includeVatInPrices())
                ? Cash::from($minimumPriceAmount)->addPercentage(Percentage::fromString($this->traderConfig->getDefaultTaxRate()))->getAmount()
                : ((! $this->traderConfig->doesPriceInputIncludesVat() && $this->traderConfig->includeVatInPrices())
                    ? Cash::from($minimumPriceAmount)->subtractTaxPercentage(Percentage::fromString($this->traderConfig->getDefaultTaxRate()))->getAmount()
                    : $minimumPriceAmount);

            $this->builder->where(static::$variantTable . '.sale_price', '>=', $minimumPriceAmount);
        }

        if (! is_null($maximumPriceAmount)) {
            // Match input with expected vat inclusion
            $maximumPriceAmount = ($this->traderConfig->doesPriceInputIncludesVat() && ! $this->traderConfig->includeVatInPrices())
                ? Cash::from($maximumPriceAmount)->addPercentage(Percentage::fromString($this->traderConfig->getDefaultTaxRate()))->getAmount()
                : ((! $this->traderConfig->doesPriceInputIncludesVat() && $this->traderConfig->includeVatInPrices())
                    ? Cash::from($maximumPriceAmount)->subtractTaxPercentage(Percentage::fromString($this->traderConfig->getDefaultTaxRate()))->getAmount()
                    : $maximumPriceAmount);

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
        $this->builder->addSelect(
            DB::raw('LOWER(json_extract('.static::$productTable.'.data, "$.title.'.$this->locale->get().'")) AS product_title'),
            DB::raw('LOWER(json_extract('.static::$variantTable.'.data, "$.title.'.$this->locale->get().'")) AS variant_title')
        );

        $this->builder->orderBy('variant_title', $order);
        $this->builder->orderBy('product_title', $order);

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
        $this->builder->orderBy('sale_price', $order);

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

        $this->limit = $limit;

        return $this;
    }

    public function setLocale(Locale $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return GridItem[]
     */
    public function getResults(): LengthAwarePaginator
    {
        // Default ordering if no ordering has been applied yet.
        if (! $this->builder->orders || count($this->builder->orders) < 1) {
            $this->builder->orderBy(static::$productTable . '.order_column', 'ASC');
        }

        if (isset($this->limit) && $this->limit < $this->perPage) {
            $rows = $this->builder->get();
            $results = (new \Illuminate\Pagination\LengthAwarePaginator($rows, count($rows), $this->perPage))
                ->withQueryString();
        } else {
            // TODO: concat all gridProduct ids that match the filters
            $results = $this->builder->paginate($this->perPage)->withQueryString();
        }

        return $results->setCollection(
            $results->getCollection()
                ->map(fn ($state) => get_object_vars($state))
                ->map(fn ($state) => $this->container->get(GridItem::class)::fromMappedData(array_merge($state, [
                    'includes_vat' => (bool) $state['includes_vat'],
                ]), $this->locale))
                ->each(fn (GridItem $gridItem) => $gridItem->setLocale($this->locale))
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

    protected function whereJsonLike($value)
    {
        $value = trim(strtolower($value));

        $keys = [
            static::$productTable => ['title', 'content'],
            static::$variantTable => ['title'],
        ];

        $this->builder->where(function ($builder) use ($value, $keys) {
            foreach ([static::$productTable, static::$variantTable] as $table) {
                foreach ($keys[$table] as $key) {
                    $builder->orWhereRaw('LOWER(json_extract(`'.$table.'`.`data`, "$.'.$key.'")) LIKE ?', '%'. $value . '%');
                }
            }
        });
    }
}
