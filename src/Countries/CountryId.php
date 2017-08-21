<?php

namespace Thinktomorrow\Trader\Countries;

use Assert\Assertion;

class CountryId
{
    /**
     * @var string
     */
    private $isoString;

    private function __construct(string $isoString)
    {
        Assertion::length($isoString, 2);

        $this->isoString = $isoString;
    }

    public static function fromIsoString(string $isoString): self
    {
        return new self($isoString);
    }

    public function get(): string
    {
        return $this->isoString;
    }

    public function equals($otherCountryId): bool
    {
        return get_class($otherCountryId) === get_class($this)
                && (string) $this->get() === (string) $otherCountryId->get();
    }

    public function __toString()
    {
        return $this->get();
    }
}
