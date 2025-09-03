<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;

class MysqlProductRepository implements ProductRepository
{
    private VariantRepository $variantRepository;

    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $productTaxonLookupTable = 'trader_taxa_products';
    private static string $personalisationTable = 'trader_product_personalisations';

    public function __construct(VariantRepository $variantRepository)
    {
        $this->variantRepository = $variantRepository;
    }

    public function save(Product $product): void
    {
        $state = $product->getMappedData();

        if (! $this->exists($product->productId)) {
            DB::table(static::$productTable)->insert($state);
        } else {
            DB::table(static::$productTable)->where('product_id', $product->productId->get())->update($state);
        }

        $this->upsertProductTaxa($product);
        $this->upsertVariants($product);
        $this->upsertPersonalisations($product);
    }

    private function upsertProductTaxa(Product $product): void
    {
        $taxonIds = array_map(fn ($taxonState) => $taxonState['taxon_id'], $product->getChildEntities()[ProductTaxon::class]);

        DB::table(static::$productTaxonLookupTable)
            ->where('product_id', $product->productId->get())
            ->whereNotIn('taxon_id', $taxonIds)
            ->delete();

        foreach ($product->getChildEntities()[ProductTaxon::class] as $i => $taxonState) {
            DB::table(static::$productTaxonLookupTable)
                ->updateOrInsert([
                    'product_id' => $product->productId->get(),
                    'taxon_id' => $taxonState['taxon_id'],
                ], [
                    'product_id' => $product->productId->get(),
                    'taxon_id' => $taxonState['taxon_id'],
                    'data' => $taxonState['data'],
                    'order_column' => $i,
                    'state' => $taxonState['state'],
                ]);
        }
    }

    private function upsertVariants(Product $product): void
    {
        $variant_ids = array_map(fn ($variant) => $variant->variantId->get(), $product->getVariants());

        DB::table(static::$variantTable)
            ->where('product_id', $product->productId)
            ->whereNotIn('variant_id', $variant_ids)
            ->delete();

        foreach ($product->getVariants() as $variant) {
            $this->variantRepository->save($variant);
        }
    }

    private function upsertPersonalisations(Product $product): void
    {
        $personalisation_ids = array_map(fn ($personalisationState) => $personalisationState['personalisation_id'], $product->getChildEntities()[Personalisation::class]);

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

    private function exists(ProductId $productId): bool
    {
        return DB::table(static::$productTable)->where('product_id', $productId->get())->exists();
    }

    public function find(ProductId $productId): Product
    {
        $taxaSelect = $this->composeTaxaSelect();

        $productState = DB::table(static::$productTable)
            ->select([
                static::$productTable . '.*',
                DB::raw("GROUP_CONCAT(DISTINCT $taxaSelect) AS taxa"),
            ])
            ->where(static::$productTable . '.product_id', $productId->get())
            ->leftJoin(static::$productTaxonLookupTable, static::$productTable . '.product_id', '=', static::$productTaxonLookupTable . '.product_id')
            ->leftJoin('trader_taxa', static::$productTaxonLookupTable . '.taxon_id', '=', 'trader_taxa.taxon_id')
            ->leftJoin('trader_taxonomies', 'trader_taxa.taxonomy_id', '=', 'trader_taxonomies.taxonomy_id')
            ->groupBy(static::$productTable . '.product_id')
            ->first();

        // Handle a bug in laravel where raw group concat statement would return a record with falsy null values
        if ($productState && null === $productState->product_id) {
            $productState = null;
        }

        if (! $productState) {
            throw new CouldNotFindProduct('No product found by id [' . $productId->get() . ']');
        }

        $productState = (array)$productState;
        $variantStates = $this->variantRepository->getStatesByProduct($productId);

        $personalisationStates = DB::table(static::$personalisationTable)
            ->where(static::$personalisationTable . '.product_id', $productId->get())
            ->orderBy(static::$personalisationTable . '.order_column')
            ->orderBy('order_column')
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();

        $productTaxa = $this->getProductTaxonStatesByProduct($productState);

        return Product::fromMappedData($productState, [
            Variant::class => $variantStates,
            ProductTaxon::class => $productTaxa,
            Personalisation::class => $personalisationStates,
        ]);
    }

    private function getProductTaxonStatesByProduct(array $state): array
    {
        if (empty($state['taxa'])) {
            return [];
        }

        $pairs = [];

        foreach (explode(',', $state['taxa']) as $pair) {
            [$taxonomyId, $taxonomyType, $taxonId, $taxonState, $taxonData] = explode('::::', $pair);
            $pairs[] = [
                'product_id' => $state['product_id'],
                'taxonomy_type' => $taxonomyType,
                'taxonomy_id' => $taxonomyId,
                'taxon_id' => $taxonId,
                'state' => $taxonState,
                'data' => $taxonData,
            ];
        }

        return $pairs;
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

    private function composeTaxaSelect(): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "trader_taxonomies.taxonomy_id || '::::' || trader_taxonomies.type || '::::' || trader_taxa.taxon_id || '::::' || trader_taxa_products.state || '::::' || trader_taxa_products.data";
        }

        return "CONCAT(
            trader_taxonomies.taxonomy_id, '::::',
            trader_taxonomies.type, '::::',
            trader_taxa.taxon_id, '::::',
            trader_taxa_products.state, '::::',
            trader_taxa_products.data
        )";
    }
}
