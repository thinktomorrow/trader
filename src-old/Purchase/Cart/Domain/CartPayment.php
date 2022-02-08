<?php declare(strict_types=1);

namespace Purchase\Cart\Domain;

use Common\Cash\RendersMoney;
use Purchase\Discounts\Domain\Discountable;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;

/** An applied cart discount */
class CartPayment implements Discountable
{
    use HasMagicAttributes, CartMethodDefaults, RendersMoney;
}
