<?php
declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\VatRate\CreateVatRate;
use Thinktomorrow\Trader\Application\VatRate\CreateBaseRate;
use Thinktomorrow\Trader\Application\VatRate\DeleteVatRate;
use Thinktomorrow\Trader\Application\VatRate\DeleteBaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\Events\BaseRateDeleted;
use Thinktomorrow\Trader\Domain\Model\VatRate\Events\VatRateDeleted;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\CouldNotFindVatRate;

class DeleteVatRateTest extends VatRateContext
{
    use TestHelpers;

    public function test_it_can_delete_a_profile()
    {
        $taxRateProfileId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $this->vatRateApplication->deleteVatRate(new DeleteVatRate($taxRateProfileId->get()));

        $this->assertEquals([
            new VatRateDeleted($taxRateProfileId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $this->expectException(CouldNotFindVatRate::class);
        $this->vatRateRepository->find($taxRateProfileId);
    }

    public function test_it_can_delete_a_double()
    {
        $taxRateProfileId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $taxRateDoubleId = $this->vatRateApplication->createBaseRate(new CreateBaseRate($taxRateProfileId->get(), '21', '10'));

        $this->vatRateApplication->deleteBaseRate(new DeleteBaseRate($taxRateProfileId->get(), $taxRateDoubleId->get()));

        $this->assertEquals([
            new BaseRateDeleted($taxRateProfileId, $taxRateDoubleId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $taxRateProfile = $this->vatRateRepository->find($taxRateProfileId);
        $this->assertCount(0, $taxRateProfile->getBaseRates());
    }
}
