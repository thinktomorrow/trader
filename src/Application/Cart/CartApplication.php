<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingRepository;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\ShippingProfileNotSelectableForCountry;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotSelectShippingCountryDueToMissingShippingCountry;

final class CartApplication
{
    private ProductRepository $productRepository;
    private OrderRepository $orderRepository;
    private ShippingRepository $shippingRepository;
    private ShippingProfileRepository $shippingProfileRepository;
    private EventDispatcher $eventDispatcher;
    private PaymentMethodRepository $paymentMethodRepository;
    private TraderConfig $config;

    public function __construct(
        TraderConfig $config,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        ShippingRepository $shippingRepository,
        ShippingProfileRepository $shippingProfileRepository,
        PaymentMethodRepository $paymentMethodRepository,
        EventDispatcher $eventDispatcher
    )
    {
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->shippingRepository = $shippingRepository;
        $this->shippingProfileRepository = $shippingProfileRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->config = $config;
    }

    public function addLine(AddLine $addLine): void
    {
        $order = $this->orderRepository->find($addLine->getOrderId());
        $product = $this->productRepository->find($addLine->getProductId());

        $order->addOrUpdateLine(
            $addLine->getLineNumber(),
            $product->productId,
            LinePrice::fromPrice($product->getSalePrice()),
            $addLine->getQuantity()
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());

//        // Trigger refresh (should be after event)
//        $this->refreshCart(new RefreshCart($order->orderId->get(), [
//            AdjustShipping::class,
//        ], new Context(), // TODO: create testcontext?
//        ));
    }

    public function chooseShippingCountry(ChooseShippingCountry $chooseShippingCountry): void
    {
        $order = $this->orderRepository->find($chooseShippingCountry->getOrderId());

        $order->updateShippingAddress(
            $order->getShippingAddress()->replaceCountry($chooseShippingCountry->getShippingCountry()->get())
        );

        // TODO: Maybe do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());
    }

    public function chooseShippingAddress(ChooseShippingAddress $chooseShippingAddress): void
    {
        $order = $this->orderRepository->find($chooseShippingAddress->getOrderId());

        $order->updateShippingAddress($chooseShippingAddress->getShippingAddress());

        // TODO: do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());
    }

    public function chooseBillingAddress(ChooseBillingAddress $chooseBillingAddress): void
    {
        $order = $this->orderRepository->find($chooseBillingAddress->getOrderId());

        $order->updateBillingAddress($chooseBillingAddress->getBillingAddress());

        // TODO: do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());
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

        $shippingId = $this->shippingRepository->nextReference();

        // find matching tariff - validate if shipping profile is valid for this order?? ()
        $tariff = $shippingProfile->findTariffByPrice($order->getSubtotal(), $this->config->doesPriceInputIncludesTax());

        $shipping = Shipping::create(
            $order->orderId,
            $shippingId,
            $shippingProfile->shippingProfileId,
            ShippingCost::fromMoney(
                $tariff->getRate(),
                TaxRate::fromString($this->config->getDefaultTaxRate()),
                $this->config->doesPriceInputIncludesTax()
            )
        );

        $order->updateShipping($shipping);

        // TODO: Maybe do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());

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
                $this->config->doesPriceInputIncludesTax()
            )
        );

        $order->updatePayment($payment);

        // TODO: Maybe do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());
    }


}
