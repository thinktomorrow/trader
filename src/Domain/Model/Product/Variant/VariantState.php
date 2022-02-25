<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Variant;

enum VariantState: string
{
    case available = 'available'; // product is available for purchase
    case unavailable = 'unavailable'; // product is not available for purchase
    case deleted = 'deleted'; // Product is / will be deleted

    public static function availableStates(): array
    {
        return [
            static::available,
        ];
    }
}
