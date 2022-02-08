<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Thinktomorrow\Trader\Order\Domain\OrderState;
use Thinktomorrow\Trader\Order\Domain\OrderStateMachine;
use Thinktomorrow\Trader\Common\State\AbstractStateMachine;
use Thinktomorrow\Trader\Order\Domain\Exceptions\OrderNotInCartState;

class DefaultOrderStateMachine extends AbstractStateMachine implements OrderStateMachine
{
    protected function getStateKey(): string
    {
        return 'order_state';
    }

//    public function getDefaultState(): State
//    {
//        return OrderState::fromString(OrderState::CART_PENDING);
//    }

    protected function getStates(): array
    {
        return [
            /**
             * ------------------------------------------------
             * Cart
             * ------------------------------------------------
             * Order is in customer hands and is still subject to change
             */
            OrderState::CART_PENDING, // order still in cart
            OrderState::CART_ABANDONED, // cart has been stale for too long and is considered abandoned by customer
            OrderState::CART_REVIVED, // abandoned cart has been revived by customer
            OrderState::CART_REMOVED, // cart is soft deleted and ready for garbage collection

            /**
             * ------------------------------------------------
             * Order awaiting payment
             * ------------------------------------------------
             * The cart order has successfully returned from payment provider and is considered confirmed by customer.
             * Not per se paid yet. From this state on, the cart is considered an order awaiting payment and the order cannot be altered anymore by the customer.
             */
            OrderState::CONFIRMED,

            /**
             * ------------------------------------------------
             * Order
             * ------------------------------------------------
             * The order has been successfully paid and the cart can be considered an 'order'.
             */
            OrderState::PAID, // payment received by merchant or acquirer
            OrderState::CANCELLED, // customer cancelled order after payment
            OrderState::HALTED_FOR_PACKING, // Something is wrong with the order (e.g. outdated order,  out of stock, ...)
            OrderState::READY_FOR_PACKING, // Ready to be picked
            OrderState::PACKED, // ready for pickup by the logistic partner
            OrderState::SHIPPED, // in hands of logistic partner
            OrderState::FULFILLED, // delivered to customer
            OrderState::RETURNED, // order is returned to merchant
        ];
    }

    protected function getTransitions(): array
    {
        return [
            'abandon' => [
                'from' => [OrderState::CART_PENDING],
                'to' => OrderState::CART_ABANDONED,
            ],
            'remove' => [
                'from' => [OrderState::CART_PENDING, OrderState::CART_ABANDONED],
                'to' => OrderState::CART_REMOVED,
            ],
            'confirm' => [
                'from' => [OrderState::CART_PENDING, OrderState::CART_ABANDONED],
                'to' => OrderState::CONFIRMED,
            ],
            'pay' => [
                'from' => [OrderState::CONFIRMED],
                'to' => OrderState::PAID,
            ],
            'cancel' => [
                'from' => [OrderState::CONFIRMED, OrderState::PAID, OrderState::READY_FOR_PACKING, OrderState::PACKED],
                'to' => OrderState::CANCELLED,
            ],
            'halt' => [
                'from' => [OrderState::PAID],
                'to' => OrderState::HALTED_FOR_PACKING,
            ],
            'queue' => [
                'from' => [OrderState::PAID, OrderState::HALTED_FOR_PACKING],
                'to' => OrderState::READY_FOR_PACKING,
            ],
            'pack' => [
                'from' => [OrderState::PAID, OrderState::READY_FOR_PACKING],
                'to' => OrderState::PACKED,
            ],
            'ship' => [
                'from' => [OrderState::PACKED],
                'to' => OrderState::SHIPPED,
            ],
            'fulfill' => [
                'from' => [OrderState::SHIPPED],
                'to' => OrderState::FULFILLED,
            ],
            'return' => [
                'from' => [OrderState::SHIPPED, OrderState::FULFILLED],
                'to' => OrderState::RETURNED,
            ],
        ];
    }

    public function emitEvent(string $transition): void
    {

    }

    public function assertCartState(string $state): void
    {
        if (! in_array($state, [
            OrderState::CART_PENDING,
            OrderState::CART_ABANDONED,
            OrderState::CART_REVIVED,
            OrderState::CART_REMOVED,
        ])) {
            throw new OrderNotInCartState('Invalid order state. '. $state . ' is not asserted as a cart state.');
        }
    }
}
