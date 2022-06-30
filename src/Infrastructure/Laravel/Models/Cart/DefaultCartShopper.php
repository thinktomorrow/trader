<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadShopper;

class DefaultCartShopper extends OrderReadShopper implements CartShopper
{
}
