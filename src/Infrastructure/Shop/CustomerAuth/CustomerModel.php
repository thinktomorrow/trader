<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Notifications\ResetCustomerPasswordNotification;

class CustomerModel extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable;
    use CanResetPassword;
    use Notifiable;

    public $table = 'trader_customers';

    // Using uuid as primary key
    public $primaryKey = 'customer_id';
    public $keyType = 'string';
    public $incrementing = false;

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetCustomerPasswordNotification($token));
    }
}
