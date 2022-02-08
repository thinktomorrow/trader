<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Illuminate\Database\Eloquent\Model;

class OrderShippingModel extends Model
{
    public $table = 'trader_order_shipping';
    public $guarded = [];
    public $casts = [
        'address' => 'array',
        'data' => 'array',
        'total' => 'int',
        'subtotal' => 'int',
        'discount_total' => 'int',
        'tax_total' => 'int',
        'tax_rate' => 'int',
    ];
}
