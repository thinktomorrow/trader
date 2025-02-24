<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodProviderId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState;

class PaymentMethodTest extends TestCase
{
    public function test_it_can_create_an_payment_method_entity()
    {
        $paymentMethod = PaymentMethod::create(
            $paymentMethodId = PaymentMethodId::fromString('xxx'),
            $paymentMethodProviderId = PaymentMethodProviderId::fromString('mollie'),
            Money::EUR(10),
        );

        $this->assertEquals([
            'payment_method_id' => $paymentMethodId->get(),
            'provider_id' => $paymentMethodProviderId->get(),
            'state' => PaymentMethodState::online->value,
            'rate' => '10',
            'data' => "[]",
        ], $paymentMethod->getMappedData());
    }

    public function test_it_can_update_rate()
    {
        $paymentMethod = $this->createPaymentMethod();

        $paymentMethod->updateRate(Money::EUR(30));

        $this->assertEquals(Money::EUR(30), $paymentMethod->getRate());
    }

    public function test_it_can_update_provider()
    {
        $paymentMethod = $this->createPaymentMethod();

        $paymentMethod->updateProvider($updatedProvider = PaymentMethodProviderId::fromString('updated-provider'));

        $this->assertEquals($updatedProvider, $paymentMethod->getProvider());
    }

    public function test_adding_data_merges_with_existing_data()
    {
        $paymentMethod = $this->createPaymentMethod();

        $paymentMethod->addData(['bar' => 'baz']);
        $paymentMethod->addData(['foo' => 'bar', 'bar' => 'boo']);

        $this->assertEquals(json_encode(['bar' => 'boo', 'foo' => 'bar']), $paymentMethod->getMappedData()['data']);
    }

    public function test_it_can_delete_data()
    {
        $paymentMethod = $this->createPaymentMethod();

        $paymentMethod->addData(['foo' => 'bar', 'bar' => 'boo']);
        $paymentMethod->deleteData('bar');

        $this->assertEquals(json_encode(['foo' => 'bar']), $paymentMethod->getMappedData()['data']);
    }

    public function test_it_can_update_countries()
    {
        $paymentMethod = $this->createPaymentMethod();

        $countries = [
            CountryId::fromString('FR'),
            CountryId::fromString('NL'),
        ];

        $paymentMethod->updateCountries($countries);

        $this->assertCount(2, $paymentMethod->getCountryIds());
        $this->assertCount(2, $paymentMethod->getChildEntities()[CountryId::class]);
        $this->assertEquals($countries, $paymentMethod->getCountryIds());

        $this->assertTrue($paymentMethod->hasCountry(CountryId::fromString('FR')));
        $this->assertTrue($paymentMethod->hasCountry(CountryId::fromString('NL')));
        $this->assertFalse($paymentMethod->hasCountry(CountryId::fromString('BE')));
    }

    public function test_it_can_add_country()
    {
        $paymentMethod = $this->createPaymentMethod();

        $paymentMethod->addCountry(CountryId::fromString('FR'));

        $this->assertCount(1, $paymentMethod->getCountryIds());
        $this->assertEquals([
            CountryId::fromString('FR'),
        ], $paymentMethod->getCountryIds());
    }

    public function test_it_can_delete_country()
    {
        $paymentMethod = $this->createPaymentMethod();
        $paymentMethod->addCountry(CountryId::fromString('BE'));
        $paymentMethod->addCountry(CountryId::fromString('NL'));
        $paymentMethod->deleteCountry(CountryId::fromString('BE'));

        $this->assertCount(1, $paymentMethod->getCountryIds());
        $this->assertEquals([
            CountryId::fromString('NL'),
        ], $paymentMethod->getCountryIds());
    }
}
