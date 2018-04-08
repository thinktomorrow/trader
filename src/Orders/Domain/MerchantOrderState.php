<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Thinktomorrow\Trader\Common\State\StateMachine;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantOrder;

class MerchantOrderStateOld extends StateMachine
{
    // Complete states - order can be processed by merchant
    const CONFIRMED = 'confirmed';
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
        'fullfill' => [
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

    public function __construct(MerchantOrder $order)
    {
        parent::__construct($order);
    }

    public function inCustomerHands(): bool
    {
        return in_array($this->statefulContract->state(), [
            OrderState::NEW,
            OrderState::PENDING,
            OrderState::ABANDONED,
            OrderState::REMOVED,
            OrderState::CONFIRMED, // Should stay the same, but customer can still change cart prior to payment
        ]);
    }

    public function inMerchantHands(): bool
    {
        return !$this->inCustomerHands();
    }
}
