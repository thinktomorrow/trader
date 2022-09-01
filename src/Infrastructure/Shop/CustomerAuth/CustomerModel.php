<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerReadRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Notifications\ResetCustomerPasswordNotification;
use Thinktomorrow\Trader\TraderConfig;

/**
 * @property string $customer_id
 */
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
        $customer = app(CustomerReadRepository::class)->findCustomer(CustomerId::fromString($this->getCustomerId()));

        $this->notify(new ResetCustomerPasswordNotification(
            $token,
            app(TraderConfig::class),
            $customer
        ));
    }

    public function getCustomerId(): string
    {
        return $this->customer_id;
    }
}
