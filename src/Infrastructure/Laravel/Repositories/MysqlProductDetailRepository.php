<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class MysqlProductDetailRepository implements ProductDetailRepository
{
    use WithTaxaSelection;

    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
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
        ]), $this->getTaxaItems($state['product_id'], $state['variant_id']), $personalisations);
    }
}
