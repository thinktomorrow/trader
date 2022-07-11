<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\State\State;

enum OrderState: string implements State
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
    case cancelled = 'cancelled'; // customer cancelled order after payment
    case cancelled_by_merchant = 'cancelled_by_merchant'; // admin cancelled order after payment
    case unpaid = 'unpaid'; // not yet fully paid or one of the payments has failed
    case paid = 'paid'; // fully paid so order can be processed
    case processed = 'processed'; // internally processed the order
    case undelivered = 'undelivered'; // not yet fully delivered or one of the shippings has failed
    case delivered = 'delivered'; // fully delivered so order can be processed
    case unfulfilled = 'unfulfilled'; // order is not fulfilled yet or something happened that caused the failure.
    case fulfilled = 'fulfilled'; // order is fulfilled and finished

    public function inCustomerHands(): bool
    {
        return in_array($this, static::customerStates());
    }

    public static function customerStates(): array
    {
        return [
            self::cart_pending,
            self::cart_abandoned,
            self::cart_revived,
        ];
    }

    public function getValueAsString(): string
    {
        return $this->value;
    }
}
