<?php

namespace Purchase\Discounts\Domain\Conditions;

use Optiphar\Discounts\Condition;
use Optiphar\Promos\Common\Domain\Rules\Code;
use Optiphar\Promos\Common\Domain\Rules\Rule;
use Optiphar\Promos\Common\Domain\Rules\UniqueRedeemer;
use Optiphar\Promos\Common\Domain\Rules\ProductWhitelist;
use Optiphar\Promos\Common\Domain\Rules\MaximumRedemption;
use Optiphar\Promos\Common\Services\HandlesKeyToClassMapping;

class ConditionKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        'coupon'                   => Coupon::class,
        'period'                   => Period::class,
        'minimum_amount'           => MinimumAmount::class,
        'minimum_items'            => MinimumItems::class,
        'item_whitelist'           => ItemWhitelist::class,
        'item_blacklist'           => ItemBlacklist::class,
        'brand_whitelist'          => BrandWhitelist::class,
        'category_whitelist'       => CategoryWhitelist::class,
        'maximum_customer_applies' => MaximumCustomerApplies::class,
        'maximum_applies'          => MaximumApplies::class,
    ];

    public static function fromCondition(Condition $condition)
    {
        return static::fromInstance($condition);
    }

    /**
     * Derive the typekey from the older rule object
     *
     * @param Rule $rule
     * @return ConditionKey
     */
    public static function fromRule(Rule $rule): ConditionKey
    {
        switch(get_class($rule)){
            case Code::class:
                return static::fromString('coupon');
                break;
            case \Optiphar\Promos\Common\Domain\Rules\MinimumAmount::class:
                return static::fromString('minimum_amount');
                break;
            case \Optiphar\Promos\Common\Domain\Rules\Period::class:
                return static::fromString('period');
                break;
            case \Optiphar\Promos\Common\Domain\Rules\MinimumItems::class:
                return static::fromString('minimum_items');
                break;
            case ProductWhitelist::class:
                return static::fromString('item_whitelist');
                break;
            case MaximumRedemption::class:
                return static::fromString('maximum_applies');
                break;
            case UniqueRedeemer::class:
                return static::fromString('maximum_customer_applies');
                break;
        }

        throw new \InvalidArgumentException('No condition key found for discount rule [' .get_class($rule). ']');
    }
}
