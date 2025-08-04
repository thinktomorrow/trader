<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Application\Common\TraderHelpers;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\ProductVariantProperty;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class MysqlProductRepository implements ProductRepository
{
    private VariantRepository $variantRepository;

    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $productTaxonLookupTable = 'trader_taxa_products';

    private static string $optionTable = 'trader_product_options';
    private static string $optionValueTable = 'trader_product_option_values';
    private static string $personalisationTable = 'trader_product_personalisations';

    public function __construct(VariantRepository $variantRepository)
    {
        $this->variantRepository = $variantRepository;
    }

    public function save(Product $product): void
    {
        $state = $product->getMappedData();
        $taxon_ids = TraderHelpers::array_remove($state, 'taxon_ids');

        if (!$this->exists($product->productId)) {
            DB::table(static::$productTable)->insert($state);
        } else {
            DB::table(static::$productTable)->where('product_id', $product->productId->get())->update($state);
        }

        $this->upsertProductTaxa($product);
        $this->upsertVariants($product);
        $this->upsertPersonalisations($product);
    }

    private function upsertPersonalisations(Product $product): void
    {
        $personalisation_ids = array_map(fn($personalisationState) => $personalisationState['personalisation_id'], $product->getChildEntities()[Personalisation::class]);

        DB::table(static::$personalisationTable)
            ->where('product_id', $product->productId)
            ->whereNotIn('personalisation_id', $personalisation_ids)
            ->delete();

        foreach ($product->getChildEntities()[Personalisation::class] as $i => $personalisationState) {
            DB::table(static::$personalisationTable)
                ->updateOrInsert([
                    'product_id' => $product->productId->get(),
                    'personalisation_id' => $personalisationState['personalisation_id'],
                ], array_merge($personalisationState, ['order_column' => $i]));
        }
    }

    private function upsertVariants(Product $product): void
    {
        $variant_ids = array_map(fn($variant) => $variant->variantId->get(), $product->getVariants());

        DB::table(static::$variantTable)
            ->where('product_id', $product->productId)
            ->whereNotIn('variant_id', $variant_ids)
            ->delete();

        foreach ($product->getVariants() as $variant) {
            $this->variantRepository->save($variant);
        }
    }

    private function upsertProductTaxa(Product $product): void
    {
        $taxonIds = array_map(fn($taxonState) => $taxonState['taxon_id'], $product->getChildEntities()[ProductTaxon::class]);

        DB::table(static::$productTaxonLookupTable)
            ->where('product_id', $product->productId)
            ->whereNotIn('taxon_id', $taxonIds)
            ->delete();

        foreach ($product->getChildEntities()[ProductTaxon::class] as $i => $taxonState) {
            DB::table(static::$productTaxonLookupTable)
                ->updateOrInsert([
                    'product_id' => $product->productId->get(),
                    'taxonomy_id' => $taxonState['taxonomy_id'],
                    'taxon_id' => $taxonState['taxon_id'],
                ], array_merge($taxonState, ['order_column' => $i]));
        }

//
//
//
//
//
//
//        // TODO: allow to set custom labels for each product-taxon relation...
//        $changedTaxonIds = collect($taxon_ids);
//
//        // Get all existing taxon ids
//        $existingTaxonIds = DB::table(static::$productTaxonLookupTable)
//            ->where('product_id', $productId)
//            ->select('taxon_id')
//            ->get()
//            ->pluck('taxon_id');
//
//        // Remove the ones that are not in the new list
//        $detachTaxonIds = $existingTaxonIds->diff($changedTaxonIds);
//        if ($detachTaxonIds->count() > 0) {
//            DB::table(static::$productTaxonLookupTable)
//                ->where('product_id', $productId)
//                ->whereIn('taxon_id', $detachTaxonIds->all())
//                ->delete();
//        }
//
//        // Insert the new taxon ids
//        $attachTaxonIds = $changedTaxonIds->diff($existingTaxonIds);
//
//        $insertData = $attachTaxonIds->map(function ($taxon_id) use ($productId) {
//            return ['product_id' => $productId->get(), 'taxon_id' => $taxon_id, 'taxonomy_id' => ];
//        })->all();
//
//        DB::table(static::$productTaxonLookupTable)->insert($insertData);
    }

    private function exists(ProductId $productId): bool
    {
        return DB::table(static::$productTable)->where('product_id', $productId->get())->exists();
    }

    public function find(ProductId $productId): Product
    {
        $productState = DB::table(static::$productTable)
            ->select([
                static::$productTable . '.*',
            ])
            ->where(static::$productTable . '.product_id', $productId->get())
            ->first();

        // Handle a bug in laravel where raw group concat statement would return a record with falsy null values
        if ($productState && null === $productState->product_id) {
            $productState = null;
        }

        if (!$productState) {
            throw new CouldNotFindProduct('No product found by id [' . $productId->get() . ']');
        }

        $productState = (array)$productState;
        $variantStates = $this->variantRepository->getStatesByProduct($productId);

        $personalisationStates = DB::table(static::$personalisationTable)
            ->where(static::$personalisationTable . '.product_id', $productId->get())
            ->orderBy(static::$personalisationTable . '.order_column')
            ->orderBy('order_column')
            ->get()
            ->map(fn($item) => (array)$item)
            ->toArray();

        $productTaxa = $this->getTaxaStates($productId->get());
        $productVariantProperties = collect($productTaxa)
            ->filter(fn($taxonState) => $taxonState['taxonomy_type'] === TaxonomyType::variant_property->value)
            ->all();

        return Product::fromMappedData($productState, [
            Variant::class => $variantStates,
            ProductTaxon::class => $productTaxa,
            ProductVariantProperty::class => $productVariantProperties,
            Personalisation::class => $personalisationStates,
        ]);
    }

    private function getTaxaStates(string $productId): array
    {
        $taxa = DB::table(static::$productTaxonLookupTable)
            ->join('trader_taxa', 'trader_taxa.taxon_id', '=', static::$productTaxonLookupTable . '.taxon_id')
            ->join('trader_taxonomies', 'trader_taxonomies.taxonomy_id', '=', static::$productTaxonLookupTable . '.taxonomy_id')
            ->where(static::$productTaxonLookupTable . '.product_id', $productId)
            ->select([
                static::$productTaxonLookupTable . '.*',
                'trader_taxonomies.taxonomy_id',
                'trader_taxonomies.type as taxonomy_type',
                'trader_taxonomies.shows_in_grid',
                'trader_taxonomies.state as taxonomy_state',
                'trader_taxa.state',
                'trader_taxa.order',
                'trader_taxa.data',
            ])
            ->get()
            ->map(function ($item) {
                // Extend the ProductTaxon with other methods and properties to enhance usage.
                return [
                    'taxonomy_type' => $item->taxonomy_type,
                    'product_id' => $item->product_id,
                    'taxon_id' => $item->taxon_id,
                    'taxonomy_id' => $item->taxonomy_id,
                ];
            })
            ->all();

        return $taxa;
    }

    public function delete(ProductId $productId): void
    {
        DB::table(static::$variantTable)->where('product_id', $productId->get())->delete();
        DB::table(static::$productTable)->where('product_id', $productId->get())->delete();
    }

    public function nextReference(): ProductId
    {
        return ProductId::fromString((string)Uuid::uuid4());
    }
}
