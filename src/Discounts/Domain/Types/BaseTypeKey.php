<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

/**
 * The base gives you the origin on where the discount is calculated upon.
 *
 * Default is the eligibleForDiscount object itself the base for the discount calculation.
 * However it is possible that certain parts of this object deliver the base for
 * discount calculation. E.g. shipping costs, payment costs of the order.
 *
 * Different types of discounts. Default is the generic 'basket' type which points to
 * all the order discounts. Shipping and payment types point out to specific
 * shipping discounts and payment discounts resp.
 *
 * @package Thinktomorrow\Trader\Discounts\Domain\Types
 */
class BaseTypeKey
{
    /**
     *
     *
     * @param EligibleForDiscount $eligibleForDiscount
     * @return EligibleForDiscount
     */
    const BASKET = 'basket';
    const SHIPPING = 'shipping';
    const PAYMENT = 'payment';
}
