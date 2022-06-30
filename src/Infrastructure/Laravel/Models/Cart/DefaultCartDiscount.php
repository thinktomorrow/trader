<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadDiscount;

class DefaultCartDiscount extends OrderReadDiscount implements CartDiscount
{
}
