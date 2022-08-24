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
    case cart_queued_for_deletion = 'cart_queued_for_deletion'; // cart is soft deleted and ready for garbage collection

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

    case partially_paid = 'partially_paid'; // one or more of many payments are delivered
    case paid = 'paid'; // fully paid so order can be processed

    case partially_packed = 'partially_packed'; // one or more of many shipments are packed
    case packed = 'packed'; // internally processed the order so order can be shipped

    case partially_delivered = 'partially_delivered'; // one or more of many shipments are delivered
    case delivered = 'delivered'; // fully delivered

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

    public static function getDefaultTransitions(): array
    {
        return [
            'abandon' => [
                'from' => [self::cart_pending, self::cart_revived],
                'to' => self::cart_abandoned,
            ],
            'revive' => [
                'from' => [self::cart_abandoned],
                'to' => self::cart_revived,
            ],
            'remove_cart' => [
                'from' => [self::cart_abandoned],
                'to' => self::cart_queued_for_deletion,
            ],
            'confirm' => [
                'from' => [self::cart_pending, self::cart_revived],
                'to' => self::confirmed,
            ],
            'cancel' => [
                'from' => [self::confirmed],
                'to' => self::cancelled,
            ],
            'cancel_by_merchant' => [
                'from' => [self::confirmed],
                'to' => self::cancelled_by_merchant,
            ],
            'partially_pay' => [
                'from' => [self::confirmed],
                'to' => self::partially_paid,
            ],
            'pay' => [
                'from' => [self::confirmed, self::partially_paid],
                'to' => self::paid,
            ],
            'partially_pack' => [
                'from' => [self::paid, self::partially_paid],
                'to' => self::partially_packed,
            ],
            'pack' => [
                'from' => [self::paid, self::partially_paid, self::partially_packed],
                'to' => self::packed,
            ],
            'partially_deliver' => [
                'from' => [self::packed, self::partially_packed],
                'to' => self::partially_delivered,
            ],
            'deliver' => [
                'from' => [self::packed, self::partially_packed, self::partially_delivered],
                'to' => self::delivered,
            ],
        ];
    }
}
