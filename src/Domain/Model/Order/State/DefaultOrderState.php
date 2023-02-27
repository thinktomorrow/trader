<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\State;

use Thinktomorrow\Trader\Domain\Common\State\State;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartAbandoned;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartCompleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartQueuedForDeletion;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartRevived;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderCancelled;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderCancelledByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderConfirmed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderConfirmedAsBusiness;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderMarkedPaidByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderQuoted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\QuotedOrderConfirmed;

enum DefaultOrderState:string implements OrderState
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
    case cart_completed = 'cart_completed'; // cart info is considered complete and payment and order fulfillment is possible

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
     * Business states
     * ------------------------------------------------
     * The order is not paid but is ready for processing. In case of a business order,
     * the fulfillment can take place before any payment is made.
     * The invoice is the request for payment to the business customer.
     */
    case confirmed_as_business = 'confirmed_as_business';

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
    case marked_paid_by_merchant = 'marked_paid_by_merchant'; // merchant set this order as paid

    case partially_packed = 'partially_packed'; // one or more of many shipments are packed
    case packed = 'packed'; // internally processed the order so order can be shipped

    case partially_delivered = 'partially_delivered'; // one or more of many shipments are delivered
    case delivered = 'delivered'; // fully delivered

    public static function fromString(string $state): self
    {
        return static::from($state);
    }

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
            self::cart_completed,
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

    public static function getDefaultState(): self
    {
        return static::cart_pending;
    }

    public static function getStates(): array
    {
        return static::cases();
    }

    public static function getTransitions(): array
    {
        return [
            'quote' => [
                'from' => [self::cart_pending, self::cart_revived, self::cart_completed],
                'to' => self::quoted,
            ],
            'abandon' => [
                'from' => [self::cart_pending, self::cart_revived, self::cart_completed],
                'to' => self::cart_abandoned,
            ],
            'revive' => [
                'from' => [self::cart_abandoned],
                'to' => self::cart_revived,
            ],
            'delete' => [
                'from' => [self::cart_abandoned, self::cart_pending, self::cart_revived, self::cart_completed],
                'to' => self::cart_queued_for_deletion,
            ],
            'complete' => [
                'from' => [self::cart_pending, self::cart_revived],
                'to' => self::cart_completed,
            ],
            'confirm' => [
                'from' => [self::cart_pending, self::cart_revived, self::cart_completed],
                'to' => self::confirmed,
            ],
            'confirm_quote' => [
                'from' => [self::quoted],
                'to' => self::quote_confirmed,
            ],
            'confirm_as_business' => [
                'from' => [self::cart_pending, self::cart_revived, self::cart_completed],
                'to' => self::confirmed_as_business,
            ],
            'cancel' => [
                'from' => [self::cart_completed, self::cart_revived, self::confirmed],
                'to' => self::cancelled,
            ],
            'cancel_by_merchant' => [
                'from' => [self::cart_completed, self::confirmed, self::paid, self::marked_paid_by_merchant, self::quote_confirmed, self::confirmed_as_business],
                'to' => self::cancelled_by_merchant,
            ],
            'partially_pay' => [
                'from' => [self::cart_completed, self::confirmed],
                'to' => self::partially_paid,
            ],
            'mark_paid_by_merchant' => [
                'from' => [self::cart_completed, self::confirmed, self::partially_paid, self::quote_confirmed, self::confirmed_as_business],
                'to' => self::marked_paid_by_merchant,
            ],
            'pay' => [
                'from' => [self::cart_completed, self::confirmed, self::partially_paid, self::quote_confirmed, self::confirmed_as_business, self::marked_paid_by_merchant],
                'to' => self::paid,
            ],
            'partially_pack' => [
                'from' => [self::paid, self::partially_paid, self::marked_paid_by_merchant, self::confirmed_as_business],
                'to' => self::partially_packed,
            ],
            'pack' => [
                'from' => [self::paid, self::partially_paid, self::marked_paid_by_merchant, self::partially_packed, self::confirmed_as_business],
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

    public static function getEventMapping(): array
    {
        return [
            self::cart_abandoned->value => CartAbandoned::class,
            self::cart_revived->value => CartRevived::class,
            self::cart_queued_for_deletion->value => CartQueuedForDeletion::class,
            self::cart_completed->value => CartCompleted::class,
            self::confirmed->value => OrderConfirmed::class,
            self::cancelled->value => OrderCancelled::class,
            self::cancelled_by_merchant->value => OrderCancelledByMerchant::class,
            self::quoted->value => OrderQuoted::class,
            self::quote_confirmed->value => QuotedOrderConfirmed::class,
            self::confirmed_as_business->value => OrderConfirmedAsBusiness::class,
            self::paid->value => OrderPaid::class,
            self::partially_paid->value => OrderPartiallyPaid::class,
            self::marked_paid_by_merchant->value => OrderMarkedPaidByMerchant::class,
            self::packed->value => OrderPacked::class,
            self::partially_packed->value => OrderPartiallyPacked::class,
            self::delivered->value => OrderDelivered::class,
            self::partially_delivered->value => OrderPartiallyDelivered::class,
        ];
    }
}
