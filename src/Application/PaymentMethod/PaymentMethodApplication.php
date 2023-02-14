<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\PaymentMethod;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Events\PaymentMethodDeleted;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;

class PaymentMethodApplication
{
    private EventDispatcher $eventDispatcher;
    private PaymentMethodRepository $paymentMethodRepository;

    public function __construct(EventDispatcher $eventDispatcher, PaymentMethodRepository $paymentMethodRepository)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }


    public function createPaymentMethod(CreatePaymentMethod $command): PaymentMethodId
    {
        $paymentMethodId = $this->paymentMethodRepository->nextReference();

        $paymentMethod = PaymentMethod::create($paymentMethodId, $command->getProviderId(), $command->getRate());
        $paymentMethod->updateCountries($command->getCountryIds());
        $paymentMethod->addData($command->getData());

        $this->paymentMethodRepository->save($paymentMethod);

        $this->eventDispatcher->dispatchAll($paymentMethod->releaseEvents());

        return $paymentMethodId;
    }

    public function updatePaymentMethod(UpdatePaymentMethod $command): void
    {
        $paymentMethod = $this->paymentMethodRepository->find($command->getPaymentMethodId());

        $paymentMethod->updateProvider($command->getProviderId());
        $paymentMethod->updateRate($command->getRate());
        $paymentMethod->updateCountries($command->getCountryIds());
        $paymentMethod->addData($command->getData());

        $this->paymentMethodRepository->save($paymentMethod);

        $this->eventDispatcher->dispatchAll($paymentMethod->releaseEvents());
    }

    public function deletePaymentMethod(DeletePaymentMethod $command): void
    {
        $this->paymentMethodRepository->delete($command->getPaymentMethodId());

        $this->eventDispatcher->dispatchAll([
            new PaymentMethodDeleted($command->getPaymentMethodId()),
        ]);
    }
}
