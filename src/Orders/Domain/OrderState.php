<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Thinktomorrow\Trader\Common\Domain\State\StateMachine;

class OrderState extends StateMachine
{
    // Incomplete states - order is still in customer hands
    const NEW = 'new'; // Order exists but without items
    const PENDING = 'pending'; // order still in cart
    const ABANDONED = 'abandoned'; // order has been stale for too long
    const REMOVED = 'removed'; // order is in queue to be removed

    // Complete states - order can be processed by merchant
    const CONFIRMED = 'confirmed'; // ready for payment - unpaid
//    const CANCELLED = 'cancelled'; // customer cancelled order
//    const PROCESSED = 'processed'; // ready for pickup
//    const SHIPPED = 'shipped'; // picked up
//    const FULLFILLED = 'fullfilled'; // delivered to customer
//    const REFUNDED = 'refunded';
//    const RETURNED = 'returned';

    protected $states = [
        self::NEW,
        self::PENDING,
        self::ABANDONED,
        self::REMOVED,
        self::CONFIRMED,
//        self::PAID,
    ];

    protected $transitions = [
        'create' => [
            'from' => [self::NEW],
            'to'   => self::PENDING,
        ],
        'abandon' => [
            'from' => [self::PENDING],
            'to'   => self::ABANDONED,
        ],
        'remove' => [
          'from' => [self::PENDING, self::ABANDONED],
          'to'   => self::REMOVED,
        ],
        'confirm' => [
            'from' => [self::PENDING, self::ABANDONED],
            'to'   => self::CONFIRMED,
        ],
    ];

    public function __construct(Order $order)
    {
        parent::__construct($order);
    }

    public function inCustomerHands(): bool
    {
        return in_array($this->statefulContract->state(), [
            static::NEW,
            static::PENDING,
            static::ABANDONED,
            static::CONFIRMED, // Should stay the same, but customer can still change cart prior to payment
        ]);
    }

    public function inMerchantHands(): bool
    {
        return !$this->inCustomerHands();
    }
}
