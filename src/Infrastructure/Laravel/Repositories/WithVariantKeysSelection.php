<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKey;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKeyId;

trait WithVariantKeysSelection
{
    protected function composeVariantKeysSelect(): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "trader_product_keys.`key` || '::::' || trader_product_keys.locale";
        }

        return "CONCAT(
            trader_product_keys.`key`, '::::',trader_product_keys.locale
        )";
    }

    /**
     * @return VariantKey[]
     */
    protected function extractVariantKeys(array $state): array
    {
        if (empty($state['variant_keys'])) {
            return [];
        }

        $pairs = [];

        foreach (explode(',', $state['variant_keys']) as $pair) {
            [$key, $locale] = explode('::::', $pair);

            $pairs[] = VariantKey::create(VariantId::fromString($state['variant_id']), VariantKeyId::fromString($key), Locale::fromString($locale));
        }

        // Sort by locale
        usort($pairs, fn (VariantKey $a, VariantKey $b) => $a->getKey()->get() <=> $b->getKey()->get());

        return $pairs;
    }
}
