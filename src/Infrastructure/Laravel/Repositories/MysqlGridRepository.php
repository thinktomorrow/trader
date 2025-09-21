<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Product\Grid\FlattenedTaxonIds;
use Thinktomorrow\Trader\Application\Product\Grid\GridItem;
use Thinktomorrow\Trader\Application\Product\Grid\GridRepository;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\TraderConfig;

class MysqlGridRepository implements GridRepository
{
    use WithTaxonKeysSelection;

    private ContainerInterface $container;
    private TraderConfig $traderConfig;
    private FlattenedTaxonIds $flattenedTaxonIds;

    protected Builder $builder;
    private Locale $locale;
    private int $perPage = 20;
    private ?int $limit;

    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $taxonTable = 'trader_taxa';
    private static string $taxonomyTable = 'trader_taxonomies';
    private static string $taxonPivotTable = 'trader_taxa_products';
    private static string $taxonVariantPivotTable = 'trader_taxa_variants';
    private static $taxonKeysTable = 'trader_taxa_keys';

    public function __construct(ContainerInterface $container, TraderConfig $traderConfig, FlattenedTaxonIds $flattenedTaxonIds)
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
            ->leftJoin(static::$taxonPivotTable, static::$variantTable . '.product_id', '=', static::$taxonPivotTable . '.product_id')
            ->leftJoin(static::$taxonVariantPivotTable, static::$variantTable . '.variant_id', '=', static::$taxonVariantPivotTable . '.variant_id')
            ->groupBy(static::$variantTable . '.variant_id', 'product_data', 'product_order_column', 'variant_order_column')
            ->select([
                static::$variantTable . '.*',
                static::$productTable . '.data AS product_data',
                static::$variantTable . '.order_column AS variant_order_column',
                static::$productTable . '.order_column AS product_order_column',
                DB::raw('GROUP_CONCAT(' . static::$taxonPivotTable . '.taxon_id) AS product_taxon_ids'),
                DB::raw('GROUP_CONCAT(' . static::$taxonVariantPivotTable . '.taxon_id) AS variant_taxon_ids'),
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
        $taxonIds = $this->flattenedTaxonIds->getGroupedByTaxonomyByKeys($taxonKeys);

        return $this->filterByTaxonIds($taxonIds, true);
    }

    public function filterByTaxonIds(array $taxon_ids, bool $already_grouped = false): static
    {
        $taxonIdsGroupedByTaxonomy = $already_grouped ? $taxon_ids : $this->flattenedTaxonIds->getGroupedByTaxonomyByIds($taxon_ids);

        foreach ($taxonIdsGroupedByTaxonomy as $ids) {
            $this->builder->whereIn(static::$taxonPivotTable . '.taxon_id', array_unique($ids));
        }

        //        $this->builder->where(function ($query) use ($taxonIdsGroupedByTaxonomy) {
        //            foreach ($taxonIdsGroupedByTaxonomy as $ids) {
        //                $query->whereIn(static::$taxonPivotTable . '.taxon_id', array_unique($ids));
        //            }
        //        });

        return $this;
    }

    public function filterByVariantTaxonIds(array $taxon_ids, bool $already_grouped = false): static
    {
        $taxonIdsGroupedByTaxonomy = $already_grouped ? $taxon_ids : $this->flattenedTaxonIds->getGroupedByTaxonomyByIds($taxon_ids);

        foreach ($taxonIdsGroupedByTaxonomy as $ids) {
            $this->builder->whereIn(static::$taxonVariantPivotTable . '.taxon_id', array_unique($ids));
        }

        return $this;
    }

    public function filterByProductIds(array $product_ids): static
    {
        $this->builder->whereIn(static::$productTable . '.product_id', $product_ids);

        return $this;
    }

    public function filterByPrice(?string $minimumPriceAmount = null, ?string $maximumPriceAmount = null): static
    {
        // Fallback vat percentage is fine because we only want to filter on price, not calculate it.
        $fallbackVatPercentage = VatPercentage::fromString($this->traderConfig->getFallBackStandardVatRate())->toPercentage();

        if (! is_null($minimumPriceAmount)) {
            // Match input with expected vat inclusion
            $minimumPriceAmount = ($this->traderConfig->doesPriceInputIncludesVat() && ! $this->traderConfig->includeVatInPrices())
                ? Cash::from($minimumPriceAmount)->addPercentage($fallbackVatPercentage)->getAmount()
                : ((! $this->traderConfig->doesPriceInputIncludesVat() && $this->traderConfig->includeVatInPrices())
                    ? Cash::from($minimumPriceAmount)->subtractTaxPercentage($fallbackVatPercentage)->getAmount()
                    : $minimumPriceAmount);

            $this->builder->where(static::$variantTable . '.sale_price', '>=', $minimumPriceAmount);
        }

        if (! is_null($maximumPriceAmount)) {
            // Match input with expected vat inclusion
            $maximumPriceAmount = ($this->traderConfig->doesPriceInputIncludesVat() && ! $this->traderConfig->includeVatInPrices())
                ? Cash::from($maximumPriceAmount)->addPercentage($fallbackVatPercentage)->getAmount()
                : ((! $this->traderConfig->doesPriceInputIncludesVat() && $this->traderConfig->includeVatInPrices())
                    ? Cash::from($maximumPriceAmount)->subtractTaxPercentage($fallbackVatPercentage)->getAmount()
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
            DB::raw('LOWER(json_extract(' . static::$productTable . '.data, "$.title.' . $this->locale->get() . '")) AS product_title'),
            DB::raw('LOWER(json_extract(' . static::$variantTable . '.data, "$.title.' . $this->locale->get() . '")) AS variant_title')
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

    /** @return array[product_id,variant_id] */
    public function getResultingIds(): array
    {
        $this->addDefaultSorting();

        $result = $this->builder
            ->clone()
            ->select([
                static::$productTable . '.product_id',
                static::$variantTable . '.variant_id',
                static::$productTable . '.data AS product_data',
                static::$productTable . '.order_column AS product_order_column',
                static::$variantTable . '.order_column AS variant_order_column',

            ])
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'variant_id' => $row->variant_id,
            ])->all();

        return $result;
    }

    /**
     * In case you already know the total count of results,
     * you can pass it here to optimize the pagination.
     *
     * @return LengthAwarePaginator<GridItem>
     */
    public function getResults(?int $total = null): LengthAwarePaginator
    {
        $this->addDefaultSorting();

        if (isset($this->limit) && $this->limit < $this->perPage) {
            $rows = $this->builder->get();
            $results = (new \Illuminate\Pagination\LengthAwarePaginator($rows, count($rows), $this->perPage))
                ->withQueryString();
        } else {
            $results = $this->builder->paginate($this->perPage, ['*'], 'page', null, $total)->withQueryString();
        }

        [$productTaxa, $variantTaxa] = $this->getTaxaInBulk($results);

        return $results->setCollection(
            $results->getCollection()
                ->map(fn ($state) => get_object_vars($state))
                ->map(function (array $state) use ($productTaxa, $variantTaxa) {

                    // Create taxon items for corresponding product and variant taxa
                    $productTaxonItems = $productTaxa
                        ->filter(fn ($taxonState) => $taxonState->product_id == $state['product_id'])
                        ->map(fn ($taxonState) => $this->container->get(ProductTaxonItem::class)::fromMappedData((array)$taxonState, $this->extractTaxonKeys((array)$taxonState)));

                    $variantTaxonItems = $variantTaxa
                        ->filter(fn ($taxonState) => $taxonState->variant_id == $state['variant_id'])
                        ->map(fn ($taxonState) => $this->container->get(VariantTaxonItem::class)::fromMappedData(array_merge((array)$taxonState, ['product_id' => $state['product_id']]), $this->extractTaxonKeys((array)$taxonState)));

                    return $this->container->get(GridItem::class)::fromMappedData(array_merge($state, [
                        'includes_vat' => (bool)$state['includes_vat'],
                    ]), [...$productTaxonItems, ...$variantTaxonItems]);
                })
                ->each(fn (GridItem $gridItem) => $gridItem->setLocale($this->locale))
        );
    }

    protected function addDefaultSorting(): void
    {
        // Default ordering if no ordering has been applied yet.
        if (! $this->builder->orders || count($this->builder->orders) < 1) {
            $this->builder->orderBy(static::$productTable . '.order_column', 'ASC');
        }
    }

    protected function getTaxaInBulk(Paginator $results): array
    {
        $productIds = $results->getCollection()->pluck('product_id')->all();
        $variantIds = $results->getCollection()->pluck('variant_id')->all();

        // Get all product and variant taxon ids that are in the result set
        $productTaxonIds = [];
        $variantTaxonIds = [];

        foreach ($results->getCollection() as $state) {
            $productTaxonIds = array_merge($productTaxonIds, explode(',', $state->product_taxon_ids ?? ''));
            $variantTaxonIds = array_merge($variantTaxonIds, explode(',', $state->variant_taxon_ids ?? ''));
        }

        // Remove empty values and duplicates
        $productTaxonIds = array_values(array_filter(array_unique($productTaxonIds)));
        $variantTaxonIds = array_values(array_filter(array_unique($variantTaxonIds)));

        $productTaxaStates = DB::table(static::$taxonPivotTable)
            ->join(static::$taxonTable, static::$taxonPivotTable . '.taxon_id', '=', static::$taxonTable . '.taxon_id')
            ->join(static::$taxonomyTable, static::$taxonTable . '.taxonomy_id', '=', static::$taxonomyTable . '.taxonomy_id')
            ->leftJoin(static::$taxonKeysTable, static::$taxonTable . '.taxon_id', '=', static::$taxonKeysTable . '.taxon_id')
            ->select([
                static::$taxonPivotTable . '.product_id AS product_id',
                static::$taxonPivotTable . '.taxon_id AS taxon_id',
                static::$taxonPivotTable . '.state AS state',
                static::$taxonPivotTable . '.data AS data',
                static::$taxonPivotTable . '.order_column AS order_column',
                static::$taxonTable . '.data AS taxon_data',
                static::$taxonTable . '.state AS taxon_state',
                static::$taxonomyTable . '.taxonomy_id AS taxonomy_id',
                static::$taxonomyTable . '.data AS taxonomy_data',
                static::$taxonomyTable . '.state AS taxonomy_state',
                static::$taxonomyTable . '.type AS taxonomy_type',
                static::$taxonomyTable . '.shows_in_grid AS shows_in_grid',
                DB::raw("GROUP_CONCAT(DISTINCT {$this->composeTaxonKeysSelect()}) AS taxon_keys"),
            ])
            ->whereIn(static::$taxonPivotTable . '.product_id', $productIds)
            ->whereIn(static::$taxonPivotTable . '.taxon_id', $productTaxonIds)
            ->whereIn(static::$taxonTable . '.state', array_map(fn (TaxonState $state) => $state->value, TaxonState::onlineStates()))
            ->groupBy(
                static::$taxonPivotTable . '.product_id',
                static::$taxonPivotTable . '.taxon_id',
                static::$taxonPivotTable . '.state',
                static::$taxonPivotTable . '.data',
                static::$taxonPivotTable . '.order_column',
                static::$taxonTable . '.data',
                static::$taxonTable . '.state',
                static::$taxonomyTable . '.taxonomy_id',
                static::$taxonomyTable . '.data',
                static::$taxonomyTable . '.state',
                static::$taxonomyTable . '.type',
                static::$taxonomyTable . '.shows_in_grid',
            )
            ->get();

        $variantTaxaStates = DB::table(static::$taxonVariantPivotTable)
            ->join(static::$taxonTable, static::$taxonVariantPivotTable . '.taxon_id', '=', static::$taxonTable . '.taxon_id')
            ->join(static::$taxonomyTable, static::$taxonTable . '.taxonomy_id', '=', static::$taxonomyTable . '.taxonomy_id')
            ->leftJoin(static::$taxonKeysTable, static::$taxonTable . '.taxon_id', '=', static::$taxonKeysTable . '.taxon_id')
            ->select([
                static::$taxonVariantPivotTable . '.variant_id AS variant_id',
                static::$taxonVariantPivotTable . '.taxon_id AS taxon_id',
                static::$taxonVariantPivotTable . '.state AS state',
                static::$taxonVariantPivotTable . '.data AS data',
                static::$taxonVariantPivotTable . '.order_column AS order_column',
                static::$taxonTable . '.data AS taxon_data',
                static::$taxonTable . '.state AS taxon_state',
                static::$taxonomyTable . '.taxonomy_id AS taxonomy_id',
                static::$taxonomyTable . '.data AS taxonomy_data',
                static::$taxonomyTable . '.state AS taxonomy_state',
                static::$taxonomyTable . '.type AS taxonomy_type',
                static::$taxonomyTable . '.shows_in_grid AS shows_in_grid',
                DB::raw("GROUP_CONCAT({$this->composeTaxonKeysSelect()}) AS taxon_keys"),
            ])
            ->whereIn(static::$taxonVariantPivotTable . '.variant_id', $variantIds)
            ->whereIn(static::$taxonVariantPivotTable . '.taxon_id', $variantTaxonIds)
            ->whereIn(static::$taxonTable . '.state', array_map(fn (TaxonState $state) => $state->value, TaxonState::onlineStates()))
            ->groupBy([
                static::$taxonVariantPivotTable . '.variant_id',
                static::$taxonVariantPivotTable . '.taxon_id',
                static::$taxonVariantPivotTable . '.state',
                static::$taxonVariantPivotTable . '.data',
                static::$taxonVariantPivotTable . '.order_column',
                static::$taxonTable . '.data',
                static::$taxonTable . '.state',
                static::$taxonomyTable . '.taxonomy_id',
                static::$taxonomyTable . '.data',
                static::$taxonomyTable . '.state',
                static::$taxonomyTable . '.type',
                static::$taxonomyTable . '.shows_in_grid',
            ])
            ->get();

        return [$productTaxaStates, $variantTaxaStates];
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
                    $builder->orWhereRaw('LOWER(json_extract(`' . $table . '`.`data`, "$.' . $key . '")) LIKE ?', '%' . $value . '%');
                }
            }
        });
    }
}
