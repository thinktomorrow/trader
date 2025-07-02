<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Merchant;

use Thinktomorrow\Trader\Application\VatNumber\ValidateVatNumber;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberApplication;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidation;
use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Events\Merchant\BillingAddressUpdatedByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\Merchant\ShippingAddressUpdatedByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\Merchant\ShopperUpdatedByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEvent;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;

class MerchantOrderApplication
{
    private OrderRepository $orderRepository;
    private EventDispatcher $eventDispatcher;
    private VatNumberApplication $vatNumberApplication;

    public function __construct(OrderRepository $orderRepository, EventDispatcher $eventDispatcher, VatNumberApplication $vatNumberApplication)
    {
        $this->orderRepository = $orderRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->vatNumberApplication = $vatNumberApplication;
    }

    public function addLogEntry(AddLogEntry $command): void
    {
        $order = $this->orderRepository->find($command->getOrderId());

        $order->addLogEntry(OrderEvent::create(
            $this->orderRepository->nextLogEntryReference(),
            $command->getEvent(),
            new \DateTime(),
            $command->getData(),
        ));

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateShippingData(UpdateShippingData $command): void
    {
        $order = $this->orderRepository->find($command->getOrderId());

        $shipping = $order->findShipping($command->getShippingId());
        $shipping->addData($command->getData());

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateShopper(UpdateShopper $command, array $contextData): void
    {
        $order = $this->orderRepository->find($command->getOrderId());
        $shopper = $order->getShopper();

        $updatedShopperValues = $this->extractUpdatedShopperValues($command, $shopper);

        if (count($updatedShopperValues) < 1) {
            return;
        }

        $shopper->updateEmail($command->getEmail());
        $shopper->updateBusiness($command->isBusiness());
        $shopper->updateLocale($command->getLocale());
        $shopper->addData($command->getData());

        $order->updateShopper($shopper);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll([...$order->releaseEvents(), new ShopperUpdatedByMerchant($order->orderId, $updatedShopperValues, $contextData)]);
    }

    public function verifyVatNumber(VerifyVatNumber $command): void
    {
        $order = $this->orderRepository->find($command->getOrderId());
        $shopper = $order->getShopper();

        $billingAddressCountryId = $order->getBillingAddress()?->getAddress()->countryId;

        if (! $billingAddressCountryId) {
            throw new \Exception('No billing address found for order ' . $order->orderId);
        }

        try {

            $vatNumberValidation = $this->vatNumberApplication->validate(new ValidateVatNumber(
                $billingAddressCountryId->get(),
                $command->getVatNumber()
            ));

            $this->vatNumberApplication->addVatNumberValidationToShopper($order->getShopper(), $vatNumberValidation);

        } catch (\Exception $e) {
            $this->vatNumberApplication->addVatNumberValidationToShopper(
                $order->getShopper(),
                VatNumberValidation::fromException($billingAddressCountryId->get(), $command->getVatNumber(), $e)
            );
        }

        $order->updateShopper($shopper);
        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateShippingAddress(UpdateShippingAddress $command, array $contextData): void
    {
        $order = $this->orderRepository->find($command->getOrderId());
        $shippingAddress = $order->getShippingAddress();

        $updatedAddressValues = $this->extractUpdatedAddressValues($command, $shippingAddress->getAddress());

        if (count($updatedAddressValues) < 1) {
            return;
        }

        // Get existing address_id, if not we create one here
        $order->updateShippingAddress(ShippingAddress::create(
            $order->orderId,
            $command->getAddress(),
            []
        ));

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll([...$order->releaseEvents(), new ShippingAddressUpdatedByMerchant($order->orderId, $updatedAddressValues, $contextData)]);
    }

    public function updateBillingAddress(UpdateBillingAddress $command, array $contextData): void
    {
        $order = $this->orderRepository->find($command->getOrderId());

        $billingAddress = $order->getBillingAddress();

        $updatedAddressValues = $this->extractUpdatedAddressValues($command, $billingAddress->getAddress());
        if (count($updatedAddressValues) < 1) {
            return;
        }

        // Get existing address_id, if not we create one here
        $order->updateBillingAddress(BillingAddress::create(
            $order->orderId,
            $command->getAddress(),
            []
        ));

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll([...$order->releaseEvents(), new BillingAddressUpdatedByMerchant($order->orderId, $updatedAddressValues, $contextData)]);
    }

    private function extractUpdatedShopperValues(UpdateShopper $command, Shopper $shopper): array
    {
        $updatedValues = [];

        if (! $command->getEmail()->equals($shopper->getEmail())) {
            $updatedValues['email'] = [
                'old' => $shopper->getEmail()->get(),
                'new' => $command->getEmail()->get(),
            ];
        }

        if (! $command->getLocale()->equals($shopper->getLocale())) {
            $updatedValues['locale'] = [
                'old' => $shopper->getLocale()->get(),
                'new' => $command->getLocale()->get(),
            ];
        }

        foreach ($command->getData() as $key => $value) {
            if ($shopper->getData($key) !== $value) {
                $updatedValues[$key] = [
                    'old' => $shopper->getData($key),
                    'new' => $value,
                ];
            }
        }

        return $updatedValues;
    }

    private function extractUpdatedAddressValues(UpdateShippingAddress|UpdateBillingAddress $command, Address $address): array
    {
        $updatedValues = $command->getAddress()->diff($address);

        foreach ($updatedValues as $key => $value) {
            $updatedValues[$key] = [
                'old' => $address->toArray()[$key],
                'new' => $command->getAddress()->toArray()[$key],
            ];
        }

        return $updatedValues;
    }
}
