<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

enum DiscountableType: string
{
    case order = 'order';
    case line = 'line';
    case shipping = 'shipping';
    case payment = 'payment';
}
