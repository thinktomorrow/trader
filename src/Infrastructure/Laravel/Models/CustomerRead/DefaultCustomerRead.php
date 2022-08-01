<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerBillingAddress;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerRead;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerShippingAddress;
use Thinktomorrow\Trader\Domain\Common\Locale;

class DefaultCustomerRead implements CustomerRead
{
    use RendersData;

    private string $customerId;
    private ?CustomerShippingAddress $shippingAddress;
    private ?CustomerBillingAddress $billingAddress;
    private string $email;
    private bool $is_business;
    private array $data;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $childObjects): static
    {
        $customer = new static();

        $customer->customerId = $state['customer_id'];

        $customer->shippingAddress = $childObjects[CustomerShippingAddress::class];
        $customer->billingAddress = $childObjects[CustomerBillingAddress::class];

        $customer->email = $state['email'];
        $customer->locale = Locale::fromString($state['locale']);
        $customer->is_business = $state['is_business'];
        $customer->data = json_decode($state['data'], true);

        return $customer;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCustomerLocale(): Locale
    {
        return $this->locale;
    }

    public function isBusiness(): bool
    {
        return $this->is_business;
    }

    public function getShippingAddress(): ?CustomerShippingAddress
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): ?CustomerBillingAddress
    {
        return $this->billingAddress;
    }
}
