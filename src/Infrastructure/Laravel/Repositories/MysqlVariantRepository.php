<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Stock\Exceptions\CouldNotFindStockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\Exceptions\VariantRecordDoesNotExistWhenSavingStockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItemId;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItemRepository;

class MysqlVariantRepository implements VariantRepository, VariantForCartRepository, StockItemRepository
{
    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $variantTaxaLookupTable = 'trader_taxa_variants';
    private static $productPersonalisationsTable = 'trader_product_personalisations';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function save(Variant $variant): void
    {
        $state = $variant->getMappedData();

        if (! $this->exists($variant->variantId)) {
            DB::table(static::$variantTable)->insert($state);
        } else {
            DB::table(static::$variantTable)->where('variant_id', $variant->variantId->get())->update($state);
        }

        $this->upsertVariantTaxa($variant);
    }

    private function exists(VariantId $variantId): bool
    {
        return DB::table(static::$variantTable)->where('variant_id', $variantId->get())->exists();
    }

    private function upsertVariantTaxa(Variant $variant): void
    {
        $taxonIds = array_map(fn ($taxonState) => $taxonState['taxon_id'], $variant->getChildEntities()[VariantTaxon::class]);

        DB::table(static::$variantTaxaLookupTable)
            ->where('variant_id', $variant->variantId->get())
            ->whereNotIn('taxon_id', $taxonIds)
            ->delete();

        foreach ($variant->getChildEntities()[VariantTaxon::class] as $i => $variantTaxonState) {
            DB::table(static::$variantTaxaLookupTable)
                ->updateOrInsert([
                    'variant_id' => $variant->variantId->get(),
                    'taxon_id' => $variantTaxonState['taxon_id'],
                ], [
                    'variant_id' => $variant->variantId->get(),
                    'taxon_id' => $variantTaxonState['taxon_id'],
                    'data' => $variantTaxonState['data'],
                    'state' => $variantTaxonState['state'],
                    'order_column' => $i,
                ]);
        }
    }

    public function getStatesByProduct(ProductId $productId): array
    {
        $taxaSelect = $this->composeTaxaSelect();

        $variantStates = DB::table(static::$variantTable)
            ->select([
                static::$variantTable . '.*',
                DB::raw("GROUP_CONCAT(DISTINCT $taxaSelect) AS taxa"),
            ])
            ->where(static::$variantTable . '.product_id', $productId->get())
            ->leftJoin(static::$variantTaxaLookupTable, static::$variantTable . '.variant_id', '=', static::$variantTaxaLookupTable . '.variant_id')
            ->leftJoin('trader_taxa', static::$variantTaxaLookupTable . '.taxon_id', '=', 'trader_taxa.taxon_id')
            ->leftJoin('trader_taxonomies', 'trader_taxa.taxonomy_id', '=', 'trader_taxonomies.taxonomy_id')
            ->groupBy(static::$variantTable . '.variant_id')
            ->orderBy(static::$variantTable . '.order_column')
            ->get()
            ->map(fn ($item) => (array)$item)
            ->map(fn ($item) => array_merge($item, [
                'includes_vat' => (bool)$item['includes_vat'],
            ]))
            ->map(function ($item) {
                $pairs = [];
                if (! empty($item['taxa'])) {
                    foreach (explode(',', $item['taxa']) as $pair) {
                        [$taxonomyId, $taxonomyType, $taxonId, $taxonState, $taxonData] = explode('::::', $pair);
                        $pairs[] = [
                            'variant_id' => $item['variant_id'],
                            'taxonomy_type' => $taxonomyType,
                            'taxonomy_id' => $taxonomyId,
                            'taxon_id' => $taxonId,
                            'state' => $taxonState,
                            'data' => $taxonData,
                        ];
                    }
                }

                return [$item, [VariantTaxon::class => $pairs]];
            })
            ->toArray();

        // Handle a bug in laravel where raw group concat statement would return a record with falsy null values
        //        if (count($variantStates) == 1 && null === $variantStates[0]['variant_id']) {
        //            $variantStates = [];
        //        }

        return $variantStates;
    }

    public function delete(VariantId $variantId): void
    {
        DB::table(static::$variantTaxaLookupTable)->where('variant_id', $variantId->get())->delete();
        DB::table(static::$variantTable)->where('variant_id', $variantId->get())->delete();
    }

    public function nextReference(): VariantId
    {
        return VariantId::fromString((string)Uuid::uuid4());
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
            throw new \RuntimeException('No online/available variant found by id [' . $variantId->get() . ']');
        }

        $state = (array)$state;

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
            ->map(fn ($state) => (array)$state)
            ->map(fn ($state) => $this->composeVariantForCart($state, $allPersonalisationStates->filter(fn ($personalisationState) => $personalisationState['product_id'] == $state['product_id'])->map(fn ($personalisationState) => Personalisation::fromMappedData($personalisationState, $state))->all()))->toArray();
    }

    private function composeVariantForCart(array $state, array $personalisations): VariantForCart
    {
        return $this->container->get(VariantForCart::class)::fromMappedData(array_merge($state, [
            'includes_vat' => (bool)$state['includes_vat'],
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
            throw new CouldNotFindStockItem('No stockitem found by id [' . $stockItemId->get() . ']');
        }

        $state = (array)$state;

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

    private function composeTaxaSelect(): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "trader_taxonomies.taxonomy_id || '::::' || trader_taxonomies.type || '::::' || trader_taxa.taxon_id || '::::' || trader_taxa_variants.state || '::::' || trader_taxa_variants.data";
        }

        return "CONCAT(
            trader_taxonomies.taxonomy_id, '::::',
            trader_taxonomies.type, '::::',
            trader_taxa.taxon_id, '::::',
            trader_taxa_variants.state, '::::',
            trader_taxa_variants.data
        )";
    }
}
