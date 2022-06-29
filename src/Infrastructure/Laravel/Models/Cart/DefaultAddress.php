<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Common\RendersData;

abstract class DefaultAddress
{
    use RendersData;

    protected ?string $country;
    protected ?string $line1;
    protected ?string $line2;
    protected ?string $postalCode;
    protected ?string $city;

    public static function fromMappedData(array $state, array $cartState): static
    {
        $address = new static();

        $address->country = $state['country'];
        $address->line1 = $state['line_1'];
        $address->line2 = $state['line_2'];
        $address->postalCode = $state['postal_code'];
        $address->city = $state['city'];
        $address->data = json_decode($state['data'], true);

        return $address;
    }

    public function getCountry(): ?string
    {
        return $this->country;
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

    public function getTitle(): ?string
    {
        return $this->data('title');
    }

    public function getDescription(): ?string
    {
        return $this->data('description');
    }
}
