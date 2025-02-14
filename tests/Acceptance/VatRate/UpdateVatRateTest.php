<?php
declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Thinktomorrow\Trader\Application\VatRate\CreateVatRate;
use Thinktomorrow\Trader\Application\VatRate\CreateBaseRate;
use Thinktomorrow\Trader\Application\VatRate\UpdateTaxRateDouble;
use Thinktomorrow\Trader\Application\VatRate\UpdateVatRate;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class UpdateVatRateTest extends VatRateContext
{
    public function test_it_can_update_a_profile()
    {
        $taxRateProfileId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $this->vatRateApplication->updateVatRate(new UpdateVatRate(
            $taxRateProfileId->get(),
            ['BE'],
            ['foo' => 'baz']
        ));

        $taxRateProfile = $this->vatRateRepository->find($taxRateProfileId);

        $this->assertEquals([
            CountryId::fromString('BE'),
        ], $taxRateProfile->getCountryIds());
        $this->assertEquals(['foo' => 'baz'], $taxRateProfile->getData());
    }

    public function test_it_can_update_a_double()
    {
        $taxRateProfileId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $taxRateDoubleId = $this->vatRateApplication->createBaseRate(new CreateBaseRate($taxRateProfileId->get(), '21', '10'));

        $this->vatRateApplication->updateBaseRate(new UpdateTaxRateDouble($taxRateDoubleId->get(), $taxRateProfileId->get(), '60', '20'));

        $double = $this->vatRateRepository->find($taxRateProfileId)->findBaseRate($taxRateDoubleId);

        $this->assertFalse($double->hasOriginalRate(TaxRate::fromString('21')));
        $this->assertTrue($double->hasOriginalRate(TaxRate::fromString('60')));
        $this->assertEquals(TaxRate::fromString('20'), $double->getTargetRate());
    }
}
