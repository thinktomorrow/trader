<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\PaymentMethod;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Common\Price\VatApplicableAmount;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Country\HasCountryIds;

class PaymentMethod implements Aggregate
{
    use HasCountryIds;
    use HasData;
    use RecordsEvents;

    public readonly PaymentMethodId $paymentMethodId;

    private PaymentMethodProviderId $paymentMethodProviderId;

    private PaymentMethodState $state;

    private Money $rate;

    private TaxMode $taxMode;

    private function __construct() {}

    public static function create(PaymentMethodId $paymentMethodId, PaymentMethodProviderId $paymentMethodProviderId, Money $rate, TaxMode $taxMode = TaxMode::Exclusive): static
    {
        $method = new static;

        $method->paymentMethodId = $paymentMethodId;
        $method->paymentMethodProviderId = $paymentMethodProviderId;
        $method->state = PaymentMethodState::online;
        $method->rate = $rate;
        $method->taxMode = $taxMode;

        return $method;
    }

    public function updateState(PaymentMethodState $state): void
    {
        $this->state = $state;
    }

    public function getState(): PaymentMethodState
    {
        return $this->state;
    }

    public function updateProvider(PaymentMethodProviderId $paymentMethodProviderId): void
    {
        $this->paymentMethodProviderId = $paymentMethodProviderId;
    }

    public function getProvider(): PaymentMethodProviderId
    {
        return $this->paymentMethodProviderId;
    }

    public function updateRate(Money $rate, TaxMode $taxMode = TaxMode::Exclusive): void
    {
        $this->rate = $rate;
        $this->taxMode = $taxMode;
    }

    public function getRate(): Money
    {
        return $this->rate;
    }

    public function getVatApplicableRate(): VatApplicableAmount
    {
        return VatApplicableAmount::fromMoney($this->rate, $this->taxMode);
    }

    public function getTaxMode(): TaxMode
    {
        return $this->taxMode;
    }

    public function getMappedData(): array
    {
        return [
            'payment_method_id' => $this->paymentMethodId->get(),
            'provider_id' => $this->paymentMethodProviderId->get(),
            'state' => $this->state->value,
            'rate' => $this->rate->getAmount(),
            'tax_mode' => $this->taxMode->value,
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $method = new static;

        $method->paymentMethodId = PaymentMethodId::fromString($state['payment_method_id']);
        $method->paymentMethodProviderId = PaymentMethodProviderId::fromString($state['provider_id']);
        $method->state = PaymentMethodState::from($state['state']);
        $method->rate = Cash::make($state['rate']);
        $method->taxMode = isset($state['tax_mode']) ? TaxMode::from($state['tax_mode']) : TaxMode::Exclusive;
        $method->data = json_decode($state['data'], true);
        $method->countryIds = array_map(fn ($countryState) => CountryId::fromString($countryState['country_id']), $childEntities[CountryId::class]);

        return $method;
    }

    public function getChildEntities(): array
    {
        return [
            CountryId::class => array_map(fn (CountryId $countryId) => $countryId->get(), $this->countryIds),
        ];
    }
}
