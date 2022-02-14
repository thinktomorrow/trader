<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Address;

trait Country
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
}
