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
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderVatSnapshot;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustVatRates;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCartAction;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\UpdateShippingProfileOnOrder;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Application\VatNumber\ValidateVatNumber;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberApplication;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidation;
use Thinktomorrow\Trader\Application\VatRate\VatExemptionApplication;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatRoundingStrategy;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateMachine;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\TraderConfig;

final class CartApplication
{
    public function __construct(
        private TraderConfig $config,
        private ContainerInterface $container,
        private ProductDetailRepository $productDetailRepository,
        private AdjustLine $adjustLine,
        private OrderRepository $orderRepository,
        private OrderStateMachine $orderStateMachine,
        private RefreshCartAction $refreshCartAction,
        private UpdateShippingProfileOnOrder $updateShippingProfileOnOrder,
        private UpdatePaymentMethodOnOrder $updatePaymentMethodOnOrder,
        private CustomerRepository $customerRepository,
        private EventDispatcher $eventDispatcher,
        private VatNumberApplication $vatNumberApplication,
        private VatExemptionApplication $vatExemptionApplication,
    ) {}

    public function refresh(RefreshCart $refreshCart): void
    {
        $order = $this->orderRepository->findForCart($refreshCart->getOrderId());

        $this->refreshCartAction->handle($order, [
            $this->container->get(AdjustLines::class),
            $this->container->get(AdjustShipping::class),
            $this->container->get(AdjustVatRates::class),
            $this->container->get(AdjustDiscounts::class),
            $this->container->get(AdjustOrderVatSnapshot::class),
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

        $this->container->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());

        return $order->orderId;
    }

    public function addLine(AddLine $addLine): OrderId
    {
        $locale = $addLine->getData()['locale'] ?? null;

        $orderId = $addLine->getOrderId();
        $order = $this->orderRepository->findForCart($orderId);
        $lineId = $this->orderRepository->nextLineReference();

        $product = $this->productDetailRepository->findProductDetail($addLine->getVariantId());

        $itemPrice = DefaultItemPrice::fromMoney(
            $this->config->includeVatInPrices() ? $product->getUnitPrice()->getIncludingVat() : $product->getUnitPrice()->getExcludingVat(),
            $product->getUnitPrice()->getVatPercentage(),
            $this->config->includeVatInPrices()
        );

        $taxaData = $this->extractTaxaData($product, $locale);

        $line = Line::create(
            $orderId,
            $lineId,
            new PurchasableReference('variant', $addLine->getVariantId()->get()),
            $itemPrice,
            $addLine->getQuantity(),
            array_merge($addLine->getData(), [
                'title' => $product->getTitle($locale),
                'taxa' => $taxaData,
                'product_id' => $product->getProductId(),
                'unit_price_excl' => $product->getUnitPrice()->getExcludingVat()->getAmount(),
                'unit_price_incl' => $product->getUnitPrice()->getIncludingVat()->getAmount(),
                'sale_price_excl' => $product->getSalePrice()->getExcludingVat()->getAmount(),
                'sale_price_incl' => $product->getSalePrice()->getIncludingVat()->getAmount(),
                'vat_rounding_strategy' => VatRoundingStrategy::fromStringOrDefault($this->config->getVatRoundingStrategy())->value,
            ])
        );

        if ($this->config->includeVatInPrices()) {
            $line->setAuthoritativeIncl(true);
        }

        $order->addOrUpdateLine($line);

        $this->adjustLine->adjust($order, $order->findLine($lineId));

        $linePersonalisations = [];

        foreach ($addLine->getPersonalisations() as $personalisation_id => $personalisation_value) {
            $originalPersonalisation = null;

            foreach ($product->getPersonalisations() as $personalisation) {
                if ($personalisation->personalisationId->equals(PersonalisationId::fromString($personalisation_id))) {
                    $originalPersonalisation = $personalisation;
                }
            }

            if (! $originalPersonalisation) {
                throw new \InvalidArgumentException('No personalisation found for variant ['.$addLine->getVariantId()->get().'] by personalisation id ['.$personalisation_id.'].');
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

        // Recalculate VAT snapshot
        $this->container->get(AdjustOrderVatSnapshot::class)->adjust($order);

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

        // Recalculate VAT snapshot
        $this->container->get(AdjustOrderVatSnapshot::class)->adjust($order);

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

        // Recalculate VAT snapshot
        $this->container->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateShippingAddress(UpdateShippingAddress $updateShippingAddress): void
    {
        $order = $this->orderRepository->findForCart($updateShippingAddress->getOrderId());
        $existingData = $order->getShippingAddress()?->getData() ?? [];

        // Get existing address_id, if not we create one here
        $shippingAddress = ShippingAddress::create(
            $order->orderId,
            $updateShippingAddress->getAddress(),
            $existingData
        );
        $shippingAddress->addData($updateShippingAddress->getData());

        $order->updateShippingAddress($shippingAddress);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateBillingAddress(UpdateBillingAddress $updateBillingAddress): void
    {
        $order = $this->orderRepository->findForCart($updateBillingAddress->getOrderId());
        $existingData = $order->getBillingAddress()?->getData() ?? [];

        // Get existing address_id, if not we create one here
        $billingAddress = BillingAddress::create(
            $order->orderId,
            $updateBillingAddress->getAddress(),
            $existingData
        );
        $billingAddress->addData($updateBillingAddress->getData());

        $order->updateBillingAddress($billingAddress);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function chooseShippingProfile(ChooseShippingProfile $chooseShippingProfile): void
    {
        $order = $this->orderRepository->findForCart($chooseShippingProfile->getOrderId());

        $this->updateShippingProfileOnOrder->handle($order, $chooseShippingProfile->getShippingProfileId());

        // Recalculate VAT snapshot
        $this->container->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function choosePaymentMethod(ChoosePaymentMethod $choosePaymentMethod): void
    {
        $order = $this->orderRepository->findForCart($choosePaymentMethod->getOrderId());

        $this->updatePaymentMethodOnOrder->handle($order, $choosePaymentMethod->getPaymentMethodId());

        // Recalculate VAT snapshot
        $this->container->get(AdjustOrderVatSnapshot::class)->adjust($order);

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

        // Recalculate VAT snapshot
        $this->container->get(AdjustOrderVatSnapshot::class)->adjust($order);

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
            $billingAddress->getData()
        ));

    }

    private function chooseCustomerShippingAddress(Order $order, \Thinktomorrow\Trader\Domain\Model\Customer\Address\ShippingAddress $shippingAddress): void
    {
        $order->updateShippingAddress(ShippingAddress::create(
            $order->orderId,
            $shippingAddress->getAddress(),
            $shippingAddress->getData()
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

        // Recalculate VAT snapshot
        $this->container->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    private function extractTaxaData(\Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail $product, ?string $locale): array
    {
        $taxaData = [];

        /** @var ProductTaxonItem|VariantTaxonItem $taxa */
        foreach ($product->getTaxa() as $taxon) {
            $taxaData[] = [
                'class_type' => $taxon instanceof VariantTaxonItem ? VariantTaxonItem::class : ProductTaxonItem::class,
                'taxonomy_id' => $taxon->getTaxonomyId(),
                'taxonomy_type' => $taxon->getTaxonomyType(),
                'taxon_id' => $taxon->getTaxonId(),
                'key' => $taxon->getKey($locale),
                'url' => $taxon->getUrl($locale),
                'label' => $taxon->getLabel($locale),
                'taxonomy_label' => $taxon->getTaxonomyLabel($locale),
                'data' => $taxon->getData(),
            ];
        }

        return $taxaData;
    }
}
