<?php

namespace App\Order;

use Money\Money;
use Thinktomorrow\Trader\Order\Ports\Web\Merchant\Order;

class OrderRepository
{
    public function all()
    {
        return [
            new Order([
                'total'        => Money::EUR(120),
                'reference'    => '119adfei393',
                'confirmed_at' => (new \DateTime('@'.strtotime('-9days'))),
                'state'        => 'refunded',
            ]),
            new Order([
                'total'        => Money::EUR(3900),
                'reference'    => 'dkajepidfqsd29929',
                'confirmed_at' => (new \DateTime('@'.strtotime('-1days'))),
                'state'        => 'pending',
            ]),
            new Order([
                'total'        => Money::EUR(0),
                'reference'    => 'dakjdmfiqdfq',
                'confirmed_at' => (new \DateTime('@'.strtotime('-90days'))),
                'state'        => 'confirmed',
            ]),
        ];
    }
}
