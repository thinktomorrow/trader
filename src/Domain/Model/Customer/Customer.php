<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\ShippingAddress;

class Customer implements Aggregate
{
    use RecordsEvents;
    use HasData;

    public readonly CustomerId $customerId;
    private Email $email;
    private bool $isBusiness;
    private Locale $locale;
    private ?BillingAddress $billingAddress = null;
    private ?ShippingAddress $shippingAddress = null;

    private function __construct()
    {
    }

    public static function create(CustomerId $customerId, Email $email, bool $isBusiness, Locale $locale)
    {
        $customer = new static();
        $customer->customerId = $customerId;
        $customer->email = $email;
        $customer->isBusiness = $isBusiness;
        $customer->locale = $locale;

        return $customer;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function updateLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }

    public function updateBusiness(bool $isBusiness): void
    {
        $this->isBusiness = $isBusiness;
    }

    public function isBusiness(): bool
    {
        return $this->isBusiness;
    }

    public function getBillingAddress(): ?BillingAddress
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): ?ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function updateBillingAddress(BillingAddress $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function updateShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function getMappedData(): array
    {
        return [
            'customer_id' => $this->customerId->get(),
            'email' => $this->email->get(),
            'is_business' => $this->isBusiness,
            'locale' => $this->locale->get(),
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            BillingAddress::class => $this->billingAddress?->getMappedData(),
            ShippingAddress::class => $this->shippingAddress?->getMappedData(),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $customer = new static();
        $customer->customerId = $state['customer_id'] ? CustomerId::fromString($state['customer_id']) : null;
        $customer->email = Email::fromString($state['email']);
        $customer->isBusiness = ! ! $state['is_business'];
        $customer->locale = Locale::fromString($state['locale']);
        $customer->data = json_decode($state['data'], true);

        $customer->shippingAddress = $childEntities[ShippingAddress::class] ? ShippingAddress::fromMappedData($childEntities[ShippingAddress::class], $state) : null;
        $customer->billingAddress = $childEntities[BillingAddress::class] ? BillingAddress::fromMappedData($childEntities[BillingAddress::class], $state) : null;


        return $customer;
    }
}
