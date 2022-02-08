<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Illuminate\Database\Eloquent\Model;

class OrderPaymentModel extends Model
{
    public $table = 'trader_order_payment';
    public $guarded = [];
    public $casts = [
        'data' => 'array',
        'total' => 'int',
        'subtotal' => 'int',
        'discount_total' => 'int',
        'tax_total' => 'int',
        'tax_rate' => 'int',
    ];
}
