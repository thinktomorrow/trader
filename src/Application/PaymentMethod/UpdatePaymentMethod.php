<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\PaymentMethod;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;

class UpdatePaymentMethod
{
    private string $paymentMethodId;
    private string $rate;
    private array $countryIds;
    private array $data;

    public function __construct(string $paymentMethodId, string $rate, array $countryIds, array $data)
    {
        $this->paymentMethodId = $paymentMethodId;
        $this->rate = $rate;
        $this->countryIds = $countryIds;
        $this->data = $data;
    }

    public function getPaymentMethodId(): PaymentMethodId
    {
        return PaymentMethodId::fromString($this->paymentMethodId);
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
