<?php
declare(strict_types=1);

namespace Tests\Acceptance\TaxRateProfile;

use Thinktomorrow\Trader\Application\TaxRateProfile\CreateTaxRateDouble;
use Thinktomorrow\Trader\Application\TaxRateProfile\CreateTaxRateProfile;
use Thinktomorrow\Trader\Application\TaxRateProfile\UpdateTaxRateDouble;
use Thinktomorrow\Trader\Application\TaxRateProfile\UpdateTaxRateProfile;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class UpdateTaxRateProfileTest extends TaxRateProfileContext
{
    public function test_it_can_update_a_profile()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createTaxRateProfile(new CreateTaxRateProfile(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $this->taxRateProfileApplication->updateTaxRateProfile(new UpdateTaxRateProfile(
            $taxRateProfileId->get(),
            ['BE'],
            ['foo' => 'baz']
        ));

        $taxRateProfile = $this->taxRateProfileRepository->find($taxRateProfileId);

        $this->assertEquals([
            CountryId::fromString('BE'),
        ], $taxRateProfile->getCountryIds());
        $this->assertEquals(['foo' => 'baz'], $taxRateProfile->getData());
    }

    public function test_it_can_update_a_double()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createTaxRateProfile(new CreateTaxRateProfile(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $taxRateDoubleId = $this->taxRateProfileApplication->createTaxRateDouble(new CreateTaxRateDouble($taxRateProfileId->get(), '21', '10'));

        $this->taxRateProfileApplication->updateTaxRateDouble(new UpdateTaxRateDouble($taxRateDoubleId->get(), $taxRateProfileId->get(), '60', '20'));

        $double = $this->taxRateProfileRepository->find($taxRateProfileId)->findTaxRateDouble($taxRateDoubleId);

        $this->assertFalse($double->hasOriginalRate(TaxRate::fromString('21')));
        $this->assertTrue($double->hasOriginalRate(TaxRate::fromString('60')));
        $this->assertEquals(TaxRate::fromString('20'), $double->getRate());
    }
}
