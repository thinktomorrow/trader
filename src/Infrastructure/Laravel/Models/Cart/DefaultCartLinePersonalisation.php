<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartLinePersonalisation;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadLinePersonalisation;

class DefaultCartLinePersonalisation extends OrderReadLinePersonalisation implements CartLinePersonalisation
{
}
