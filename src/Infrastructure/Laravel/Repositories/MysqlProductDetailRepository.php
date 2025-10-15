<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class MysqlProductDetailRepository implements ProductDetailRepository
{
    use WithTaxonKeysSelection;

    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $taxonProductLookupTable = 'trader_taxa_products';
    private static string $taxonVariantLookupTable = 'trader_taxa_variants';
    private static string $taxonomyTable = 'trader_taxonomies';
    private static string $taxonTable = 'trader_taxa';
    private static $taxonKeysTable = 'trader_taxa_keys';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function findProductDetail(VariantId $variantId, bool $allowOffline = false): ProductDetail
    {
        // Basic builder query
        $builder = DB::table(static::$variantTable)
            ->join(static::$productTable, static::$variantTable . '.product_id', '=', static::$productTable . '.product_id')
            ->where(static::$variantTable . '.variant_id', $variantId->get())
            ->select([
                static::$variantTable . '.*',
                static::$productTable . '.data AS product_data',
            ])
            ->addSelect($this->container->get(ProductDetail::class)::stateSelect());

        if (! $allowOffline) {
            $builder->whereIn(static::$productTable . '.state', ProductState::onlineStates());
        }

        $state = $builder->first();

        if (! $state) {
            throw new CouldNotFindVariant('No online variant found by id [' . $variantId->get() . ']');
        }

        $state = (array)$state;

        return $this->container->get(ProductDetail::class)::fromMappedData(array_merge($state, [
            'includes_vat' => (bool)$state['includes_vat'],
        ]), $this->getTaxaItems($state['product_id'], $state['variant_id']));
    }

    private function getTaxaItems(string $product_id, string $variant_id): array
    {
        $productTaxaStates = DB::table(static::$taxonProductLookupTable)
            ->join(static::$taxonTable, static::$taxonProductLookupTable . '.taxon_id', '=', static::$taxonTable . '.taxon_id')
            ->join(static::$taxonomyTable, static::$taxonTable . '.taxonomy_id', '=', static::$taxonomyTable . '.taxonomy_id')
            ->leftJoin(static::$taxonKeysTable, static::$taxonTable . '.taxon_id', '=', static::$taxonKeysTable . '.taxon_id')
            ->where('product_id', $product_id)
            ->select([
                static::$taxonProductLookupTable . '.product_id AS product_id',
                static::$taxonProductLookupTable . '.taxon_id AS taxon_id',
                static::$taxonProductLookupTable . '.state AS state',
                static::$taxonProductLookupTable . '.data AS data',
                static::$taxonProductLookupTable . '.order_column AS order_column',
                static::$taxonTable . '.data AS taxon_data',
                static::$taxonTable . '.state AS taxon_state',
                static::$taxonomyTable . '.taxonomy_id AS taxonomy_id',
                static::$taxonomyTable . '.data AS taxonomy_data',
                static::$taxonomyTable . '.state AS taxonomy_state',
                static::$taxonomyTable . '.type AS taxonomy_type',
                static::$taxonomyTable . '.shows_in_grid AS shows_in_grid',
                DB::raw("GROUP_CONCAT({$this->composeTaxonKeysSelect()}) AS taxon_keys"),
            ])
            ->groupBy([
                static::$taxonProductLookupTable . '.product_id',
                static::$taxonProductLookupTable . '.taxon_id',
                static::$taxonProductLookupTable . '.state',
                static::$taxonProductLookupTable . '.data',
                static::$taxonProductLookupTable . '.order_column',
                static::$taxonTable . '.data',
                static::$taxonTable . '.state',
                static::$taxonomyTable . '.taxonomy_id',
                static::$taxonomyTable . '.data',
                static::$taxonomyTable . '.state',
                static::$taxonomyTable . '.type',
                static::$taxonomyTable . '.shows_in_grid',
            ])
            ->get();

        $variantTaxaStates = DB::table(static::$taxonVariantLookupTable)
            ->join(static::$taxonTable, static::$taxonVariantLookupTable . '.taxon_id', '=', static::$taxonTable . '.taxon_id')
            ->join(static::$taxonomyTable, static::$taxonTable . '.taxonomy_id', '=', static::$taxonomyTable . '.taxonomy_id')
            ->leftJoin(static::$taxonKeysTable, static::$taxonTable . '.taxon_id', '=', static::$taxonKeysTable . '.taxon_id')
            ->where('variant_id', $variant_id)
            ->select([
                static::$taxonVariantLookupTable . '.variant_id AS variant_id',
                static::$taxonVariantLookupTable . '.taxon_id AS taxon_id',
                static::$taxonVariantLookupTable . '.state AS state',
                static::$taxonVariantLookupTable . '.data AS data',
                static::$taxonVariantLookupTable . '.order_column AS order_column',
                static::$taxonTable . '.data AS taxon_data',
                static::$taxonTable . '.state AS taxon_state',
                static::$taxonomyTable . '.taxonomy_id AS taxonomy_id',
                static::$taxonomyTable . '.data AS taxonomy_data',
                static::$taxonomyTable . '.state AS taxonomy_state',
                static::$taxonomyTable . '.type AS taxonomy_type',
                static::$taxonomyTable . '.shows_in_grid AS shows_in_grid',
                DB::raw("GROUP_CONCAT(DISTINCT {$this->composeTaxonKeysSelect()}) AS taxon_keys"),
            ])
            ->groupBy([
                static::$taxonVariantLookupTable . '.variant_id',
                static::$taxonVariantLookupTable . '.taxon_id',
                static::$taxonVariantLookupTable . '.state',
                static::$taxonVariantLookupTable . '.data',
                static::$taxonVariantLookupTable . '.order_column',
                static::$taxonTable . '.data',
                static::$taxonTable . '.state',
                static::$taxonomyTable . '.taxonomy_id',
                static::$taxonomyTable . '.data',
                static::$taxonomyTable . '.state',
                static::$taxonomyTable . '.type',
                static::$taxonomyTable . '.shows_in_grid',
            ])
            ->get();

        return [
            ...array_map(fn ($state) => $this->container->get(ProductTaxonItem::class)::fromMappedData((array)$state, $this->extractTaxonKeys((array)$state)), $productTaxaStates->all()),
            ...array_map(fn ($state) => $this->container->get(VariantTaxonItem::class)::fromMappedData(array_merge((array)$state, ['product_id' => $product_id]), $this->extractTaxonKeys((array)$state)), $variantTaxaStates->all()),
        ];
    }
}
