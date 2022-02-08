<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Domain;

use Thinktomorrow\Trader\Common\Address\Address;

class OrderCustomer
{
    private ?string $id;
    private ?string $customerId;
    private string $email;
    private Address $billingAddress;
    private Address $shippingAddress;
    private array $data;

    public function __construct(?string $id, ?string $customerId, string $email, Address $billingAddress, Address $shippingAddress, array $data)
    {
        if ((! is_null($id) && ! $id) || (! is_null($customerId) && ! $customerId)) {
            throw new \InvalidArgumentException('empty strings for id or customerId values are not allowed. Use null instead');
        }

        $this->id = $id;
        $this->customerId = $customerId;
        $this->email = $email;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->data = $data;
    }

    public static function empty()
    {
        return new static(null, null, '', Address::empty(), Address::empty(), []);
    }

    /** does it already exist as orderCustomer record in storage */
    public function exists(): bool
    {
        return ! ! $this->id;
    }

    public function isExistingCustomer(): bool
    {
        return ! ! $this->customerId;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    public function isBusiness(): bool
    {
        return true === $this->data('is_business');
    }

    /**
     * Indicates if the ordering customer requires VAT or not.
     * @return bool
     */
    public function isTaxApplicable(): bool
    {
        return true === $this->data('is_tax_applicable');
    }

    public function data($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function toArray(): array
    {
        return array_merge($this->data, [
            'customerId' => $this->customerId,
            'email' => $this->email,
            'billingAddress' => $this->billingAddress->toArray(),
            'shippingAddress' => $this->shippingAddress->toArray(),
        ]);
    }
}
