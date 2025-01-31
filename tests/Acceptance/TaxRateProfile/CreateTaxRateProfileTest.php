<?php
declare(strict_types=1);

namespace Tests\Acceptance\TaxRateProfile;

use Thinktomorrow\Trader\Application\TaxRateProfile\CreateTaxRateDouble;
use Thinktomorrow\Trader\Application\TaxRateProfile\CreateTaxRateProfile;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDouble;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDoubleId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;

class CreateTaxRateProfileTest extends TaxRateProfileContext
{
    public function test_it_can_create_a_taxrate_profile()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createTaxRateProfile(new CreateTaxRateProfile(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $taxRateProfile = $this->taxRateProfileRepository->find($taxRateProfileId);

        $this->assertInstanceOf(TaxRateProfileId::class, $taxRateProfileId);
        $this->assertEquals($taxRateProfileId, $taxRateProfile->taxRateProfileId);
        $this->assertEquals([
            CountryId::fromString('BE'),
            CountryId::fromString('NL'),
        ], $taxRateProfile->getCountryIds());
        $this->assertEquals(['foo' => 'bar'], $taxRateProfile->getData());
    }

    public function test_it_can_create_a_rate_double()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createTaxRateProfile(new CreateTaxRateProfile(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $doubleId = $this->taxRateProfileApplication->createTaxRateDouble(new CreateTaxRateDouble($taxRateProfileId->get(), '21', '10'));

        $this->assertInstanceOf(TaxRateDoubleId::class, $doubleId);
        $this->assertInstanceOf(TaxRateDouble::class, $double = $this->taxRateProfileRepository->find($taxRateProfileId)->findTaxRateDouble($doubleId));
        $this->assertTrue($double->hasOriginalRate(TaxRate::fromString('21')));
        $this->assertEquals(TaxRate::fromString('10'), $double->getRate());
    }
}
