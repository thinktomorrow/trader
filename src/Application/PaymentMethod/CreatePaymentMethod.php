<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\PaymentMethod;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class CreatePaymentMethod
{
    private string $rate;
    private array $data;
    private array $countryIds;

    public function __construct(string $rate, array $countryIds, array $data)
    {
        $this->rate = $rate;
        $this->data = $data;
        $this->countryIds = $countryIds;
    }

    public function getRate(): Money
    {
        return Cash::make($this->rate);
    }

    public function getCountryIds(): array
    {
        return array_map(fn ($country) => CountryId::fromString($country), $this->countryIds);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
