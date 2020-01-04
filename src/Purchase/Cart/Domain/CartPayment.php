<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Illuminate\Contracts\Support\Arrayable;
use Optiphar\Discounts\EligibleForDiscount;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;

/** An applied cart discount */
class CartPayment implements Arrayable, EligibleForDiscount
{
    use HasMagicAttributes, CartMethodDefaults, RendersMoney;
}
