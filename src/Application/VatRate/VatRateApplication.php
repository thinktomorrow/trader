<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\VatRate\Events\VatRateDeleted;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateMapping;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateMappingId;
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
        $vatRate->updateCountry($command->getCountryId());
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

    public function createTaxRateDouble(CreateVatRateMapping $command): VatRateMappingId
    {
        $vatRate = $this->vatRateRepository->find($command->getVatRateId());

        $vatRate->addBaseRate(
            VatRateMapping::create(
                $taxRateDoubleId = $this->vatRateRepository->nextVatRateMappingReference(),
                $vatRate->vatRateId,
                $command->getOriginalRate(),
                $command->getRate(),
            )
        );

        $this->vatRateRepository->save($vatRate);

        $this->eventDispatcher->dispatchAll($vatRate->releaseEvents());

        return $taxRateDoubleId;
    }

    public function updateTaxRateDouble(UpdateTaxRateDouble $command): void
    {
        $vatRate = $this->vatRateRepository->find($command->getVatRateId());

        $taxRateDouble = $vatRate->findBaseRate($command->getTaxRateDoubleId());

        $taxRateDouble->update(
            $command->getOriginalRate(),
            $command->getRate()
        );

        $this->vatRateRepository->save($vatRate);

        $this->eventDispatcher->dispatchAll($vatRate->releaseEvents());
    }

    public function deleteTaxRateDouble(DeleteVatRateMapping $command): void
    {
        $vatRate = $this->vatRateRepository->find($command->getVatRateId());

        $vatRate->deleteBaseRate($command->getTaxRateDoubleId());

        $this->vatRateRepository->save($vatRate);

        $this->eventDispatcher->dispatchAll($vatRate->releaseEvents());
    }
}
