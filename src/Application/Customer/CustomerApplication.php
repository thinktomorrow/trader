<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\Events\CustomerDeleted;

class CustomerApplication
{
    private CustomerRepository $customerRepository;
    private EventDispatcher $eventDispatcher;

    public function __construct(CustomerRepository $customerRepository, EventDispatcher $eventDispatcher)
    {
        $this->customerRepository = $customerRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function registerCustomer(RegisterCustomer $command): CustomerId
    {
        if ($this->customerRepository->existsByEmail($command->getEmail())) {
            throw new \InvalidArgumentException('Registration failed. A customer with email ' . $command->getEmail()->get() . ' already exists.');
        }

        $customer = Customer::create(
            $this->customerRepository->nextReference(),
            $command->getEmail(),
            $command->isBusiness(),
            $command->getLocale()
        );

        $customer->addData($command->getData());

        $this->customerRepository->save($customer);

        $this->eventDispatcher->dispatchAll($customer->releaseEvents());

        return $customer->customerId;
    }

    public function updateData(UpdateData $command): void
    {
        $customer = $this->customerRepository->find($command->getCustomerId());

        $customer->addData($command->getData());

        $this->customerRepository->save($customer);

        $this->eventDispatcher->dispatchAll($customer->releaseEvents());
    }

    public function updateEmail(UpdateEmail $command): void
    {
        if ($this->customerRepository->existsByEmail($command->getNewEmail(), $command->getCustomerId())) {
            throw new \InvalidArgumentException('Email update failed. A customer with email ' . $command->getNewEmail()->get() . ' already exists.');
        }

        $customer = $this->customerRepository->find($command->getCustomerId());

        if (! $customer->getEmail()->equals($command->getOldEmail())) {
            throw new \InvalidArgumentException('Email update constraint: Email [' . $command->getOldEmail()->get() . '] does not belong to customer with id [' . $customer->customerId->get() . '].');
        }

        $customer->updateEmail($command->getNewEmail());

        $this->customerRepository->save($customer);

        $this->eventDispatcher->dispatchAll($customer->releaseEvents());
    }

    public function updateLocale(UpdateLocale $command): void
    {
        $customer = $this->customerRepository->find($command->getCustomerId());

        $customer->updateLocale($command->getLocale());

        $this->customerRepository->save($customer);

        $this->eventDispatcher->dispatchAll($customer->releaseEvents());
    }

    public function updateBillingAddress(UpdateBillingAddress $command): void
    {
        $customer = $this->customerRepository->find($command->getCustomerId());

        $customer->updateBillingAddress(
            BillingAddress::create(
                $customer->customerId,
                $command->getAddress(),
                []
            )
        );

        $this->customerRepository->save($customer);

        $this->eventDispatcher->dispatchAll($customer->releaseEvents());
    }

    public function updateShippingAddress(UpdateShippingAddress $command): void
    {
        $customer = $this->customerRepository->find($command->getCustomerId());

        $customer->updateShippingAddress(
            ShippingAddress::create(
                $customer->customerId,
                $command->getAddress(),
                []
            )
        );

        $this->customerRepository->save($customer);

        $this->eventDispatcher->dispatchAll($customer->releaseEvents());
    }

    public function deleteCustomer(DeleteCustomer $command): void
    {
        $customer = $this->customerRepository->find($command->getCustomerId());

        $this->customerRepository->delete($customer->customerId);

        $this->eventDispatcher->dispatchAll([
            new CustomerDeleted($command->getCustomerId(), $customer->getEmail()),
        ]);
    }
}
