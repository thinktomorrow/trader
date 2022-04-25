<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
use Thinktomorrow\Trader\Application\Cart\Line\RemoveLine;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Application\Cart\Line\ChangeLineQuantity;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\ShippingProfileNotSelectableForCountry;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotSelectShippingCountryDueToMissingShippingCountry;

final class CartApplication
{
    private VariantForCartRepository $findVariantDetailsForCart;
    private OrderRepository $orderRepository;
    private ShippingProfileRepository $shippingProfileRepository;
    private EventDispatcher $eventDispatcher;
    private PaymentMethodRepository $paymentMethodRepository;
    private CustomerRepository $customerRepository;
    private TraderConfig $config;

    public function __construct(
        TraderConfig              $config,
        VariantForCartRepository  $findVariantDetailsForCart,
        OrderRepository           $orderRepository,
        ShippingProfileRepository $shippingProfileRepository,
        PaymentMethodRepository   $paymentMethodRepository,
        CustomerRepository        $customerRepository,
        EventDispatcher           $eventDispatcher
    )
    {
        $this->findVariantDetailsForCart = $findVariantDetailsForCart;
        $this->orderRepository = $orderRepository;
        $this->shippingProfileRepository = $shippingProfileRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->customerRepository = $customerRepository;
        $this->config = $config;
    }

    public function addLine(AddLine $addLine): void
    {
        $order = $this->orderRepository->find($addLine->getOrderId());
        $variant = $this->findVariantDetailsForCart->findVariantForCart($addLine->getVariantId());

        $lineId = $order->getNextLineId();

        $order->addOrUpdateLine(
            $lineId,
            $addLine->getVariantId(),
            LinePrice::fromPrice($variant->getSalePrice()),
            $addLine->getQuantity()
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function changeLineQuantity(ChangeLineQuantity $changeLineQuantity): void
    {
        $order = $this->orderRepository->find($changeLineQuantity->getOrderId());

        $order->updateLineQuantity(
            $changeLineQuantity->getLineId(),
            $changeLineQuantity->getQuantity()
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function removeLine(RemoveLine $removeLine): void
    {
        $order = $this->orderRepository->find($removeLine->getOrderId());

        $order->deleteLine(
            $removeLine->getLineId(),
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function chooseShippingCountry(ChooseShippingCountry $chooseShippingCountry): void
    {
        $order = $this->orderRepository->find($chooseShippingCountry->getOrderId());

        $order->updateShippingAddress(
            $order->getShippingAddress()->replaceCountry($chooseShippingCountry->getShippingCountry()->get())
        );

        // TODO: Maybe do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function chooseShippingAddress(ChooseShippingAddress $chooseShippingAddress): void
    {
        $order = $this->orderRepository->find($chooseShippingAddress->getOrderId());

        $order->updateShippingAddress($chooseShippingAddress->getShippingAddress());

        // TODO: do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function chooseBillingAddress(ChooseBillingAddress $chooseBillingAddress): void
    {
        $order = $this->orderRepository->find($chooseBillingAddress->getOrderId());

        $order->updateBillingAddress($chooseBillingAddress->getBillingAddress());

        // TODO: do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function chooseShippingProfile(ChooseShippingProfile $chooseShippingProfile): void
    {
        $shippingProfile = $this->shippingProfileRepository->find($chooseShippingProfile->getShippingProfileId());
        $order = $this->orderRepository->find($chooseShippingProfile->getOrderId());

        // Country of shipment
        if(!$shippingCountry = $order->getShippingAddress()?->getCountry()){
            throw new CouldNotSelectShippingCountryDueToMissingShippingCountry(
                'Order ['.$order->orderId->get().'] missing shipping country that is required when selecting profile ' . $shippingProfile->shippingProfileId->get()
            );
        }

        if(!$shippingProfile->hasCountry(ShippingCountry::fromString($shippingCountry))) {
            throw new ShippingProfileNotSelectableForCountry(
                'Shipping profile ' . $shippingProfile->shippingProfileId->get() . ' is not allowed for country ' . $shippingCountry
            );
        }

        // Find matching shipping tariff
        $tariff = $shippingProfile->findTariffByPrice($order->getSubtotal(), $this->config->doesPriceInputIncludesVat());

        $shippingCost = ShippingCost::fromMoney(
            $tariff->getRate(),
            TaxRate::fromString($this->config->getDefaultTaxRate()),
            $this->config->doesPriceInputIncludesVat()
        );

        if(count($order->getShippings()) > 0) {
            /** @var Shipping $existingShipping */
            $existingShipping = $order->getShippings()[0];
            $existingShipping->updateShippingProfile($shippingProfile->shippingProfileId);
            $existingShipping->updateCost($shippingCost);

            $order->updateShipping($existingShipping);
        } else {
            $order->addShipping(Shipping::create(
                $order->orderId,
                $this->orderRepository->nextShippingReference(),
                $shippingProfile->shippingProfileId,
                $shippingCost
            ));
        }

        // TODO: Maybe do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());

        // Trigger refresh (should be after event)
//        $this->refreshCart(new RefreshCart($order->orderId->get(), [
//            AdjustShipping::class,
//        ], new Context(), // TODO: create testcontext?
//        ));
    }

    public function choosePaymentMethod(ChoosePaymentMethod $choosePaymentMethod): void
    {
        $paymentMethod = $this->paymentMethodRepository->find($choosePaymentMethod->getPaymentMethodId());
        $order = $this->orderRepository->find($choosePaymentMethod->getOrderId());

        // Currently no restrictions on payment selection... if any, this should be checked here.

        $payment = Payment::create(
            $order->orderId,
            $paymentMethod->paymentMethodId,
            PaymentCost::fromMoney(
                $paymentMethod->getRate(),
                TaxRate::fromString($this->config->getDefaultTaxRate()),
                $this->config->doesPriceInputIncludesVat()
            )
        );

        $order->updatePayment($payment);

        // TODO: Maybe do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function chooseCustomer(ChooseCustomer $chooseCustomer): void
    {
        $order = $this->orderRepository->find($chooseCustomer->getOrderId());
        $customer = $this->customerRepository->find($chooseCustomer->getCustomerId());

        $shopper = Shopper::create();

        $order->updateShopper($shopper);

        // TODO: do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }


}
