<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

abstract class Address
{
    use RendersData;

    protected ?CountryId $countryId;
    protected ?string $line1;
    protected ?string $line2;
    protected ?string $postalCode;
    protected ?string $city;
    protected array $data;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState): static
    {
        $address = new static();

        $address->countryId = $state['country_id'] ? CountryId::fromString($state['country_id']) : null;
        $address->line1 = $state['line_1'];
        $address->line2 = $state['line_2'];
        $address->postalCode = $state['postal_code'];
        $address->city = $state['city'];
        $address->data = json_decode($state['data'], true);

        return $address;
    }

    public function getCountryId(): ?string
    {
        return $this->countryId?->get();
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getLine1(): ?string
    {
        return $this->line1;
    }

    public function getLine2(): ?string
    {
        return $this->line2;
    }

    public function equalsAddress($otherAddress): bool
    {
        if (! $otherAddress || ! $otherAddress instanceof Address) {
            return false;
        }

        return $this->countryId == $otherAddress->countryId
            && $this->postalCode == $otherAddress->postalCode
            && $this->city == $otherAddress->city
            && $this->line1 == $otherAddress->line1
            && $this->line2 == $otherAddress->line2;
    }
}
