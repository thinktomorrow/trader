<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductDetail;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;

class MysqlProductDetailRepository implements ProductDetailRepository
{
    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $taxonLookupTable = 'trader_taxa_products';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function findProductDetail(VariantId $variantId): DefaultProductDetail
    {
        // Basic builder query
        $state = DB::table(static::$variantTable)
            ->join(static::$productTable, static::$variantTable . '.product_id', '=', static::$productTable . '.product_id')
            ->leftJoin(static::$taxonLookupTable, static::$productTable.'.product_id', static::$taxonLookupTable.'.product_id')
            ->whereIn(static::$productTable . '.state', ProductState::onlineStates())
            ->where(static::$variantTable . '.variant_id', $variantId->get())
            ->groupBy(static::$variantTable.'.variant_id')
            ->select([
                static::$variantTable . '.*',
                static::$productTable . '.data AS product_data',
                DB::raw('GROUP_CONCAT(taxon_id) AS taxon_ids')
            ])
        ->first();

        if(!$state) {
            throw new CouldNotFindVariant('No online variant found by id [' . $variantId->get(). ']');
        }

        $state = (array) $state;

        return $this->container->get(ProductDetail::class)::fromMappedData(array_merge($state, [
            'includes_vat' => (bool) $state['includes_vat'],
            'taxon_ids' => $state['taxon_ids'] ? explode(',',$state['taxon_ids']) : []
        ]));
    }
}
