<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateMapping;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateMappingId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateState;

class TaxRateProfileTest extends TestCase
{
    public function test_it_can_create_a_taxrate_profile()
    {
        $taxRateProfile = VatRate::create(
            $taxRateProfileId = VatRateId::fromString('yyy'),
        );

        $this->assertEquals([
            'taxrate_profile_id' => $taxRateProfileId->get(),
            'state' => VatRateState::online->value,
            'data' => "[]",
        ], $taxRateProfile->getMappedData());

        $this->assertEquals([
            VatRateMapping::class => [],
            CountryId::class => [],
        ], $taxRateProfile->getChildEntities());
    }

    public function test_it_can_be_build_from_raw_data()
    {
        $taxRateProfile = $this->createdTaxRateProfile();

        $this->assertEquals(VatRateId::fromString('yyy'), $taxRateProfile->taxRateProfileId);
        $this->assertEquals(VatRateState::offline, $taxRateProfile->getState());
        $this->assertEquals('bar', $taxRateProfile->getData('foo'));
        $this->assertCount(2, $taxRateProfile->getChildEntities()[VatRateMapping::class]);
        $this->assertEquals([
            'taxrate_profile_id' => 'yyy',
            'taxrate_double_id' => 'xxx',
            'original_rate' => '21',
            'rate' => '10',
        ], $taxRateProfile->getChildEntities()[VatRateMapping::class][0]);

        $this->assertCount(2, $taxRateProfile->getChildEntities()[CountryId::class]);
    }

    public function test_it_can_add_a_double()
    {
        $taxRateProfile = $this->createdTaxRateProfile();

        $taxRateProfile->addBaseRate(
            VatRateMapping::create(
                VatRateMappingId::fromString('xxx'),
                $taxRateProfile->taxRateProfileId,
                TaxRate::fromString('21'),
                TaxRate::fromString('10')
            )
        );

        $this->assertCount(3, $taxRateProfile->getChildEntities()[VatRateMapping::class]);
    }

    public function test_it_can_update_countries()
    {
        $taxRateProfile = $this->createdTaxRateProfile();

        $countries = [
            CountryId::fromString('FR'),
            CountryId::fromString('NL'),
        ];

        $taxRateProfile->updateCountries($countries);

        $this->assertCount(2, $taxRateProfile->getCountryIds());
        $this->assertCount(2, $taxRateProfile->getChildEntities()[CountryId::class]);
        $this->assertEquals($countries, $taxRateProfile->getCountryIds());

        $this->assertTrue($taxRateProfile->hasCountry(CountryId::fromString('FR')));
        $this->assertTrue($taxRateProfile->hasCountry(CountryId::fromString('NL')));
        $this->assertFalse($taxRateProfile->hasCountry(CountryId::fromString('BE')));
    }

    public function test_it_can_add_country()
    {
        $taxRateProfile = $this->createdTaxRateProfile();

        $taxRateProfile->addCountry(CountryId::fromString('FR'));

        $this->assertCount(3, $taxRateProfile->getCountryIds());
        $this->assertEquals([
            CountryId::fromString('BE'),
            CountryId::fromString('NL'),
            CountryId::fromString('FR'),
        ], $taxRateProfile->getCountryIds());
    }

    public function test_it_can_delete_country()
    {
        $taxRateProfile = $this->createdTaxRateProfile();

        $taxRateProfile->deleteCountry(CountryId::fromString('BE'));

        $this->assertCount(1, $taxRateProfile->getCountryIds());
        $this->assertEquals([
            CountryId::fromString('NL'),
        ], $taxRateProfile->getCountryIds());
    }

    public function test_it_can_check_if_double_applies_for_a_rate()
    {
        $double = VatRateMapping::create(VatRateMappingId::fromString('xxx'), VatRateId::fromString('yyy'), TaxRate::fromString('21'), TaxRate::fromString('10'));
        $this->assertTrue($double->hasOriginalRate(TaxRate::fromString('21')));
        $this->assertFalse($double->hasOriginalRate(TaxRate::fromString('10')));
    }

    private function createdTaxRateProfile(): VatRate
    {
        return VatRate::fromMappedData([
            'taxrate_profile_id' => 'yyy',
            'state' => VatRateState::offline->value,
            'data' => json_encode(['foo' => 'bar']),
        ], [
            VatRateMapping::class => [
                [
                    'taxrate_double_id' => 'xxx',
                    'original_rate' => '21',
                    'rate' => '10',
                ],
                [
                    'taxrate_double_id' => 'yyy',
                    'original_rate' => '6',
                    'rate' => '12',
                ],
            ],
            CountryId::class => [
                ['country_id' => 'BE'],
                ['country_id' => 'NL'],
            ],
        ]);
    }
}
