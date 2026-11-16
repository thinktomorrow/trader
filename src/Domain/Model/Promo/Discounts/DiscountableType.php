<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

enum DiscountableType: string
{
    case line = 'line';
    case order = 'order';
    case shipping = 'shipping';
    case payment = 'payment';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
