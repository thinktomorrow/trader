<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Thinktomorrow\Trader\Common\Domain\State\StatefulContract;
use Thinktomorrow\Trader\Common\Domain\State\StateMachine;

class OrderState extends StateMachine
{
    // Incomplete states - order is still in customer hands
    const NEW = 'new'; // Order exists but without items
    const PENDING = 'pending'; // order still in cart
    const ABANDONED = 'abandoned'; // order has been stale for too long
    const REMOVED = 'removed'; // order is in queue to be removed
    const CONFIRMED = 'confirmed'; // ready for payment - unpaid

    // Complete states - order can be processed by merchant
    const PAID = 'paid'; // payment received by merchant or acquirer
    const HALTED_FOR_PROCESS = 'halted_for_process'; // Something is wrong with the order (e.g. outdated order,  out of stock, ...)
    const QUEUED_FOR_PROCESS = 'queued_for_process';
    const PROCESSED = 'processed'; // ready for pickup
    const CANCELLED = 'cancelled'; // customer cancelled order after payment
    const SHIPPED = 'shipped'; // in hands of delivery service
    const FULFILLED = 'fulfilled'; // delivered to customer
    const RETURNED = 'returned';
    const REFUNDED = 'refunded';

    protected $states = [
        self::NEW,
        self::PENDING,
        self::ABANDONED,
        self::REMOVED,
        self::CONFIRMED,

        self::PAID,
        self::CANCELLED,
        self::HALTED_FOR_PROCESS,
        self::QUEUED_FOR_PROCESS,
        self::PROCESSED,
        self::SHIPPED,
        self::FULFILLED,
        self::RETURNED,
        self::REFUNDED,
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
        'pay' => [
            'from' => [self::CONFIRMED],
            'to'   => self::PAID,
        ],
        'cancel' => [
            'from' => [self::CONFIRMED, self::PAID, self::QUEUED_FOR_PROCESS, self::PROCESSED],
            'to'    => self::CANCELLED,
        ],
        'halt' => [
            'from' => [self::PAID],
            'to'    => self::HALTED_FOR_PROCESS,
        ],
        'queue' => [
            'from' => [self::PAID, self::HALTED_FOR_PROCESS],
            'to'    => self::QUEUED_FOR_PROCESS,
        ],
        'process' => [
            'from' => [self::PAID, self::QUEUED_FOR_PROCESS],
            'to'    => self::PROCESSED,
        ],
        'ship' => [
            'from' => [self::PROCESSED],
            'to'    => self::SHIPPED,
        ],
        'fulfill' => [
            'from' => [self::SHIPPED],
            'to'    => self::FULFILLED,
        ],
        'return' => [
            'from' => [self::SHIPPED, self::FULFILLED],
            'to'    => self::RETURNED,
        ],
        'refund' => [
            'from' => [self::SHIPPED, self::FULFILLED, self::RETURNED],
            'to'    => self::REFUNDED,
        ],
    ];

    public function __construct(StatefulContract $order)
    {
        parent::__construct($order);
    }

    public function inCustomerHands(): bool
    {
        return in_array($this->statefulContract->state(), [
            static::NEW,
            static::PENDING,
            static::ABANDONED,
            static::REMOVED,
            static::CONFIRMED, // Should stay the same, but customer can still change cart prior to payment
        ]);
    }

    public function inMerchantHands(): bool
    {
        return !$this->inCustomerHands();
    }
}
