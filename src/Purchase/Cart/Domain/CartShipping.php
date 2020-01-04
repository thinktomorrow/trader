<?php

declare(strict_types=1);

namespace Optiphar\Cart;

use Illuminate\Contracts\Support\Arrayable;
use Optiphar\Deliveries\Delivery;
use Optiphar\Discounts\EligibleForDiscount;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;

/** An applied cart discount */
class CartShipping implements Arrayable, EligibleForDiscount
{
    use HasMagicAttributes, CartMethodDefaults, RendersMoney;

    public function requiresAddress(): bool
    {
        return ! in_array($this->method, [
            Delivery::PICKUP_PROVIDER,
            Delivery::DELIVERY_PICKUP_KEY,
            Delivery::DELIVERY_PICKUP_2_KEY,
            Delivery::DELIVERY_PICKUP_3_KEY,
            Delivery::DELIVERY_PICKUP_4_KEY,
        ]);
    }
}
