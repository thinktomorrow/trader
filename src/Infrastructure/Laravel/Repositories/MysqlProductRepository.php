<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;

class MysqlProductRepository implements ProductRepository
{
    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';

    public function save(Product $product): void
    {
        // Save product WITH
        // VARIANTS,
        // options
        // personalisations

        $state = $product->getMappedData();

        if (!$this->exists($product->productId)) {
            DB::table(static::$productTable)->insert($state);
        } else {
            DB::table(static::$productTable)->where('product_id', $product->productId)->update($state);
        }

        $this->upsertVariants($product);
    }

    private function upsertVariants(Product $product): void
    {
        $variantIds = array_map(fn($variantState) => $variantState['variant_id'], $product->getChildEntities()[Variant::class]);

        DB::table(static::$variantTable)
            ->where('product_id', $product->productId)
            ->whereNotIn('variant_id', $variantIds)
            ->delete();

        foreach ($product->getChildEntities()[Variant::class] as $variantState) {

            DB::table(static::$variantTable)
                ->updateOrInsert([
                    'product_id' => $product->productId->get(),
                    'variant_id'  => $variantState['variant_id'],
                ], $variantState);
        }
    }

    private function exists(ProductId $productId): bool
    {
        return DB::table(static::$productTable)->where('product_id', $productId->get())->exists();
    }

    public function find(ProductId $productId): Product
    {
        $productState = DB::table(static::$productTable)
            ->where(static::$productTable . '.product_id', $productId->get())
            ->first();

        if (!$productState) {
            throw new CouldNotFindProduct('No product found by id [' . $productId->get() . ']');
        }

        $variantStates = DB::table(static::$variantTable)
            ->where(static::$variantTable . '.product_id', $productId->get())
            ->get()
            ->map(fn($item) => (array) $item)
            ->map(fn($item) => array_merge($item, ['includes_vat' => (bool) $item['includes_vat']]))
            ->toArray();

        return Product::fromMappedData((array) $productState, [
            Variant::class => $variantStates,
        ]);
    }

    public function delete(ProductId $productId): void
    {
        DB::table(static::$variantTable)->where('product_id', $productId->get())->delete();
        DB::table(static::$productTable)->where('product_id', $productId->get())->delete();
    }

    public function nextReference(): ProductId
    {
        return ProductId::fromString((string) Uuid::uuid4());
    }
}
