<?php declare(strict_types=1);

namespace Purchase\Cart\Domain;

use Optiphar\Users\User;
use Illuminate\Contracts\Support\Arrayable;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;

class CartCustomer implements Arrayable
{
    use HasMagicAttributes;

    /** @var int */
    private $customerId;

    /** @var string */
    private $email;

    /** @var array */
    private $data;

    public function __construct(int $customerId, string $email, array $data)
    {
        $this->customerId = $customerId;
        $this->email = $email;
        $this->data = $data;
    }

    public static function empty()
    {
        return new static(0, '', []);
    }

    public static function fromUser(User $user, array $overrides = [])
    {
        return new static($user->id, $user->email, array_merge([
            'is_onetime_customer' => false,
            'telephone' => $user->telephone,
        ], $overrides));
    }

    public function exists(): bool
    {
        return ($this->customerId > 0);
    }

    public function isOneTimeCustomer(): bool
    {
        return $this->data('is_onetime_customer', false);
    }

    public function email(): string
    {
        return $this->email;
    }

    public function telephone(): ?string
    {
        return $this->data('telephone', null);
    }

    public function customerId(): int
    {
        return $this->customerId;
    }

    private function data($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge($this->data, [
            'customerid' => $this->customerId,
            'email' => $this->email,
        ]);
    }
}
