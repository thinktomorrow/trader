<?php
declare(strict_types=1);

namespace Tests\Acceptance\TaxRateProfile;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\VatRate\CreateVatRateMapping;
use Thinktomorrow\Trader\Application\VatRate\CreateVatRate;
use Thinktomorrow\Trader\Application\VatRate\DeleteVatRateMapping;
use Thinktomorrow\Trader\Application\VatRate\DeleteVatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\Events\BaseVatRateDeleted;
use Thinktomorrow\Trader\Domain\Model\VatRate\Events\VatRateDeleted;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\CouldNotFindVatRate;

class DeleteTaxRateProfileTest extends TaxRateProfileContext
{
    use TestHelpers;

    public function test_it_can_delete_a_profile()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createVatRate(new CreateVatRate(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $this->taxRateProfileApplication->deleteVatRate(new DeleteVatRate($taxRateProfileId->get()));

        $this->assertEquals([
            new VatRateDeleted($taxRateProfileId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $this->expectException(CouldNotFindVatRate::class);
        $this->taxRateProfileRepository->find($taxRateProfileId);
    }

    public function test_it_can_delete_a_double()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createVatRate(new CreateVatRate(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $taxRateDoubleId = $this->taxRateProfileApplication->createTaxRateDouble(new CreateVatRateMapping($taxRateProfileId->get(), '21', '10'));

        $this->taxRateProfileApplication->deleteTaxRateDouble(new DeleteVatRateMapping($taxRateProfileId->get(), $taxRateDoubleId->get()));

        $this->assertEquals([
            new BaseVatRateDeleted($taxRateProfileId, $taxRateDoubleId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $taxRateProfile = $this->taxRateProfileRepository->find($taxRateProfileId);
        $this->assertCount(0, $taxRateProfile->getBaseRates());
    }
}
