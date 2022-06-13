<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;

class DefaultCartShippingAddress extends DefaultAddress implements CartShippingAddress
{
}
