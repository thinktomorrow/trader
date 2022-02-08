<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Order\Domain\Order;

interface Condition
{
    public function check(Order $order, Discountable $discountable): bool;

//    public function toArray(): array;
}
