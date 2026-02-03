<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class MysqlProductDetailRepository implements ProductDetailRepository
{
    use WithTaxaSelection;
    use WithVariantKeysSelection;

    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static $variantKeysTable = 'trader_product_keys';
    private static string $taxonProductLookupTable = 'trader_taxa_products';
    private static string $taxonVariantLookupTable = 'trader_taxa_variants';
    private static string $taxonomyTable = 'trader_taxonomies';
    private static string $taxonTable = 'trader_taxa';
    private static $taxonKeysTable = 'trader_taxa_keys';
    private static $productPersonalisationsTable = 'trader_product_personalisations';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function findProductDetailByKey(Locale $locale, string $variantKey, bool $allowOffline = false): ProductDetail
    {
        $variantId = DB::table('trader_product_keys')
            ->where('key', $variantKey)
            ->where('locale', $locale->get())
            ->value('variant_id');

        // If no custom key found, we assume the given key is actually the variant ID.
        if (!$variantId) {
            $variantId = $variantKey;
        }

        return $this->findProductDetail(VariantId::fromString($variantId), $allowOffline);
    }

    public function findProductDetail(VariantId $variantId, bool $allowOffline = false): ProductDetail
    {
        $variantKeysSelect = $this->composeVariantKeysSelect();

        // Basic builder query
        $builder = DB::table(static::$variantTable)
            ->join(static::$productTable, static::$variantTable . '.product_id', '=', static::$productTable . '.product_id')
            ->leftJoin(static::$variantKeysTable, static::$variantTable . '.variant_id', '=', static::$variantKeysTable . '.variant_id')
            ->where(static::$variantTable . '.variant_id', $variantId->get())
            ->select([
                static::$variantTable . '.*',
                static::$productTable . '.data AS product_data',
                DB::raw("GROUP_CONCAT(DISTINCT $variantKeysSelect) AS variant_keys"),
            ])
            ->addSelect($this->container->get(ProductDetail::class)::stateSelect())
            ->groupBy(static::$variantTable . '.variant_id');

        if (!$allowOffline) {
            $builder->whereIn(static::$productTable . '.state', ProductState::onlineStates());
        }

        $state = $builder->first();

        if (!$state) {
            throw new CouldNotFindVariant('No online variant found by id [' . $variantId->get() . ']');
        }

        $state = (array)$state;

        $personalisationStates = DB::table(static::$productPersonalisationsTable)
            ->where(static::$productPersonalisationsTable . '.product_id', $state['product_id'])
            ->get()
            ->map(fn($item) => (array)$item);

        $personalisations = $personalisationStates->map(fn($personalisationState) => Personalisation::fromMappedData($personalisationState, $state))->all();

        return $this->container->get(ProductDetail::class)::fromMappedData(array_merge($state, [
            'includes_vat' => (bool)$state['includes_vat'],
        ]), $this->getTaxaItems($state['product_id'], $state['variant_id']),
            $this->extractVariantKeys($state),
            $personalisations);
    }
}
