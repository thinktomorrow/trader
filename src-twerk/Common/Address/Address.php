<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Address;

use InvalidArgumentException;

class Address
{
    private ?string $country;
    private ?string $street;
    private ?string $number;
    private ?string $bus;
    private ?string $zipcode;
    private ?string $city;

    public function __construct(?string $country, ?string $street, ?string $number, ?string $bus, ?string $zipcode, ?string $city)
    {
        $this->country = $country;
        $this->street = $street;
        $this->number = $number;
        $this->bus = $bus;
        $this->zipcode = $zipcode;
        $this->city = $city;
    }

    public static function fromArray(array $values): self
    {
        self::validateArrayKeys($values);

        return new static(
            $values['country'],
            $values['street'],
            $values['number'],
            $values['bus'],
            $values['zipcode'],
            $values['city']
        );
    }

    public static function empty(): self
    {
        return new static(null, null, null, null, null, null);
    }

    public function isEmpty(): bool
    {
        return (! $this->country || ! $this->street || ! $this->number || ! $this->zipcode);
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getBus(): ?string
    {
        return $this->bus;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function replaceCountry(string $country): self
    {
        return new static($country, $this->street, $this->number, $this->bus, $this->zipcode, $this->city);
    }

    public function replaceStreet(string $street): self
    {
        return new static($this->country, $street, $this->number, $this->bus, $this->zipcode, $this->city);
    }

    public function replaceNumber(string $number): self
    {
        return new static($this->country, $this->street, $number, $this->bus, $this->zipcode, $this->city);
    }

    public function replaceBus(string $bus): self
    {
        return new static($this->country, $this->street, $this->number, $bus, $this->zipcode, $this->city);
    }

    public function replaceZipcode(string $zipcode): self
    {
        return new static($this->country, $this->street, $this->number, $this->bus, $zipcode, $this->city);
    }

    public function replaceCity(string $city): self
    {
        return new static($this->country, $this->street, $this->number, $this->bus, $this->zipcode, $city);
    }

    public function toArray(): array
    {
        return [
            'country' => $this->country,
            'street' => $this->street,
            'number' => $this->number,
            'bus' => $this->bus,
            'zipcode' => $this->zipcode,
            'city' => $this->city,
        ];
    }

    private static function validateArrayKeys(array $values): void
    {
        $allowedKeys = ['country', 'street', 'number', 'bus', 'zipcode', 'city'];
        $detectedUnallowedKeys = array_diff_key(array_flip($allowedKeys), $values);

        if (count($detectedUnallowedKeys) > 0) {
            throw new InvalidArgumentException('Invalid array keys passed to Address object. Allowed keys are: [' . implode(',', $allowedKeys) . ']. Passed unallowed keys: [' . implode(',', $detectedUnallowedKeys) . ']');
        }
    }
}
