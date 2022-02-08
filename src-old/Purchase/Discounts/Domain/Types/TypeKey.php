<?php

namespace Purchase\Discounts\Domain\Types;

use Optiphar\Discounts\Discount;
use Optiphar\Promos\Common\Domain\Discount\PromoDiscount;
use Optiphar\Promos\Common\Domain\Discount\DiscountAmount;
use Optiphar\Promos\Common\Services\HandlesKeyToClassMapping;
use Optiphar\Promos\Common\Domain\Discount\DiscountPercentage;
use Optiphar\Promos\Common\Domain\Discount\FreeProductDiscount;
use Optiphar\Promos\Common\Domain\Discount\ExtraProductDiscountPercentage;
use Optiphar\Promos\Common\Domain\Discount\CheapestProductDiscountPercentage;

class TypeKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        'percentage'                  => PercentageOffDiscount::class,
        'product_percentage'          => ProductPercentageOffDiscount::class,
        'cheapest_product_percentage' => CheapestProductPercentageOffDiscount::class,
        'fixed_amount'                => FixedAmountOffDiscount::class,
        'free_items'                  => FreeProductsDiscount::class,
    ];

    public static function fromDiscount(Discount $discount): TypeKey
    {
        return static::fromInstance($discount);
    }

    /**
     * Derive the typekey from the promo discount which is only used in admin / database layer.
     *
     * @param PromoDiscount $promoDiscount
     * @return TypeKey
     */
    public static function fromPromoDiscount(PromoDiscount $promoDiscount): TypeKey
    {
        switch (get_class($promoDiscount)) {
            case DiscountPercentage::class:
                return static::fromString('percentage');
                break;
            case ExtraProductDiscountPercentage::class:
                return static::fromString('product_percentage');
                break;
            case CheapestProductDiscountPercentage::class:
                return static::fromString('cheapest_product_percentage');
                break;
            case DiscountAmount::class:
                return static::fromString('fixed_amount');
                break;
            case FreeProductDiscount::class:
                return static::fromString('free_items');
                break;
        }

        throw new \InvalidArgumentException('No typekey found for promo discount [' . get_class($promoDiscount) . ']');
    }
}
