<?php
declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use Tests\Acceptance\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Cart\ChooseCustomer;
use Thinktomorrow\Trader\Application\Cart\ChoosePaymentMethod;
use Thinktomorrow\Trader\Application\Cart\ChooseShippingProfile;
use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
use Thinktomorrow\Trader\Application\Cart\Line\ChangeLineQuantity;
use Thinktomorrow\Trader\Application\Cart\Line\RemoveLine;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Application\Cart\UpdateBillingAddress;
use Thinktomorrow\Trader\Application\Cart\UpdateShippingAddress;
use Thinktomorrow\Trader\Application\Cart\UpdateShopper;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductPersonalisations;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodProviderId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

abstract class CartContext extends TestCase
{
    use TestHelpers;

//    protected CartApplication $cartApplication;
//    protected CouponPromoApplication $promoApplication;
//    protected InMemoryProductRepository $productRepository;
//    protected InMemoryOrderRepository $orderRepository;
//    protected InMemoryShippingProfileRepository $shippingProfileRepository;
//    protected InMemoryVatRateRepository $vatRateRepository;
//    protected InMemoryVariantRepository $variantRepository;
//    protected InMemoryCartRepository $cartRepository;
//    protected InMemoryPaymentMethodRepository $paymentMethodRepository;
//    protected InMemoryCustomerRepository $customerRepository;
//    protected InMemoryPromoRepository $promoRepository;
//    protected UpdateShippingProfileOnOrder $updateShippingProfileOnOrder;
//    protected UpdatePaymentMethodOnOrder $updatePaymentMethodOnOrder;
//    protected EventDispatcherSpy $eventDispatcher;
//    protected CustomerApplication $customerApplication;
//    protected ProductApplication $productApplication;
//    protected InMemoryMerchantOrderRepository $merchantOrderRepository;
//    protected FindVatRateForOrder $findVatRateForOrder;
//    protected VatNumberApplication $vatNumberApplication;
//    protected VatNumberValidator $vatNumberValidator;
//    protected MerchantOrderApplication $merchantOrderApplication;
//    protected VatExemptionApplication $vatExemptionApplication;


    protected function setUp(): void
    {
        parent::setUp();

//        // states
//        (new TestContainer())->add(OrderState::class, DefaultOrderState::class);
//        (new TestContainer())->add(ShippingState::class, DefaultShippingState::class);
//        (new TestContainer())->add(PaymentState::class, DefaultPaymentState::class);
//
//        $this->catalogContextcatalogRepos()->->productRepository() = new InMemoryProductRepository();
//        $this->>catalogContext->repos()->variantRepository() = new InMemoryVariantRepository();
//        $this->cartRepository = new InMemoryCartRepository();
//        $this->merchantOrderRepository = new InMemoryMerchantOrderRepository();
//        $this->catalogContext->repos()->vatRateRepository() = new InMemoryVatRateRepository(new TestTraderConfig());
//        $this->vatExemptionApplication = new VatExemptionApplication(new TestTraderConfig());
//        $this->findVatRateForOrder = new FindVatRateForOrder(new TestTraderConfig(), $this->vatExemptionApplication, $this->catalogContext->repos()->vatRateRepository());
//        $this->orderContext->repos()->promoRepository() = new InMemoryPromoRepository(
//            new DiscountFactory([
//                FixedAmountDiscount::class,
//                PercentageOffOrderDiscount::class,
//            ], new ConditionFactory([
//                MinimumLinesQuantity::class,
//            ])),
//            new OrderDiscountFactory([
//                FixedAmountOrderDiscount::class,
//                PercentageOffOrderDiscount::class,
//            ], new OrderConditionFactory([
//                \Thinktomorrow\Trader\Application\Promo\OrderPromo\Conditions\MinimumLinesQuantityOrderCondition::class,
//            ]))
//        );

        // Adjusters are loaded via container so set them up here
//        (new TestContainer())->add(InvoiceRepository::class, $this->>orderContext->repos()->orderRepository());
//        (new TestContainer())->add(ApplyPromoToOrder::class, new ApplyPromoToOrder($this->>orderContext->repos()->orderRepository()));
//        (new TestContainer())->add(AdjustLine::class, new DefaultAdjustLine());
//        (new TestContainer())->add(AdjustLines::class, new AdjustLines(new InMemoryVariantRepository(), TestContainer::make(AdjustLine::class)));
//        (new TestContainer())->add(AdjustVatRates::class, new AdjustVatRates($this->>catalogContext->repos()->variantRepository(), new FindVatRateForOrder(new TestTraderConfig(), new VatExemptionApplication(new TestTraderConfig()), $this->catalogContext->repos()->vatRateRepository())));
//        (new TestContainer())->add(AdjustDiscounts::class, new AdjustDiscounts($this->orderContext->repos()->promoRepository(), (new TestContainer())->get(ApplyPromoToOrder::class)));
//        (new TestContainer())->add(OrderStateMachine::class, new OrderStateMachine([
//            ...DefaultOrderState::customerStates(), DefaultOrderState::confirmed,
//        ], [
//            'complete' => DefaultOrderState::getTransitions()['complete'],
//            'confirm' => DefaultOrderState::getTransitions()['confirm'],
//        ]));
//
//        $this->vatNumberValidator = new DummyVatNumberValidator();
//        $this->vatNumberApplication = new VatNumberApplication($this->vatNumberValidator);
//
//        $this->orderContext->apps()->cartApplication() = new CartApplication(
//            new TestTraderConfig(),
//            new TestContainer(),
//            $this->>catalogContext->repos()->variantRepository(),
//            TestContainer::make(AdjustLine::class),
//            $this->>orderContext->repos()->orderRepository(),
//            (new TestContainer())->get(OrderStateMachine::class),
//            new RefreshCartAction(),
//            $this->orderContext->repos()->shippingProfileRepository() = new InMemoryShippingProfileRepository(),
//            $this->updateShippingProfileOnOrder = new UpdateShippingProfileOnOrder(new TestContainer(), new TestTraderConfig(), $this->>orderContext->repos()->orderRepository(), $this->orderContext->repos()->shippingProfileRepository(), $this->findVatRateForOrder),
//            $this->updatePaymentMethodOnOrder = new UpdatePaymentMethodOnOrder(new TestContainer(), new TestTraderConfig(), $this->>orderContext->repos()->orderRepository(), new DefaultVerifyPaymentMethodForCart(), $this->orderContext->repos()->paymentMethodRepository(), $this->findVatRateForOrder),
//            $this->orderContext->repos()->customerRepository() = new InMemoryCustomerRepository(),
//            $this->eventDispatcher = new EventDispatcherSpy(),
//            $this->vatNumberApplication,
//            $this->vatExemptionApplication,
//        );
//
//        $this->catalogContext->apps()->productApplication() = new ProductApplication(
//            new TestTraderConfig(),
//            $this->eventDispatcher,
//            $this->catalogContextcatalogRepos()->->productRepository(),
//            $this->>catalogContext->repos()->variantRepository(),
//        );
//
//        $this->promoApplication = new CouponPromoApplication(
//            new TestTraderConfig(),
//            new TestContainer(),
//            $this->>orderContext->repos()->orderRepository(),
//            $this->orderContext->repos()->promoRepository(),
//            (new TestContainer())->get(ApplyPromoToOrder::class),
//            $this->eventDispatcher,
//        );
//
//        $this->customerApplication = new CustomerApplication(
//            $this->orderContext->repos()->customerRepository(),
//            $this->eventDispatcher,
//        );
//
//        // Container bindings
//        (new TestContainer())->add(AdjustShipping::class, new AdjustShipping(
//            $this->updateShippingProfileOnOrder,
//        ));
//
//        $this->merchantOrderApplication = new MerchantOrderApplication(
//            $this->>orderContext->repos()->orderRepository(),
//            $this->eventDispatcher,
//            $this->vatNumberApplication,
//        );
//
//        // Make sure we start with a clean slate
//        $this->clearRepositories();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    protected function givenThereIsAProductWhichCostsEur($productTitle, $price)
    {
        $productId = ProductId::fromString($productTitle)->get();

        $this->catalogContext->createProduct($productId, null);
        $this->catalogContext->createVariant($productId, $productId . '-variant-aaa', [
            'unit_price' => $price * 100,
            'sale_price' => $price * 100,
        ]);
    }

    protected function givenThereIsAProductPersonalisation($productTitle, array $personalisations)
    {
        $product = $this->catalogContext->repos()->productRepository()->find(ProductId::fromString($productTitle));

        $this->catalogContext->apps()->productApplication()->updateProductPersonalisations(new UpdateProductPersonalisations($product->productId->get(), $personalisations));

        Assert::assertCount(count($personalisations), $this->catalogContext->repos()->productRepository()->find(ProductId::fromString($productTitle))->getPersonalisations());
    }

    public function givenOrderHasAShippingCountry(string $country)
    {
        $order = $this->getOrder();

        $this->orderContext->apps()->cartApplication()->updateShippingAddress(new UpdateShippingAddress($order->orderId->get(), $country));
    }

    public function givenOrderHasABillingCountry(string $country)
    {
        $order = $this->getOrder();

        $this->orderContext->apps()->cartApplication()->updateBillingAddress(new UpdateBillingAddress($order->orderId->get(), $country));
    }

    public function givenShippingCostsForAPurchaseOfEur($shippingCost, $from, $to, array $countries = ['BE'], string $shippingProfileId = 'bpost_home', bool $requiredAddress = true)
    {
        $shippingProfile = ShippingProfile::create(
            ShippingProfileId::fromString($shippingProfileId),
            ShippingProviderId::fromString('postnl'),
            $requiredAddress
        );
        $shippingProfile->addData(['title' => ['nl' => Str::headline($shippingProfileId)]]);

        foreach ($countries as $country) {
            $shippingProfile->addCountry(CountryId::fromString($country));
        }

        $shippingProfile->addTariff(
            Tariff::create(
                $this->orderContext->repos()->shippingProfileRepository()->nextTariffReference(),
                $shippingProfile->shippingProfileId,
                Cash::make($shippingCost * 100),
                Cash::make($from * 100),
                Cash::make($to * 100),
            )
        );

        $this->orderContext->repos()->shippingProfileRepository()->save($shippingProfile);
    }

    public function givenPaymentMethod($paymentRate, string $paymentMethodId = 'visa')
    {
        $paymentMethod = PaymentMethod::create(
            PaymentMethodId::fromString($paymentMethodId),
            PaymentMethodProviderId::fromString('mollie'),
            Cash::make($paymentRate * 100)
        );
        $paymentMethod->addData(['title' => ['nl' => Str::headline($paymentMethodId)]]);

        $this->orderContext->repos()->paymentMethodRepository()->save($paymentMethod);
    }

    public function givenThereIsAVatRate(string $countryId = 'NL', string $vatRateValue = '21', string $vatRateId = 'taxrates-nl'): VatRate
    {
        $vatRate = VatRate::create(
            VatRateId::fromString($vatRateId),
            CountryId::fromString($countryId),
            VatPercentage::fromString($vatRateValue),
            true
        );
        $vatRate->addData(['title' => ['nl' => Str::headline($vatRateId)]]);

        $this->catalogContext->repos()->vatRateRepository()->save($vatRate);

        return $vatRate;
    }

    public function givenVatRateHasBaseRateOf(VatRateId $vatRateId, VatRateId $originVatRateId, ?string $baseRateId = null)
    {
        $vatRate = $this->catalogContext->repos()->vatRateRepository()->find($vatRateId);
        $baseVatRate = $this->catalogContext->repos()->vatRateRepository()->find($originVatRateId);
        $vatRate->addBaseRate(BaseRate::create(
            $baseRateId ?: $this->catalogContext->repos()->vatRateRepository()->nextBaseRateReference(),
            $originVatRateId,
            $vatRateId,
            $baseVatRate->getRate()
        ));

        $this->catalogContext->repos()->vatRateRepository()->save($vatRate);
    }

    public function givenACustomerExists(string $email, bool $is_business = false, string $locale = 'nl_BE'): Customer
    {
        $customer = Customer::create(
            $this->orderContext->repos()->customerRepository()->nextReference(),
            Email::fromString($email),
            $is_business,
            Locale::fromString($locale)
        );

        $this->orderContext->repos()->customerRepository()->save($customer);

        return $customer;
    }

    public function givenThereIsAPromo(array $mappedData = [], array $discounts = [])
    {
        $promo = $this->createPromo($mappedData, $discounts ?: [$this->orderContext->createOrderDiscount([], [$this->createCondition()])]);

        $this->orderContext->repos()->promoRepository()->save($promo);
    }

    protected function whenIAddTheVariantToTheCart($productVariantId, $quantity = 1, array $data = [], array $personalisations = [])
    {
        $order = $this->getOrder();

        $count = count($order->getLines());

        // Add product to order
        $this->orderContext->apps()->cartApplication()->addLine(new AddLine(
            $order->orderId->get(),
            VariantId::fromString($productVariantId)->get(),
            Quantity::fromInt((int)$quantity)->asInt(),
            $personalisations,
            $data
        ));

        $checkFlag = false;
        $lines = $this->orderContext->findOrder($order->orderId)->getLines();
        foreach ($lines as $line) {
            if ($line->getPurchasableReference()->getId() == $productVariantId) {
                $checkFlag = true;
            }
        }

        if (!$checkFlag) {
            throw new \Exception('Cartitem presence check failed. No line found by ' . $productVariantId);
        }
    }

    protected function whenIAddTheFirstVariantToTheCart($productVariantId, $quantity = 1, array $data = [], array $personalisations = [])
    {
        $orderId = $this->orderContext->apps()->cartApplication()->createNewOrder();

        // Add product to a new order
        $this->orderContext->apps()->cartApplication()->addLine(new AddLine(
            $orderId->get(),
            VariantId::fromString($productVariantId)->get(),
            Quantity::fromInt((int)$quantity)->asInt(),
            $personalisations,
            $data
        ));

        $checkFlag = false;
        $lines = $this->orderContext->findOrder($orderId)->getLines();
        foreach ($lines as $line) {
            if ($line->getPurchasableReference()->getId() == $productVariantId) {
                $checkFlag = true;
            }
        }

        if (!$checkFlag) {
            throw new \Exception('Cartitem presence check failed. No line found by ' . $productVariantId);
        }
    }

    protected function whenIChangeTheProductQuantity($productTitle, $quantity)
    {
        $order = $this->getOrder();
        $lines = $order->getLines();

        // Find matching line by productId
        $lineId = null;
        foreach ($lines as $line) {
            if ($line->getPurchasableReference()->getId() == $productTitle) {
                $lineId = $line->lineId;
            }
        }

        $this->orderContext->apps()->cartApplication()->changeLineQuantity(new ChangeLineQuantity(
            $order->orderId->get(),
            $lineId->get(),
            Quantity::fromInt((int)$quantity)->asInt(),
        ));
    }

    protected function whenIRemoveTheLine($productVariantId)
    {
        $order = $this->getOrder();
        $lines = $order->getLines();

        // Find matching line by productId
        $lineId = null;
        foreach ($lines as $line) {
            if ($line->getPurchasableReference()->getId() == $productVariantId) {
                $lineId = $line->lineId;
            }
        }

        $this->orderContext->apps()->cartApplication()->removeLine(new RemoveLine(
            $order->orderId->get(),
            $lineId->get(),
        ));
    }

    protected function whenIEnterShopperDetails(string $email, bool $is_business = false, string $vatNumber = '0410340880', string $locale = 'nl_BE', array $data = [])
    {
        $order = $this->getOrder();

        $this->orderContext->apps()->cartApplication()->updateShopper(new UpdateShopper(
            $order->orderId->get(),
            $email,
            $is_business,
            $vatNumber,
            $locale,
            $data,
        ));

        $this->assertNotNull($order->getShopper());
    }

    protected function whenIChooseCustomer(string $email)
    {
        $customer = $this->orderContext->repos()->customerRepository()->findByEmail(Email::fromString($email));

        $this->orderContext->apps()->cartApplication()->chooseCustomer(new ChooseCustomer(
            $this->getOrder()->orderId->get(),
            $customer->customerId->get(),
        ));
    }

    protected function thenIShouldHaveProductInTheCart($times, $quantity, string $orderId = 'xxx')
    {
        $order = $this->orderContext->findOrder(OrderId::fromString($orderId));
        $lines = $this->orderContext->findOrder($order->orderId)->getLines();

        Assert::assertCount((int)$times, $lines);
        if (count($lines) > 0) {
            Assert::assertEquals((int)$quantity, $lines[0]->getQuantity()->asInt());
        }
    }

    protected function thenIShouldHaveAShippingCost($cost)
    {
        $this->assertEquals(Cash::make($cost * 100), $this->getOrder()->getShippingCostExcl());
    }

    protected function whenIAddShippingAddress(?string $country = null, ?string $line1 = null, ?string $line2 = null, ?string $postalCode = null, ?string $city = null)
    {
        $this->orderContext->apps()->cartApplication()->updateShippingAddress(new UpdateShippingAddress(
            $this->getOrder()->orderId->get(),
            $country,
            $line1,
            $line2,
            $postalCode,
            $city
        ));

        $this->assertEquals([$country, $line1, $line2, $postalCode, $city], array_values($this->getOrder()->getShippingAddress()->getAddress()->toArray()));
    }

    protected function whenIAddBillingAddress(?string $country = null, ?string $line1 = null, ?string $line2 = null, ?string $postalCode = null, ?string $city = null)
    {
        $this->orderContext->apps()->cartApplication()->updateBillingAddress(new UpdateBillingAddress(
            $this->getOrder()->orderId->get(),
            $country,
            $line1,
            $line2,
            $postalCode,
            $city
        ));

        $this->assertEquals([$country, $line1, $line2, $postalCode, $city], array_values($this->getOrder()->getBillingAddress()->getAddress()->toArray()));
    }

    protected function whenIChooseShipping(string $shippingProfileId)
    {
        $this->orderContext->apps()->cartApplication()->chooseShippingProfile(new ChooseShippingProfile(
            $this->getOrder()->orderId->get(),
            $shippingProfileId
        ));

        if (count($this->getOrder()->getShippings())) {
            $this->assertEquals($shippingProfileId, $this->getOrder()->getShippings()[0]->getShippingProfileId()->get());
        }
    }

    protected function whenIChoosePayment(string $paymentMethodId)
    {
        $this->orderContext->apps()->cartApplication()->choosePaymentMethod(new ChoosePaymentMethod(
            $this->getOrder()->orderId->get(),
            $paymentMethodId
        ));

        if (count($this->getOrder()->getPayments())) {
            $this->assertEquals($paymentMethodId, $this->getOrder()->getPayments()[0]->getPaymentMethodId()->get());
        }
    }

    protected function thenTheOverallCartPriceShouldBeEur($total)
    {
        Assert::assertEquals($total * 100, $this->getOrder()->getTotalExcl()->getAmount());
    }

    protected function thenTheCartItemShouldContainData($productVariantId, $dataKey, $dataValue)
    {
        $order = $this->orderContext->findOrder(OrderId::fromString('xxx'));
        $lines = $order->getLines();

        // Find matching line by variantId
        $line = null;
        foreach ($lines as $_line) {
            if ($_line->getPurchasableReference()->getId() == $productVariantId) {
                $line = $_line;
            }
        }

        if (!$line) {
            throw new \Exception('No line found by ' . $productVariantId);
        }

        $this->assertArrayHasKey($dataKey, $line->getData());
        $this->assertEquals($dataValue, $line->getData($dataKey));
    }

    protected function refreshCart(): static
    {
        $this->orderContext->apps()->cartApplication()->refresh(new RefreshCart(
            $this->getOrder()->orderId->get()
        ));

        return $this;
    }

    protected function getOrder(): Order
    {
        // Create an order if not already
        try {
            return $this->orderContext->findOrder(OrderId::fromString('xxx'));
        } catch (CouldNotFindOrder $e) {
            $this->orderContext->repos()->orderRepository()->save($order = Order::create(
                OrderId::fromString('xxx'),
                OrderReference::fromString('xx-ref'),
                DefaultOrderState::cart_pending,
            ));

            return $order;
        }
    }
}
