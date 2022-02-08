<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Illuminate\Database\Eloquent\Model;

class OrderCustomerModel extends Model
{
    public $table = 'trader_order_customer';
    public $guarded = [];
    public $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'data' => 'array',
    ];
}
