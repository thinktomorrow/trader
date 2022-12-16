<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\State;

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
    case cart_complete = 'cart_complete'; // cart info is considered complete and payment and order fulfillment is possible

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
     * Order quotation
     * ------------------------------------------------
     * The order is quoted which means that the customer cannot change this order but still needs to explicitly confirm this order.
     */
    case quoted = 'quote';
    case quote_confirmed = 'quote_confirmed';

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
            self::cart_complete,
        ];
    }

    public function getValueAsString(): string
    {
        return $this->value;
    }

    public function equals($other): bool
    {
        return (get_class($this) === get_class($other) && $this->getValueAsString() === $other->getValueAsString());
    }

    public static function getDefaultTransitions(): array
    {
        return [
            'quote' => [
                'from' => [self::cart_pending, self::cart_revived, self::cart_complete],
                'to' => self::quoted,
            ],
            'abandon' => [
                'from' => [self::cart_pending, self::cart_revived, self::cart_complete],
                'to' => self::cart_abandoned,
            ],
            'revive' => [
                'from' => [self::cart_abandoned],
                'to' => self::cart_revived,
            ],
            'delete' => [
                'from' => [self::cart_abandoned, self::cart_pending, self::cart_revived, self::cart_complete],
                'to' => self::cart_queued_for_deletion,
            ],
            'complete' => [
                'from' => [self::cart_pending, self::cart_revived],
                'to' => self::cart_complete,
            ],
            'confirm' => [
                'from' => [self::cart_pending, self::cart_revived, self::cart_complete],
                'to' => self::confirmed,
            ],
            'confirm_quote' => [
                'from' => [self::quoted],
                'to' => self::quote_confirmed,
            ],
            'cancel' => [
                'from' => [self::cart_complete, self::cart_revived, self::confirmed],
                'to' => self::cancelled,
            ],
            'cancel_by_merchant' => [
                'from' => [self::cart_complete, self::confirmed, self::quote_confirmed],
                'to' => self::cancelled_by_merchant,
            ],
            'partially_pay' => [
                'from' => [self::cart_complete, self::confirmed],
                'to' => self::partially_paid,
            ],
            'pay' => [
                'from' => [self::cart_complete, self::confirmed, self::partially_paid, self::quote_confirmed],
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
