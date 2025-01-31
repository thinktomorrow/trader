<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\TaxRateProfile;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\Events\TaxRateProfileDeleted;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDouble;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDoubleId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfile;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileRepository;

class TaxRateProfileApplication
{
    private EventDispatcher $eventDispatcher;
    private TaxRateProfileRepository $taxRateProfileRepository;

    public function __construct(EventDispatcher $eventDispatcher, TaxRateProfileRepository $taxRateProfileRepository)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->taxRateProfileRepository = $taxRateProfileRepository;
    }


    public function createTaxRateProfile(CreateTaxRateProfile $command): TaxRateProfileId
    {
        $taxRateProfileId = $this->taxRateProfileRepository->nextReference();

        $taxRateProfile = TaxRateProfile::create($taxRateProfileId);
        $taxRateProfile->updateCountries($command->getCountryIds());
        $taxRateProfile->addData($command->getData());

        $this->taxRateProfileRepository->save($taxRateProfile);

        $this->eventDispatcher->dispatchAll($taxRateProfile->releaseEvents());

        return $taxRateProfileId;
    }

    public function updateTaxRateProfile(UpdateTaxRateProfile $command): void
    {
        $taxRateProfile = $this->taxRateProfileRepository->find($command->getTaxRateProfileId());

        $taxRateProfile->updateCountries($command->getCountryIds());
        $taxRateProfile->addData($command->getData());

        $this->taxRateProfileRepository->save($taxRateProfile);

        $this->eventDispatcher->dispatchAll($taxRateProfile->releaseEvents());
    }

    public function deleteTaxRateProfile(DeleteTaxRateProfile $command): void
    {
        $this->taxRateProfileRepository->delete($command->getTaxRateProfileId());

        $this->eventDispatcher->dispatchAll([
            new TaxRateProfileDeleted($command->getTaxRateProfileId()),
        ]);
    }

    public function createTaxRateDouble(CreateTaxRateDouble $command): TaxRateDoubleId
    {
        $taxRateProfile = $this->taxRateProfileRepository->find($command->getTaxRateProfileId());

        $taxRateProfile->addTaxRateDouble(
            TaxRateDouble::create(
                $taxRateDoubleId = $this->taxRateProfileRepository->nextTaxRateDoubleReference(),
                $taxRateProfile->taxRateProfileId,
                $command->getOriginalRate(),
                $command->getRate(),
            )
        );

        $this->taxRateProfileRepository->save($taxRateProfile);

        $this->eventDispatcher->dispatchAll($taxRateProfile->releaseEvents());

        return $taxRateDoubleId;
    }

    public function updateTaxRateDouble(UpdateTaxRateDouble $command): void
    {
        $taxRateProfile = $this->taxRateProfileRepository->find($command->getTaxRateProfileId());

        $taxRateDouble = $taxRateProfile->findTaxRateDouble($command->getTaxRateDoubleId());

        $taxRateDouble->update(
            $command->getOriginalRate(),
            $command->getRate()
        );

        $this->taxRateProfileRepository->save($taxRateProfile);

        $this->eventDispatcher->dispatchAll($taxRateProfile->releaseEvents());
    }

    public function deleteTaxRateDouble(DeleteTaxRateDouble $command): void
    {
        $taxRateProfile = $this->taxRateProfileRepository->find($command->getTaxRateProfileId());

        $taxRateProfile->deleteTaxRateDouble($command->getTaxRateDoubleId());

        $this->taxRateProfileRepository->save($taxRateProfile);

        $this->eventDispatcher->dispatchAll($taxRateProfile->releaseEvents());
    }
}
