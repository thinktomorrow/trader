<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Illuminate\Database\Eloquent\Model;
use Thinktomorrow\Trader\Common\State\Stateful;
use Thinktomorrow\Trader\Order\Domain\OrderState;
use Thinktomorrow\Trader\Order\Domain\OrderReference;

class OrderModel extends Model implements Stateful
{
    public $table = 'trader_orders';
    public $guarded = [];

    // UUID
    public $primaryKey = 'id';
    public $keyType = 'string';
    public $incrementing = false;
    public $casts = [
        'tax_rates' => 'array',
    ];

    public static function findByReference(OrderReference $orderReference): ?OrderModel
    {
        return static::where('id', $orderReference->get())->first();
    }

    public function products()
    {
        return $this->hasMany(OrderProductModel::class, 'order_id');
    }

    public function shipping()
    {
        return $this->hasOne(OrderShippingModel::class, 'order_id');
    }

    public function payment()
    {
        return $this->hasOne(OrderPaymentModel::class, 'order_id');
    }

    public function customer()
    {
        return $this->hasOne(OrderCustomerModel::class, 'order_id');
    }

    public static function validateDataIntegrity($data): bool
    {
        if (! is_array($data)) {
            return false;
        }

        foreach (['items','discounts','totals','shipping','payment','customer'] as $key) {
            if (! isset($data[$key]) || ! is_array($data[$key])) {
                return false;
            }
        }

        return true;
    }

    public function getState(string $key): string
    {
        return $this->$key ?? OrderState::CART_PENDING;
    }

    public function changeState(string $key, $state): void
    {
        $this->$key = $state;
    }
}
