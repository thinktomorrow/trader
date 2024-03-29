<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Application\Common\TraderHelpers;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Domain\Model\Stock\Exceptions\CouldNotFindStockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\Exceptions\VariantRecordDoesNotExistWhenSavingStockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItemId;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItemRepository;

class MysqlVariantRepository implements VariantRepository, VariantForCartRepository, StockItemRepository
{
    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $optionTable = 'trader_product_options';
    private static string $optionValueTable = 'trader_product_option_values';
    private static string $variantOptionValueLookupTable = 'trader_variant_option_values';
    private static $productPersonalisationsTable = 'trader_product_personalisations';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function save(Variant $variant): void
    {
        $state = $variant->getMappedData();

        $option_value_ids = TraderHelpers::array_remove($state, 'option_value_ids');

        if (! $this->exists($variant->variantId)) {
            DB::table(static::$variantTable)->insert($state);
        } else {
            DB::table(static::$variantTable)->where('variant_id', $variant->variantId->get())->update($state);
        }

        $this->syncVariantOptionValueIds($variant->variantId, $option_value_ids);
    }

    private function exists(VariantId $variantId): bool
    {
        return DB::table(static::$variantTable)->where('variant_id', $variantId->get())->exists();
    }

    private function syncVariantOptionValueIds(VariantId $variantId, array $option_value_ids): void
    {
        $changedOptionValueIds = collect($option_value_ids);

        // Get all existing option_value ids
        $existingOptionValueIds = DB::table(static::$variantOptionValueLookupTable)
            ->where('variant_id', $variantId)
            ->select('option_value_id')
            ->get()
            ->pluck('option_value_id');

        // Remove the ones that are not in the new list
        $detachOptionValueIds = $existingOptionValueIds->diff($changedOptionValueIds);

        if ($detachOptionValueIds->count() > 0) {
            DB::table(static::$variantOptionValueLookupTable)
                ->where('variant_id', $variantId)
                ->whereIn('option_value_id', $detachOptionValueIds->all())
                ->delete();
        }

        // Insert the new option_value ids
        $attachOptionValueIds = $changedOptionValueIds->diff($existingOptionValueIds);

        if ($attachOptionValueIds->count() < 1) {
            return;
        }

        $insertData = $attachOptionValueIds->map(function ($option_value_id) use ($variantId) {
            return ['variant_id' => $variantId->get(), 'option_value_id' => $option_value_id];
        })->all();

        DB::table(static::$variantOptionValueLookupTable)->insert($insertData);
    }

    public function getStatesByProduct(ProductId $productId): array
    {
        $variantStates = DB::table(static::$variantTable)
            ->select([static::$variantTable . '.*', DB::raw('GROUP_CONCAT(`option_value_id`) AS option_value_ids')])
            ->where(static::$variantTable . '.product_id', $productId->get())
            ->leftJoin(static::$variantOptionValueLookupTable, static::$variantTable . '.variant_id', '=', static::$variantOptionValueLookupTable.'.variant_id')
            ->groupBy(static::$variantTable . '.variant_id')
            ->orderBy(static::$variantTable.'.order_column')
            ->get()
            ->map(fn ($item) => (array) $item)
            ->map(fn ($item) => array_merge($item, [
                'includes_vat' => (bool) $item['includes_vat'],
                'option_value_ids' => $item['option_value_ids'] ? explode(',', $item['option_value_ids']) : [],
            ]))
            ->toArray();

        // Handle a bug in laravel where raw group concat statement would return a record with falsy null values
        if (count($variantStates) == 1 && null === $variantStates[0]['variant_id']) {
            $variantStates = [];
        }

        return $variantStates;
    }

    public function delete(VariantId $variantId): void
    {
        DB::table(static::$variantOptionValueLookupTable)->where('variant_id', $variantId->get())->delete();
        DB::table(static::$variantTable)->where('variant_id', $variantId->get())->delete();
    }

    public function nextReference(): VariantId
    {
        return VariantId::fromString((string) Uuid::uuid4());
    }

    public function findVariantForCart(VariantId $variantId): VariantForCart
    {
        // Basic builder query
        $state = DB::table(static::$variantTable)
            ->join(static::$productTable, static::$variantTable . '.product_id', '=', static::$productTable . '.product_id')
            ->whereIn(static::$productTable . '.state', ProductState::onlineStates())
            ->where(static::$variantTable . '.variant_id', $variantId->get())
            ->select([
                static::$variantTable . '.*',
                static::$productTable . '.data AS product_data',
            ])
            ->first();

        if (! $state) {
            throw new \RuntimeException('No online/available variant found by id [' . $variantId->get(). ']');
        }

        $state = (array) $state;

        $personalisationStates = DB::table(static::$productPersonalisationsTable)
            ->where(static::$productPersonalisationsTable . '.product_id', $state['product_id'])
            ->get()
            ->map(fn ($item) => (array)$item);

        $personalisations = $personalisationStates->map(fn ($personalisationState) => Personalisation::fromMappedData($personalisationState, $state))->all();

        return $this->composeVariantForCart($state, $personalisations);
    }

    public function findAllVariantsForCart(array $variantIds): array
    {
        $states = DB::table(static::$variantTable)
            ->join(static::$productTable, static::$variantTable . '.product_id', '=', static::$productTable . '.product_id')
            ->whereIn(static::$productTable . '.state', ProductState::onlineStates())
            ->whereIn(static::$variantTable . '.variant_id', array_map(fn ($variantId) => $variantId->get(), $variantIds))
            ->select([
                static::$variantTable . '.*',
                static::$productTable . '.data AS product_data',
            ])
            ->get();

        $allPersonalisationStates = DB::table(static::$productPersonalisationsTable)
            ->where(static::$productPersonalisationsTable . '.product_id', $states->pluck('product_id')->unique()->toArray())
            ->get()
            ->map(fn ($item) => (array)$item);

        return $states
            ->map(fn ($state) => (array) $state)
            ->map(fn ($state) => $this->composeVariantForCart($state, $allPersonalisationStates->filter(fn ($personalisationState) => $personalisationState['product_id'] == $state['product_id'])->map(fn ($personalisationState) => Personalisation::fromMappedData($personalisationState, $state))->all()))->toArray();
    }

    private function composeVariantForCart(array $state, array $personalisations): VariantForCart
    {
        return $this->container->get(VariantForCart::class)::fromMappedData(array_merge($state, [
            'includes_vat' => (bool) $state['includes_vat'],
        ]), $personalisations);
    }

    public function findStockItem(StockItemId $stockItemId): StockItem
    {
        // Basic builder query
        $state = DB::table(static::$variantTable)
            ->where(static::$variantTable . '.variant_id', $stockItemId->get())
            ->select([
                static::$variantTable . '.*',
            ])
            ->first();

        if (! $state) {
            throw new CouldNotFindStockItem('No stockitem found by id [' . $stockItemId->get(). ']');
        }

        $state = (array) $state;

        return StockItem::fromMappedData(array_merge($state, [
            'stockitem_id' => $state['variant_id'],
        ]));
    }

    public function saveStockItem(StockItem $stockItem): void
    {
        $state = $stockItem->getMappedData();

        // StockItemId corresponds to the variant id. This is a given requirement.
        if (! $this->exists(VariantId::fromString($stockItem->stockItemId->get()))) {
            throw new VariantRecordDoesNotExistWhenSavingStockItem('No variant record found by id ' . $stockItem->stockItemId->get());
        }

        unset($state['stockitem_id']);

        DB::table(static::$variantTable)->where('variant_id', $stockItem->stockItemId->get())->update($state);
    }
}
