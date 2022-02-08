<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Domain;

use Thinktomorrow\Trader\Common\State\StateValueDefaults;

class OrderState
{
    use StateValueDefaults;

    public static $KEY = 'order_state';

    /**
     * ------------------------------------------------
     * Cart
     * ------------------------------------------------
     * Order is in customer hands and is still subject to change
     */
    const CART_PENDING = 'cart_pending'; // order still in cart
    const CART_ABANDONED = 'cart_abandoned'; // cart has been stale for too long and is considered abandoned by customer
    const CART_REVIVED = 'cart_revived'; // abandoned cart has been revived by customer
    const CART_REMOVED = 'cart_removed'; // cart is soft deleted and ready for garbage collection

    /**
     * ------------------------------------------------
     * Order awaiting payment
     * ------------------------------------------------
     * The cart order has successfully returned from payment provider and is considered confirmed by customer.
     * Not per se paid yet. From this state on, the cart is considered an order awaiting payment and the order cannot be altered anymore by the customer.
     */
    const CONFIRMED = 'confirmed';

    /**
     * ------------------------------------------------
     * Order
     * ------------------------------------------------
     * The order has been successfully paid and the cart can be considered an 'order'.
     */
    const PAID = 'paid'; // payment received by merchant or acquirer
    const CANCELLED = 'cancelled'; // customer cancelled order after payment
    const HALTED_FOR_PACKING = 'halted_for_packing'; // Something is wrong with the order (e.g. outdated order,  out of stock, ...)
    const READY_FOR_PACKING = 'ready_for_packing'; // Ready to be picked
    const PACKED = 'packed'; // ready for pickup by the logistic partner
    const SHIPPED = 'shipped'; // in hands of logistic partner
    const FULFILLED = 'fulfilled'; // delivered to customer
    const RETURNED = 'returned'; // order is returned to merchant
}
