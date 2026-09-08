<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\PaymentMethod;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodProviderId;

class UpdatePaymentMethod
{
    private string $paymentMethodId;

    private string $providerId;

    private string $rate;

    private array $countryIds;

    private array $data;

    public function __construct(string $paymentMethodId, string $providerId, string $rate, array $countryIds, array $data, private ?string $taxMode = null)
    {
        $this->paymentMethodId = $paymentMethodId;
        $this->providerId = $providerId;
        $this->rate = $rate;
        $this->countryIds = $countryIds;
        $this->data = $data;
    }

    public function getTaxMode(): ?TaxMode
    {
        return $this->taxMode ? TaxMode::from($this->taxMode) : null;
    }

    public function getPaymentMethodId(): PaymentMethodId
    {
        return PaymentMethodId::fromString($this->paymentMethodId);
    }

    public function getProviderId(): PaymentMethodProviderId
    {
        return PaymentMethodProviderId::fromString($this->providerId);
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
