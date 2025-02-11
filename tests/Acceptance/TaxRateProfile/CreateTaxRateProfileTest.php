<?php
declare(strict_types=1);

namespace Tests\Acceptance\TaxRateProfile;

use Thinktomorrow\Trader\Application\VatRate\CreateVatRateMapping;
use Thinktomorrow\Trader\Application\VatRate\CreateVatRate;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateMapping;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateMappingId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class CreateTaxRateProfileTest extends TaxRateProfileContext
{
    public function test_it_can_create_a_taxrate_profile()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createVatRate(new CreateVatRate(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $taxRateProfile = $this->taxRateProfileRepository->find($taxRateProfileId);

        $this->assertInstanceOf(VatRateId::class, $taxRateProfileId);
        $this->assertEquals($taxRateProfileId, $taxRateProfile->taxRateProfileId);
        $this->assertEquals([
            CountryId::fromString('BE'),
            CountryId::fromString('NL'),
        ], $taxRateProfile->getCountryIds());
        $this->assertEquals(['foo' => 'bar'], $taxRateProfile->getData());
    }

    public function test_it_can_create_a_rate_double()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createVatRate(new CreateVatRate(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $doubleId = $this->taxRateProfileApplication->createTaxRateDouble(new CreateVatRateMapping($taxRateProfileId->get(), '21', '10'));

        $this->assertInstanceOf(VatRateMappingId::class, $doubleId);
        $this->assertInstanceOf(VatRateMapping::class, $double = $this->taxRateProfileRepository->find($taxRateProfileId)->findBaseRate($doubleId));
        $this->assertTrue($double->hasOriginalRate(TaxRate::fromString('21')));
        $this->assertEquals(TaxRate::fromString('10'), $double->getTargetRate());
    }
}
