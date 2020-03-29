<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Thinktomorrow\Trader\Common\Cash\RendersMoney;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Discountable;

/** An applied cart discount */
class CartPayment implements Discountable
{
    use HasMagicAttributes, CartMethodDefaults, RendersMoney;
}
