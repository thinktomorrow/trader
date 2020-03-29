<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Thinktomorrow\Trader\Common\Cash\RendersMoney;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Discountable;

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
