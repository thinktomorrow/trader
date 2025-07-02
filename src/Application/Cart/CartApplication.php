<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
use Thinktomorrow\Trader\Application\Cart\Line\ChangeLineData;
use Thinktomorrow\Trader\Application\Cart\Line\ChangeLineQuantity;
use Thinktomorrow\Trader\Application\Cart\Line\RemoveLine;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\UpdatePaymentMethodOnOrder;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustDiscounts;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustLine;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustLines;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustTaxRates;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCartAction;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\UpdateShippingProfileOnOrder;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Application\VatNumber\ValidateVatNumber;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberApplication;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidation;
use Thinktomorrow\Trader\Application\VatRate\VatExemptionApplication;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateMachine;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\TraderConfig;

final class CartApplication
{
    private VariantForCartRepository $findVariantDetailsForCart;
    private AdjustLine $adjustLine;
    private OrderRepository $orderRepository;
    private ShippingProfileRepository $shippingProfileRepository;
    private EventDispatcher $eventDispatcher;
    private CustomerRepository $customerRepository;
    private TraderConfig $config;
    private RefreshCartAction $refreshCartAction;
    private ContainerInterface $container;
    private UpdateShippingProfileOnOrder $updateShippingProfileOnOrder;
    private UpdatePaymentMethodOnOrder $updatePaymentMethodOnOrder;
    private OrderStateMachine $orderStateMachine;
    private VatNumberApplication $vatNumberApplication;
    private VatExemptionApplication $vatExemptionApplication;

    public function __construct(
        TraderConfig                 $config,
        ContainerInterface           $container,
        VariantForCartRepository     $findVariantDetailsForCart,
        AdjustLine                   $adjustLine,
        OrderRepository              $orderRepository,
        OrderStateMachine            $orderStateMachine,
        RefreshCartAction            $refreshCartAction,
        ShippingProfileRepository    $shippingProfileRepository,
        UpdateShippingProfileOnOrder $updateShippingProfileOnOrder,
        UpdatePaymentMethodOnOrder   $updatePaymentMethodOnOrder,
        CustomerRepository           $customerRepository,
        EventDispatcher              $eventDispatcher,
        VatNumberApplication         $vatNumberApplication,
        VatExemptionApplication      $vatExemptionApplication
    ) {
        $this->findVariantDetailsForCart = $findVariantDetailsForCart;
        $this->adjustLine = $adjustLine;
        $this->orderRepository = $orderRepository;
        $this->shippingProfileRepository = $shippingProfileRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->updateShippingProfileOnOrder = $updateShippingProfileOnOrder;
        $this->updatePaymentMethodOnOrder = $updatePaymentMethodOnOrder;
        $this->customerRepository = $customerRepository;
        $this->config = $config;
        $this->refreshCartAction = $refreshCartAction;
        $this->container = $container;
        $this->orderStateMachine = $orderStateMachine;
        $this->vatNumberApplication = $vatNumberApplication;
        $this->vatExemptionApplication = $vatExemptionApplication;

    }

    public function refresh(RefreshCart $refreshCart): void
    {
        $order = $this->orderRepository->findForCart($refreshCart->getOrderId());

        $this->refreshCartAction->handle($order, [
            $this->container->get(AdjustLines::class),
            $this->container->get(AdjustShipping::class),
            $this->container->get(AdjustTaxRates::class),
            $this->container->get(AdjustDiscounts::class),
        ]);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function createNewOrder(): OrderId
    {
        $order = Order::create(
            $this->orderRepository->nextReference(),
            $this->orderRepository->nextExternalReference(),
            $this->container->get(OrderState::class)::getDefaultState()
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());

        return $order->orderId;
    }

    public function addLine(AddLine $addLine): OrderId
    {
        $orderId = $addLine->getOrderId();
        $order = $this->orderRepository->findForCart($orderId);

        $variant = $this->findVariantDetailsForCart->findVariantForCart($addLine->getVariantId());

        $lineId = $this->orderRepository->nextLineReference();

        $order->addOrUpdateLine(
            $lineId,
            $addLine->getVariantId(),
            LinePrice::fromMoney(
                $this->config->includeVatInPrices() ? $variant->getSalePrice()->getIncludingVat() : $variant->getSalePrice()->getExcludingVat(),
                $variant->getSalePrice()->getVatPercentage(),
                $this->config->includeVatInPrices()
            ),
            $addLine->getQuantity(),
            array_merge($addLine->getData(), [
                'title' => $variant->getTitle($addLine->getData()['locale'] ?? null),
                'product_id' => $variant->getProductId()->get(),
                'unit_price_excluding_vat' => $variant->getUnitPrice()->getExcludingVat()->getAmount(),
                'unit_price_including_vat' => $variant->getUnitPrice()->getIncludingVat()->getAmount(),
            ])
        );

        $this->adjustLine->adjust($order, $order->findLine($lineId));

        $linePersonalisations = [];

        foreach ($addLine->getPersonalisations() as $personalisation_id => $personalisation_value) {
            $originalPersonalisation = null;

            foreach ($variant->getPersonalisations() as $personalisation) {
                if ($personalisation->personalisationId->equals(PersonalisationId::fromString($personalisation_id))) {
                    $originalPersonalisation = $personalisation;
                }
            }

            if (! $originalPersonalisation) {
                throw new \InvalidArgumentException('No personalisation found for variant [' . $addLine->getVariantId()->get() . '] by personalisation id [' . $personalisation_id . '].');
            }

            $linePersonalisations[] = LinePersonalisation::create(
                $lineId,
                $this->orderRepository->nextLinePersonalisationReference(),
                $originalPersonalisation->personalisationId,
                $originalPersonalisation->personalisationType,
                $personalisation_value,
                $originalPersonalisation->getData()
            );
        }

        $order->updateLinePersonalisations($lineId, $linePersonalisations);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());

        return $orderId;
    }

    public function changeLineQuantity(ChangeLineQuantity $changeLineQuantity): void
    {
        $order = $this->orderRepository->findForCart($changeLineQuantity->getOrderId());

        $order->updateLineQuantity(
            $changeLineQuantity->getLineId(),
            $changeLineQuantity->getQuantity()
        );

        $this->adjustLine->adjust($order, $order->findLine($changeLineQuantity->getLineId()));

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateOrderData(UpdateOrderData $command): void
    {
        $order = $this->orderRepository->findForCart($command->getOrderId());

        $order->addData($command->getData());

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function changeLineData(ChangeLineData $changeLineData): void
    {
        $order = $this->orderRepository->findForCart($changeLineData->getOrderId());

        $order->updateLineData($changeLineData->getLineId(), $changeLineData->getData());

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function removeLine(RemoveLine $removeLine): void
    {
        $order = $this->orderRepository->findForCart($removeLine->getOrderId());

        $order->deleteLine(
            $removeLine->getLineId(),
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateShippingAddress(UpdateShippingAddress $updateShippingAddress): void
    {
        $order = $this->orderRepository->findForCart($updateShippingAddress->getOrderId());

        // Get existing address_id, if not we create one here
        $order->updateShippingAddress(ShippingAddress::create(
            $order->orderId,
            $updateShippingAddress->getAddress(),
            []
        ));

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateBillingAddress(UpdateBillingAddress $updateBillingAddress): void
    {
        $order = $this->orderRepository->findForCart($updateBillingAddress->getOrderId());

        // Get existing address_id, if not we create one here
        $order->updateBillingAddress(BillingAddress::create(
            $order->orderId,
            $updateBillingAddress->getAddress(),
            []
        ));

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function chooseShippingProfile(ChooseShippingProfile $chooseShippingProfile): void
    {
        $order = $this->orderRepository->findForCart($chooseShippingProfile->getOrderId());

        $this->updateShippingProfileOnOrder->handle($order, $chooseShippingProfile->getShippingProfileId());

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function choosePaymentMethod(ChoosePaymentMethod $choosePaymentMethod): void
    {
        $order = $this->orderRepository->findForCart($choosePaymentMethod->getOrderId());

        $this->updatePaymentMethodOnOrder->handle($order, $choosePaymentMethod->getPaymentMethodId());

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateShopper(UpdateShopper $updateShopper): void
    {
        $order = $this->orderRepository->findForCart($updateShopper->getOrderId());

        if ($shopper = $order->getShopper()) {
            $shopper->updateEmail($updateShopper->getEmail());
            $shopper->updateBusiness($updateShopper->isBusiness());
            $shopper->updateLocale($updateShopper->getLocale());
        } else {
            $shopper = Shopper::create(
                $this->orderRepository->nextShopperReference(),
                $updateShopper->getEmail(),
                $updateShopper->isBusiness(),
                $updateShopper->getLocale()
            );
        }

        $shopper->addData($updateShopper->getData());
        $order->updateShopper($shopper);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());

        /** Add EU Vies validation result to shopper data */
        if ($updateShopper->isBusiness() && $updateShopper->getVatNumber()) {
            $this->verifyVatNumber(new VerifyCartVatNumber(
                $updateShopper->getOrderId()->get(),
                $updateShopper->getVatNumber()
            ));
        }

        // Verify VAT exemption after updating shopper details
        $this->verifyCartVatExemption(new VerifyCartVatExemption(
            $updateShopper->getOrderId()->get()
        ));
    }

    public function verifyVatNumber(VerifyCartVatNumber $command): void
    {
        $order = $this->orderRepository->findForCart($command->getOrderId());
        $shopper = $order->getShopper();

        if (! $billingAddressCountryId = $order->getBillingAddress()?->getAddress()->countryId) {
            return;
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

    public function verifyCartVatExemption(VerifyCartVatExemption $command): void
    {
        $order = $this->orderRepository->findForCart($command->getOrderId());

        $result = $this->vatExemptionApplication->verifyForOrder($order);

        $order->setVatExempt($result);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function chooseCustomer(ChooseCustomer $chooseCustomer): void
    {
        $order = $this->orderRepository->findForCart($chooseCustomer->getOrderId());
        $customer = $this->customerRepository->find($chooseCustomer->getCustomerId());

        if ($shopper = $order->getShopper()) {
            $shopper->updateEmail($customer->getEmail());
            $shopper->updateBusiness($customer->isBusiness());
            $shopper->updateLocale($customer->getLocale());
        } else {
            $shopper = Shopper::create(
                $this->orderRepository->nextShopperReference(),
                $customer->getEmail(),
                $customer->isBusiness(),
                $customer->getLocale(),
            );
        }

        $shopper->updateCustomerId($customer->customerId);
        $shopper->addData($customer->getData());
        $order->updateShopper($shopper);

        if (! $order->getBillingAddress() && $billingAddress = $customer->getBillingAddress()) {
            $this->chooseCustomerBillingAddress($order, $billingAddress);
        }

        if (! $order->getShippingAddress() && $shippingAddress = $customer->getShippingAddress()) {
            $this->chooseCustomerShippingAddress($order, $shippingAddress);
        }

        // TODO: update shipping profile and payment method if not already filled
        // Proceed in checkout should be done based on filled data no?

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    private function chooseCustomerBillingAddress(Order $order, \Thinktomorrow\Trader\Domain\Model\Customer\Address\BillingAddress $billingAddress): void
    {
        $order->updateBillingAddress(BillingAddress::create(
            $order->orderId,
            $billingAddress->getAddress(),
            []
        ));
    }

    private function chooseCustomerShippingAddress(Order $order, \Thinktomorrow\Trader\Domain\Model\Customer\Address\ShippingAddress $shippingAddress): void
    {
        $order->updateShippingAddress(ShippingAddress::create(
            $order->orderId,
            $shippingAddress->getAddress(),
            []
        ));
    }

    public function completeCart(CompleteCart $command): void
    {
        $order = $this->orderRepository->findForCart($command->getOrderId());

        $this->orderStateMachine->apply($order, 'complete');

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function confirmCart(ConfirmCart $command): void
    {
        $order = $this->orderRepository->findForCart($command->getOrderId());

        // TODO: make sure event is recorded!!!
        $this->orderStateMachine->apply($order, 'confirm');

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function confirmCartAsBusiness(ConfirmCartAsBusiness $command): void
    {
        $order = $this->orderRepository->findForCart($command->getOrderId());

        $this->orderStateMachine->apply($order, 'confirm_as_business');

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function clearCheckoutData(ClearCheckoutData $command): void
    {
        $order = $this->orderRepository->findForCart($command->getOrderId());

        $order->deleteShopper();
        $order->deleteBillingAddress();
        $order->deleteShippingAddress();
        foreach ($order->getShippings() as $shipping) {
            $order->deleteShipping($shipping->shippingId);
        }
        foreach ($order->getPayments() as $payment) {
            $order->deletePayment($payment->paymentId);
        }

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }
}
