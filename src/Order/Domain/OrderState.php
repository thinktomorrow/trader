<?php

namespace Thinktomorrow\Trader\Order\Domain;

use Thinktomorrow\Trader\Common\Domain\State\StateMachine;

class OrderState extends StateMachine
{
    // Incomplete states - order is still in customer hands
    const STATE_NEW = 'new';
    const STATE_PENDING = 'pending';
    const STATE_ABANDONED = 'abandoned';
    const STATE_CONFIRMED = 'confirmed'; // ready for payment

    // Complete states - order can be processed by merchant
    const STATE_PAID = 'paid';
    const STATE_PROCESSED = 'processed'; // ready for pickup
    const STATE_SHIPPED = 'shipped';
    const STATE_FULLFILLED = 'fullfilled'; // delivered
    const STATE_RETURNED = 'returned';
    const STATE_REFUNDED = 'refunded';

    protected $states = [
        self::STATE_NEW,
        self::STATE_PENDING,
        'abandoned',
        'confirmed',
        'removed',

        'paid', // paid
        'readyForPickup',
        'pickedUp',
        'void', // ??? cancel between payment and receiving the payment by merchant
        'returned',
        'refunded',
    ];

    protected $transitions = [
        'create' => [
            'from' => ['new'],
            'to' => 'pending'
        ],
        'confirm' => [
            'from' => ['pending','abandoned'],
            'to' => 'confirmed'
        ],
        'abandon' => [
            'from' => ['pending'],
            'to' => 'abandoned'
        ],
        'remove' => [
          'from' => ['pending','abandoned'],
          'to' => 'removed'
        ],
    ];

    public function __construct(Order $order)
    {
        parent::__construct($order);
    }
}