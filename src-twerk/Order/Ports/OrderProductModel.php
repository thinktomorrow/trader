<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Illuminate\Database\Eloquent\Model;

class OrderProductModel extends Model
{
    public $table = 'trader_order_products';
    public $guarded = [];
    public $casts = [
        'total' => 'int',
        'discount_total' => 'int',
        'unit_price' => 'int',
        'tax_rate' => 'int',
        'data' => 'array',
    ];
}
