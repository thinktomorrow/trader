<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer;

use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class UpdateBillingAddress
{
    private string $customerId;
    private ?string $countryId;
    private ?string $line1;
    private ?string $line2;
    private ?string $postalCode;
    private ?string $city;

    public function __construct(string $customerId, ?string $countryId = null, ?string $line1 = null, ?string $line2 = null, ?string $postalCode = null, ?string $city = null)
    {
        $this->customerId = $customerId;
        $this->countryId = $countryId;
        $this->line1 = $line1;
        $this->line2 = $line2;
        $this->postalCode = $postalCode;
        $this->city = $city;
    }

    public function getCustomerId(): CustomerId
    {
        return CustomerId::fromString($this->customerId);
    }

    public function getAddress(): Address
    {
        return new Address(
            CountryId::fromString($this->countryId),
            $this->line1,
            $this->line2,
            $this->postalCode,
            $this->city,
        );
    }
}
