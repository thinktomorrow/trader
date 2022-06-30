<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\Address;

class DefaultCartBillingAddress extends Address implements CartBillingAddress
{
}
