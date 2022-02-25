<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\FindVariantForCart;

class MysqlFindVariantForCart implements FindVariantForCart
{
    private static string $variantTable = 'trader_product_variants';

    public function __construct()
    {

    }

    public function findVariantForCart(VariantId $variantId): VariantForCart
    {
        // Basic builder query
        $state = DB::table(static::$variantTable)
            ->where(static::$variantTable . '.variant_id', $variantId->get())
            ->select([
                'variant_id',
                'sale_price',
                'tax_rate',
                'includes_tax'
            ])
            ->first();

        if(!$state) {
            throw new \RuntimeException('No online variant found by id [' . $variantId->get(). ']');
        }

        $state = (array) $state;

        return VariantForCart::fromMappedData(array_merge($state, [
            'includes_tax' => (bool) $state['includes_tax'],
        ]));
    }
}
