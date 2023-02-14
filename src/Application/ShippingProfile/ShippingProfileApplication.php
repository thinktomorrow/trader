<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Events\ShippingProfileDeleted;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;

class ShippingProfileApplication
{
    private EventDispatcher $eventDispatcher;
    private ShippingProfileRepository $shippingProfileRepository;

    public function __construct(EventDispatcher $eventDispatcher, ShippingProfileRepository $shippingProfileRepository)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->shippingProfileRepository = $shippingProfileRepository;
    }


    public function createShippingProfile(CreateShippingProfile $command): ShippingProfileId
    {
        $shippingProfileId = $this->shippingProfileRepository->nextReference();

        $shippingProfile = ShippingProfile::create($shippingProfileId, $command->getProviderId(), $command->requiresAddress());
        $shippingProfile->updateCountries($command->getCountryIds());
        $shippingProfile->addData($command->getData());

        $this->shippingProfileRepository->save($shippingProfile);

        $this->eventDispatcher->dispatchAll($shippingProfile->releaseEvents());

        return $shippingProfileId;
    }

    public function updateShippingProfile(UpdateShippingProfile $command): void
    {
        $shippingProfile = $this->shippingProfileRepository->find($command->getShippingProfileId());

        $shippingProfile->updateProvider($command->getProviderId());
        $shippingProfile->updateRequiresAddress($command->requiresAddress());
        $shippingProfile->updateCountries($command->getCountryIds());
        $shippingProfile->addData($command->getData());

        $this->shippingProfileRepository->save($shippingProfile);

        $this->eventDispatcher->dispatchAll($shippingProfile->releaseEvents());
    }

    public function deleteShippingProfile(DeleteShippingProfile $command): void
    {
        $this->shippingProfileRepository->delete($command->getShippingProfileId());

        $this->eventDispatcher->dispatchAll([
            new ShippingProfileDeleted($command->getShippingProfileId()),
        ]);
    }

    public function createTariff(CreateTariff $command): TariffId
    {
        $shippingProfile = $this->shippingProfileRepository->find($command->getShippingProfileId());

        $shippingProfile->addTariff(
            Tariff::create(
                $tariffId = $this->shippingProfileRepository->nextTariffReference(),
                $shippingProfile->shippingProfileId,
                $command->getRate(),
                $command->getFrom(),
                $command->getTo()
            )
        );

        $this->shippingProfileRepository->save($shippingProfile);

        $this->eventDispatcher->dispatchAll($shippingProfile->releaseEvents());

        return $tariffId;
    }

    public function updateTariff(UpdateTariff $command): void
    {
        $shippingProfile = $this->shippingProfileRepository->find($command->getShippingProfileId());

        $tariff = $shippingProfile->findTariff($command->getTariffId());

        $tariff->update(
            $command->getRate(),
            $command->getFrom(),
            $command->getTo()
        );

        $this->shippingProfileRepository->save($shippingProfile);

        $this->eventDispatcher->dispatchAll($shippingProfile->releaseEvents());
    }

    public function deleteTariff(DeleteTariff $command): void
    {
        $shippingProfile = $this->shippingProfileRepository->find($command->getShippingProfileId());

        $shippingProfile->deleteTariff($command->getTariffId());

        $this->shippingProfileRepository->save($shippingProfile);

        $this->eventDispatcher->dispatchAll($shippingProfile->releaseEvents());
    }
}
