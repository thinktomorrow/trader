<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Address;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class Address
{
    public function __construct(
        public readonly ?CountryId $countryId,
        public readonly ?string $line1,
        public readonly ?string $line2,
        public readonly ?string $postalCode,
        public readonly ?string $city
    ) {
    }

    public function replaceCountry(CountryId $countryId): static
    {
        return new static($countryId, $this->line1, $this->line2, $this->postalCode, $this->city);
    }

    public function replaceLine1(string $line1): static
    {
        return new static($this->countryId, $line1, $this->line2, $this->postalCode, $this->city);
    }

    public function replaceLine2(string $line2): static
    {
        return new static($this->countryId, $this->line1, $line2, $this->postalCode, $this->city);
    }

    public function replacePostalCode(string $postalCode): static
    {
        return new static($this->countryId, $this->line1, $this->line2, $postalCode, $this->city);
    }

    public function replaceCity(string $city): static
    {
        return new static($this->countryId, $this->line1, $this->line2, $this->postalCode, $city);
    }

    public function toArray(): array
    {
        return [
            'country_id' => $this->countryId->get(),
            'line1' => $this->line1,
            'line2' => $this->line2,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
        ];
    }

    public function diff(self $other): array
    {
        return array_diff($other->toArray(), $this->toArray());
    }
}
