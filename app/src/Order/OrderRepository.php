<?php

namespace App\Order;

use Money\Money;
use Thinktomorrow\Trader\Orders\Ports\Reads\ExpandedOrder;

class OrderRepository
{
    public function all()
    {
        return [
            new ExpandedOrder([
                'total'        => Money::EUR(120),
                'reference'    => '119adfei393',
                'confirmed_at' => (new \DateTime('@'.strtotime('-9days'))),
                'state'        => 'refunded',
            ]),
            new ExpandedOrder([
                'total'        => Money::EUR(3900),
                'reference'    => 'dkajepidfqsd29929',
                'confirmed_at' => (new \DateTime('@'.strtotime('-1days'))),
                'state'        => 'pending',
            ]),
            new ExpandedOrder([
                'total'        => Money::EUR(0),
                'reference'    => 'dakjdmfiqdfq',
                'confirmed_at' => (new \DateTime('@'.strtotime('-90days'))),
                'state'        => 'confirmed',
            ]),
        ];
    }
}
