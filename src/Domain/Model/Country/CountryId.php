<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Country;

class CountryId
{
    private string $country;

    private function __construct(string $country)
    {
        $this->country = $country;
    }

    public static function fromString(string $country): static
    {
        return new static($country);
    }

    public function get(): string
    {
        return $this->country;
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && $this->get() === $other->get();
    }
}
