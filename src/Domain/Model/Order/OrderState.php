<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

enum OrderState: string
{
    /**
     * ------------------------------------------------
     * Cart
     * ------------------------------------------------
     * Order is in customer hands and is still subject to change
     */
    case cart_pending = 'cart_pending'; // order still in cart
    case cart_abandoned = 'cart_abandoned'; // cart has been stale for too long and is considered abandoned by customer
    case cart_revived = 'cart_revived'; // abandoned cart has been revived by customer
    case cart_removed = 'cart_removed'; // cart is soft deleted and ready for garbage collection

    /**
     * ------------------------------------------------
     * Order awaiting payment
     * ------------------------------------------------
     * the cart order has successfully returned from payment provider and is considered confirmed by customer.
     * not per se paid yet. from this state on, the cart is considered an order awaiting payment and the order cannot be altered anymore by the customer.
     */
    case confirmed = 'confirmed';

    /**
     * ------------------------------------------------
     * Order
     * ------------------------------------------------
     * the order has been successfully paid and the cart can be considered an 'order'.
     */
    case paid = 'paid'; // payment received by merchant or acquirer
    case cancelled = 'cancelled'; // customer cancelled order after payment
    case halted_for_packing = 'halted_for_packing'; // something is wrong with the order (e.g. outdated order,  out of stock, ...)
    case ready_for_packing = 'ready_for_packing'; // ready to be picked
    case packed = 'packed'; // ready for pickup by the logistic partner
    case shipped = 'shipped'; // in hands of logistic partner
    case fulfilled = 'fulfilled'; // delivered to customer
    case returned = 'returned'; // order is returned to merchant
}