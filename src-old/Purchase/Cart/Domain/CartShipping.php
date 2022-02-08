<?php declare(strict_types=1);

namespace Purchase\Cart\Domain;

use Common\Cash\RendersMoney;
use Purchase\Discounts\Domain\Discountable;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;

/** An applied cart discount */
class CartShipping implements Discountable
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
