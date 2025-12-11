<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerReadRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Notifications\ResetCustomerPasswordNotification;
use Thinktomorrow\Trader\Infrastructure\Shop\CustomerAuth\Notifications\VerifyEmailNotification;
use Thinktomorrow\Trader\TraderConfig;

/**
 * @property string $customer_id
 */
class CustomerModel extends Model implements AuthenticatableContract, CanResetPasswordContract, MustVerifyEmail
{
    use Authenticatable;
    use CanResetPassword;
    use Notifiable;
    use \Illuminate\Auth\MustVerifyEmail;

    public $table = 'trader_customers';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'data' => 'array',
        'password' => 'hashed',
    ];

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

    public function sendEmailVerificationNotification()
    {
        $customer = app(CustomerReadRepository::class)->findCustomer(CustomerId::fromString($this->getCustomerId()));

        $this->notify(new VerifyEmailNotification(
            app(TraderConfig::class),
            $customer
        ));
    }

    public function getCustomerId(): string
    {
        return $this->customer_id;
    }

    public function getFirstName(): string
    {
        return data_get($this->data, 'firstname', '');
    }

    public function getLastName(): string
    {
        return data_get($this->data, 'lastname', '');
    }

    public function getFullName(): string
    {
        return trim($this->getFirstName() . ' ' . $this->getLastName());
    }

    public function getData(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }
}
