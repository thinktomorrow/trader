<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Thinktomorrow\Trader\Common\Domain\State\StateMachine;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantOrder;


class MerchantOrderState extends StateMachine
{
    // Complete states - order can be processed by merchant
    const PAID = 'paid'; // payment received by merchant or acquirer
    const CANCELLED = 'cancelled'; // customer cancelled order
    const PREPARED_FOR_PROCESS = 'prepared_for_process';
    const PROCESSED = 'processed'; // ready for pickup
    const SHIPPED = 'shipped'; // picked up
    const FULLFILLED = 'fullfilled'; // delivered to customer
    const RETURNED = 'returned';
    const REFUNDED = 'refunded';

    protected $states = [
        self::PAID,
        self::CANCELLED,
        self::PREPARED_FOR_PROCESS,
        self::PROCESSED,
        self::SHIPPED,
        self::FULLFILLED,
        self::RETURNED,
        self::REFUNDED,
    ];

    protected $transitions = [
        'cancel' => [
            'from' => [self::PAID, self::PREPARED_FOR_PROCESS, self::PROCESSED],
            'to'    => self::CANCELLED,
        ],
        'prepare' => [
            'from' => [self::PAID],
            'to'    => self::PREPARED_FOR_PROCESS,
        ],
        'process' => [
            'from' => [self::PAID, self::PREPARED_FOR_PROCESS],
            'to'    => self::PROCESSED,
        ],
        'ship' => [
            'from' => [self::PROCESSED],
            'to'    => self::SHIPPED,
        ],
        'fullfill' => [
            'from' => [self::SHIPPED],
            'to'    => self::FULLFILLED,
        ],
        'return' => [
            'from' => [self::SHIPPED, self::FULLFILLED],
            'to'    => self::RETURNED,
        ],
        'refund' => [
            'from' => [self::SHIPPED, self::FULLFILLED, self::RETURNED],
            'to'    => self::REFUNDED,
        ],
    ];

    public function __construct(MerchantOrder $order)
    {
        parent::__construct($order);
    }
}
