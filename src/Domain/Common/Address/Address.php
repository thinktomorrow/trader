<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Address;

class Address
{
    public function __construct(
        public readonly ?string $country,
        public readonly ?string $line1,
        public readonly ?string $line2,
        public readonly ?string $postalCode,
        public readonly ?string $city
    )
    {
    }

    public function replaceCountry(string $country): static
    {
        return new static($country, $this->line1, $this->line2, $this->postalCode, $this->city);
    }

    public function replaceLine1(string $line1): static
    {
        return new static($this->country, $line1, $this->line2, $this->postalCode, $this->city);
    }

    public function replaceLine2(string $line2): static
    {
        return new static($this->country, $this->line1, $line2, $this->postalCode, $this->city);
    }

    public function replacePostalCode(string $postalCode): static
    {
        return new static($this->country, $this->line1, $this->line2, $postalCode, $this->city);
    }

    public function replaceCity(string $city): static
    {
        return new static($this->country, $this->line1, $this->line2, $this->postalCode, $city);
    }

    public function toArray(): array
    {
        return [
            'country' => $this->country,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
        ];
    }
}
