<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Thinktomorrow\Trader\Domain\Model\Promo\Discount;

final class SalePriceSystemDiscount extends BaseDiscount implements Discount
{
    public static function getMapKey(): string
    {
        return 'sale_price';
    }

    public function getMappedData(): array
    {
        return array_merge(parent::getMappedData(), [
            //
        ]);
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        return parent::fromMappedData($state, $aggregateState, $childEntities);
    }
}
