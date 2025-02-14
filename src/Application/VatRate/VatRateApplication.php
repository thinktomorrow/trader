<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Events\VatRateDeleted;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateRepository;

class VatRateApplication
{
    private EventDispatcher $eventDispatcher;
    private VatRateRepository $vatRateRepository;

    public function __construct(EventDispatcher $eventDispatcher, VatRateRepository $vatRateRepository)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->vatRateRepository = $vatRateRepository;
    }

    public function createVatRate(CreateVatRate $command): VatRateId
    {
        $vatRateId = $this->vatRateRepository->nextReference();

        // Is there already a standard vat rate for this country? If not, make this one the standard.
        $isStandard = $this->vatRateRepository->findStandardVatRateForCountry($command->getCountryId()) ? false : true;

        $vatRate = VatRate::create(
            $vatRateId,
            $command->getCountryId(),
            $command->getRate(),
            $isStandard,
        );

        $vatRate->addData($command->getData());

        $this->vatRateRepository->save($vatRate);

        $this->eventDispatcher->dispatchAll($vatRate->releaseEvents());

        return $vatRateId;
    }

    public function updateVatRate(UpdateVatRate $command): void
    {
        $vatRate = $this->vatRateRepository->find($command->getVatRateId());

        $vatRate->updateRate($command->getRate());
        $vatRate->addData($command->getData());

        $this->vatRateRepository->save($vatRate);

        $this->eventDispatcher->dispatchAll($vatRate->releaseEvents());
    }

    public function deleteVatRate(DeleteVatRate $command): void
    {
        $this->vatRateRepository->delete($command->getVatRateId());

        $this->eventDispatcher->dispatchAll([
            new VatRateDeleted($command->getVatRateId()),
        ]);
    }

    public function createBaseRate(CreateBaseRate $command): BaseRateId
    {
        $vatRate = $this->vatRateRepository->find($command->getTargetVatRateId());
        $originVatRate = $this->vatRateRepository->find($command->getOriginVatRateId());

        $vatRate->addBaseRate(
            BaseRate::create(
                $baseRateId = $this->vatRateRepository->nextBaseRateReference(),
                $originVatRate->vatRateId,
                $vatRate->vatRateId,
                $originVatRate->getRate(),
            )
        );

        $this->vatRateRepository->save($vatRate);

        $this->eventDispatcher->dispatchAll($vatRate->releaseEvents());

        return $baseRateId;
    }

    public function deleteBaseRate(DeleteBaseRate $command): void
    {
        $vatRate = $this->vatRateRepository->find($command->getVatRateId());

        $vatRate->deleteBaseRate($command->getBaseRateId());

        $this->vatRateRepository->save($vatRate);

        $this->eventDispatcher->dispatchAll($vatRate->releaseEvents());
    }

    public function changeStandardVatRateForCountry(ChangeStandardVatRateForCountry $command): void
    {
        // Unset existing standard vat rate first
        $standardVatRate = $this->vatRateRepository->findStandardVatRateForCountry($command->getCountryId());
        $standardVatRate->unsetAsStandard();
        $this->vatRateRepository->save($standardVatRate);

        $this->eventDispatcher->dispatchAll($standardVatRate->releaseEvents());

        // Set new standard vat rate
        $vatRate = $this->vatRateRepository->find($command->getVatRateId());
        $vatRate->setAsStandard();
        $this->vatRateRepository->save($vatRate);

        $this->eventDispatcher->dispatchAll($vatRate->releaseEvents());
    }
}
