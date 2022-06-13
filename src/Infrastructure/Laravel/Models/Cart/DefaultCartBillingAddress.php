<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;

class DefaultCartBillingAddress extends DefaultAddress implements CartBillingAddress
{
}
