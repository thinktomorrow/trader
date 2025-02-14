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

    public function test_it_can_delete_a_vat_rate()
    {
        $vatRateId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            'BE', '21', ['foo' => 'bar']
        ));

        $this->vatRateApplication->deleteVatRate(new DeleteVatRate($vatRateId->get()));

        $this->assertEquals([
            new VatRateDeleted($vatRateId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $this->expectException(CouldNotFindVatRate::class);
        $this->vatRateRepository->find($vatRateId);
    }

    public function test_it_can_delete_a_base_rate()
    {
        [
            'originVatRateId' => $originVatRateId,
            'targetVatRateId' => $targetVatRateId,
            'baseRateId' => $baseRateId
        ] = $this->createBaseRateStub();

        $this->vatRateApplication->deleteBaseRate(new DeleteBaseRate($baseRateId->get(), $targetVatRateId->get()));

        $this->assertEquals([
            new BaseRateDeleted($baseRateId, $targetVatRateId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $vatRate = $this->vatRateRepository->find($targetVatRateId);
        $this->assertCount(0, $vatRate->getBaseRates());
    }
}
