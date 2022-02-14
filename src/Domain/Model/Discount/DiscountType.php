<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Discount;

enum DiscountType: string
{
    case percentage_off = 'percentage_off';
    case fixed_amount = 'fixed_amount';
}
