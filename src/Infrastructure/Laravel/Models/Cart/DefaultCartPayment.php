<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadPayment;

class DefaultCartPayment extends OrderReadPayment implements CartPayment
{
}
