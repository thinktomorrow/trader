<?php

declare(strict_types=1);

namespace Purchase\Cart\Ports;

use Illuminate\Database\Eloquent\Model;
use Purchase\Cart\Domain\CartReference;

class CartModel extends Model
{
    public $table = 'carts';
    public $guarded = [];

    // UUID
    public $primaryKey = 'reference';
    public $keyType = 'string';
    public $incrementing = false;

    public static function findByReference(CartReference $cartReference): ?CartModel
    {
        return static::where('reference', $cartReference->get())->first();
    }

    public static function validateDataIntegrity($data): bool
    {
        if(!is_array($data)) return false;

        foreach(['items','discounts','totals','shipping','payment','customer'] as $key){
            if(!isset($data[$key]) || !is_array($data[$key])) return false;
        }

        return true;
    }

}
