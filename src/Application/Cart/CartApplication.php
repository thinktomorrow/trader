<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
use Thinktomorrow\Trader\Application\Cart\Line\AddLineToNewOrder;
use Thinktomorrow\Trader\Application\Cart\Line\ChangeLineQuantity;
use Thinktomorrow\Trader\Application\Cart\Line\RemoveLine;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustDiscounts;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustLines;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCartAction;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\UpdateShippingProfileOnOrder;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotSelectShippingProfileDueToMissingShippingCountry;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\ShippingProfileNotSelectableForCountry;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\TraderConfig;

final class CartApplication
{
    private VariantForCartRepository $findVariantDetailsForCart;
    private OrderRepository $orderRepository;
    private ShippingProfileRepository $shippingProfileRepository;
    private EventDispatcher $eventDispatcher;
    private PaymentMethodRepository $paymentMethodRepository;
    private CustomerRepository $customerRepository;
    private TraderConfig $config;
    private RefreshCartAction $refreshCartAction;
    private ContainerInterface $container;
    private UpdateShippingProfileOnOrder $updateShippingProfileOnOrder;

    public function __construct(
        TraderConfig              $config,
        ContainerInterface $container,
        VariantForCartRepository  $findVariantDetailsForCart,
        OrderRepository           $orderRepository,
        RefreshCartAction         $refreshCartAction,
        ShippingProfileRepository $shippingProfileRepository,
        UpdateShippingProfileOnOrder $updateShippingProfileOnOrder,
        PaymentMethodRepository   $paymentMethodRepository,
        CustomerRepository        $customerRepository,
        EventDispatcher           $eventDispatcher
    ) {
        $this->findVariantDetailsForCart = $findVariantDetailsForCart;
        $this->orderRepository = $orderRepository;
        $this->shippingProfileRepository = $shippingProfileRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->updateShippingProfileOnOrder = $updateShippingProfileOnOrder;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->customerRepository = $customerRepository;
        $this->config = $config;
        $this->refreshCartAction = $refreshCartAction;
        $this->container = $container;
    }

    public function refresh(RefreshCart $refreshCart): void
    {
        $order = $this->orderRepository->find($refreshCart->getOrderId());

        $this->refreshCartAction->handle($order, [
            $this->container->get(AdjustLines::class),
            $this->container->get(AdjustShipping::class),
            $this->container->get(AdjustDiscounts::class),
        ]);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function addLineToNewOrder(AddLineToNewOrder $addLineToNewOrder): OrderId
    {
        $orderId = $this->createNewOrder();

        return $this->addLine(AddLine::fromAddLineToNewOrder($addLineToNewOrder, $orderId));
    }

    private function createNewOrder(): OrderId
    {
        $order = Order::create(
            $this->orderRepository->nextReference(),
            $this->orderRepository->nextExternalReference()
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());

        return $order->orderId;
    }

    public function addLine(AddLine $addLine): OrderId
    {
        $orderId = $addLine->getOrderId();
        $order = $this->orderRepository->find($orderId);

        $variant = $this->findVariantDetailsForCart->findVariantForCart($addLine->getVariantId());

        // Lines are unique per variant.
        $lineId = LineId::fromString($addLine->getVariantId()->get());

        $order->addOrUpdateLine(
            $lineId,
            $addLine->getVariantId(),
            LinePrice::fromPrice($variant->getSalePrice()),
            $addLine->getQuantity(),
            array_merge($addLine->getData(), [
                // TODO: this should be done on refresh as well...
                'title' => $variant->getTitle(),
                'product_id' => $variant->getProductId()->get(),
                'unit_price' => $variant->getUnitPrice()->getMoney()->getAmount(),
            ])
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());

        return $orderId;
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

    public function updateShippingAddress(UpdateShippingAddress $updateShippingAddress): void
    {
        $order = $this->orderRepository->find($updateShippingAddress->getOrderId());

        // Get existing address_id, if not we create one here
        $order->updateShippingAddress(ShippingAddress::create(
            $order->orderId,
            $updateShippingAddress->getAddress(),
        ));

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateBillingAddress(UpdateBillingAddress $updateBillingAddress): void
    {
        $order = $this->orderRepository->find($updateBillingAddress->getOrderId());

        // Get existing address_id, if not we create one here
        $order->updateBillingAddress(BillingAddress::create(
            $order->orderId,
            $updateBillingAddress->getAddress(),
        ));

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    // TODO...
    public function chooseCustomerShippingAddress(ChooseCustomerShippingAddress $chooseCustomerShippingAddress): void
    {
        $order = $this->orderRepository->find($chooseCustomerShippingAddress->getOrderId());

        $order->updateShippingAddress($chooseCustomerShippingAddress->getAddress());

        // TODO: do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    // TODO...
    public function chooseCustomerBillingAddress(ChooseCustomerBillingAddress $chooseCustomerBillingAddress): void
    {
        $order = $this->orderRepository->find($chooseCustomerBillingAddress->getOrderId());

        $order->updateBillingAddress($chooseCustomerBillingAddress->getBillingAddress());

        // TODO: do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function chooseShippingProfile(ChooseShippingProfile $chooseShippingProfile): void
    {
        $order = $this->orderRepository->find($chooseShippingProfile->getOrderId());

        $this->updateShippingProfileOnOrder->handle($order, $chooseShippingProfile->getShippingProfileId());

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function choosePaymentMethod(ChoosePaymentMethod $choosePaymentMethod): void
    {
        $paymentMethod = $this->paymentMethodRepository->find($choosePaymentMethod->getPaymentMethodId());
        $order = $this->orderRepository->find($choosePaymentMethod->getOrderId());

        $paymentCost = PaymentCost::fromMoney(
            $paymentMethod->getRate(),
            TaxRate::fromString($this->config->getDefaultTaxRate()),
            $this->config->doesPriceInputIncludesVat()
        );

        // Currently no restrictions on payment selection... if any, this should be checked here.

        if ($payment = $order->getPayment()) {
            $payment->updatePaymentMethod($paymentMethod->paymentMethodId);
            $payment->updateCost($paymentCost);
        } else {
            $payment = Payment::create(
                $order->orderId,
                $this->orderRepository->nextPaymentReference(),
                $paymentMethod->paymentMethodId,
                $paymentCost
            );
        }

        $payment->addData($paymentMethod->getData());
        $order->updatePayment($payment);

        // TODO: Maybe do the refresh-cart here? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function updateShopper(UpdateShopper $updateShopper): void
    {
        $order = $this->orderRepository->find($updateShopper->getOrderId());

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
    }

    public function chooseCustomer(ChooseCustomer $chooseCustomer): void
    {
        $order = $this->orderRepository->find($chooseCustomer->getOrderId());
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

        // TODO:: update shipping / billing address if not already filled
        // TODO: update shipping profile and payment method if not already filled
        // Proceed in checkout should be done based on filled data no?

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }
}
