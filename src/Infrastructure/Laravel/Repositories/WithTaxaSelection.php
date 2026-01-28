<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;

trait WithTaxaSelection
{
    use WithTaxonKeysSelection;

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
            ...array_map(fn($state) => $this->container->get(ProductTaxonItem::class)::fromMappedData((array)$state, $this->extractTaxonKeys((array)$state)), $productTaxaStates->all()),
            ...array_map(fn($state) => $this->container->get(VariantTaxonItem::class)::fromMappedData(array_merge((array)$state, ['product_id' => $product_id]), $this->extractTaxonKeys((array)$state)), $variantTaxaStates->all()),
        ];
    }
}
