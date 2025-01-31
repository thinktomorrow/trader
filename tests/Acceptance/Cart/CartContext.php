<?php
declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Illuminate\Support\Str;
use Money\Money;
use PHPUnit\Framework\Assert;
use Tests\Acceptance\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Cart\CartApplication;
use Thinktomorrow\Trader\Application\Cart\ChooseCustomer;
use Thinktomorrow\Trader\Application\Cart\ChoosePaymentMethod;
use Thinktomorrow\Trader\Application\Cart\ChooseShippingProfile;
use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
use Thinktomorrow\Trader\Application\Cart\Line\ChangeLineQuantity;
use Thinktomorrow\Trader\Application\Cart\Line\RemoveLine;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\UpdatePaymentMethodOnOrder;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustDiscounts;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustLine;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustLines;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustTaxRates;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCartAction;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\UpdateShippingProfileOnOrder;
use Thinktomorrow\Trader\Application\Cart\UpdateBillingAddress;
use Thinktomorrow\Trader\Application\Cart\UpdateShippingAddress;
use Thinktomorrow\Trader\Application\Cart\UpdateShopper;
use Thinktomorrow\Trader\Application\Customer\CustomerApplication;
use Thinktomorrow\Trader\Application\Product\ProductApplication;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductPersonalisations;
use Thinktomorrow\Trader\Application\Promo\Coupon\CouponPromoApplication;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\ApplyPromoToOrder;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\FixedAmountOrderDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\PercentageOffOrderDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderConditionFactory;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscountFactory;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateMachine;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodProviderId;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Promo\ConditionFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDouble;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfile;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultAdjustLine;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCartRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryMerchantOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPromoRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxRateProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

abstract class CartContext extends TestCase
{
    use TestHelpers;

    protected CartApplication $cartApplication;
    protected CouponPromoApplication $promoApplication;
    protected InMemoryProductRepository $productRepository;
    protected InMemoryOrderRepository $orderRepository;
    protected InMemoryShippingProfileRepository $shippingProfileRepository;
    protected InMemoryTaxRateProfileRepository $taxRateProfileRepository;
    protected InMemoryVariantRepository $variantRepository;
    protected InMemoryCartRepository $cartRepository;
    protected InMemoryPaymentMethodRepository $paymentMethodRepository;
    protected InMemoryCustomerRepository $customerRepository;
    protected InMemoryPromoRepository $promoRepository;
    protected UpdateShippingProfileOnOrder $updateShippingProfileOnOrder;
    protected UpdatePaymentMethodOnOrder $updatePaymentMethodOnOrder;
    protected EventDispatcherSpy $eventDispatcher;
    protected CustomerApplication $customerApplication;
    protected ProductApplication $productApplication;
    protected InMemoryMerchantOrderRepository $merchantOrderRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // states
        (new TestContainer())->add(OrderState::class, DefaultOrderState::class);
        (new TestContainer())->add(ShippingState::class, DefaultShippingState::class);
        (new TestContainer())->add(PaymentState::class, DefaultPaymentState::class);

        $this->productRepository = new InMemoryProductRepository();
        $this->cartRepository = new InMemoryCartRepository();
        $this->merchantOrderRepository = new InMemoryMerchantOrderRepository();
        $this->taxRateProfileRepository = new InMemoryTaxRateProfileRepository();
        $this->promoRepository = new InMemoryPromoRepository(
            new DiscountFactory([
                FixedAmountDiscount::class,
                PercentageOffOrderDiscount::class,
            ], new ConditionFactory([
                MinimumLinesQuantity::class,
            ])),
            new OrderDiscountFactory([
                FixedAmountOrderDiscount::class,
                PercentageOffOrderDiscount::class,
            ], new OrderConditionFactory([
                \Thinktomorrow\Trader\Application\Promo\OrderPromo\Conditions\MinimumLinesQuantityOrderCondition::class,
            ]))
        );

        // Adjusters are loaded via container so set them up here
        (new TestContainer())->add(InvoiceRepository::class, $this->orderRepository);
        (new TestContainer())->add(ApplyPromoToOrder::class, new ApplyPromoToOrder($this->orderRepository));
        (new TestContainer())->add(AdjustLine::class, new DefaultAdjustLine());
        (new TestContainer())->add(AdjustLines::class, new AdjustLines(new InMemoryVariantRepository(), TestContainer::make(AdjustLine::class)));
        (new TestContainer())->add(AdjustTaxRates::class, new AdjustTaxRates($this->taxRateProfileRepository));
        (new TestContainer())->add(AdjustDiscounts::class, new AdjustDiscounts($this->promoRepository, (new TestContainer())->get(ApplyPromoToOrder::class)));
        (new TestContainer())->add(OrderStateMachine::class, new OrderStateMachine([
            ...DefaultOrderState::customerStates(), DefaultOrderState::confirmed,
        ], [
            'complete' => DefaultOrderState::getTransitions()['complete'],
            'confirm' => DefaultOrderState::getTransitions()['confirm'],
        ]));

        $this->cartApplication = new CartApplication(
            new TestTraderConfig(),
            new TestContainer(),
            $this->variantRepository = new InMemoryVariantRepository(),
            TestContainer::make(AdjustLine::class),
            $this->orderRepository,
            (new TestContainer())->get(OrderStateMachine::class),
            new RefreshCartAction(),
            $this->shippingProfileRepository = new InMemoryShippingProfileRepository(),
            $this->updateShippingProfileOnOrder = new UpdateShippingProfileOnOrder(new TestContainer(), new TestTraderConfig(), $this->orderRepository, $this->shippingProfileRepository),
            $this->updatePaymentMethodOnOrder = (new TestContainer())->get(UpdatePaymentMethodOnOrder::class),
            $this->customerRepository = new InMemoryCustomerRepository(),
            $this->eventDispatcher = new EventDispatcherSpy(),
        );

        $this->productApplication = new ProductApplication(
            new TestTraderConfig(),
            $this->eventDispatcher,
            $this->productRepository,
            $this->variantRepository,
        );

        $this->promoApplication = new CouponPromoApplication(
            new TestTraderConfig(),
            new TestContainer(),
            $this->orderRepository,
            $this->promoRepository,
            (new TestContainer())->get(ApplyPromoToOrder::class),
            new EventDispatcherSpy(),
        );

        $this->customerApplication = new CustomerApplication(
            $this->customerRepository,
            new EventDispatcherSpy(),
        );

        // Container bindings
        (new TestContainer())->add(AdjustShipping::class, new AdjustShipping(
            $this->updateShippingProfileOnOrder,
        ));

        // Make sure we start with a clean slate
        $this->clearRepositories();
    }

    public function tearDown(): void
    {
        $this->clearRepositories();
    }

    private function clearRepositories()
    {
        $this->productRepository->clear();
        $this->variantRepository->clear();
        $this->orderRepository->clear();
        $this->shippingProfileRepository->clear();
        $this->paymentMethodRepository->clear();
        $this->promoRepository->clear();
    }

    protected function givenThereIsAProductWhichCostsEur($productTitle, $price)
    {
        // Create a product
        $product = Product::create(ProductId::fromString($productTitle));

        $variant = Variant::create(
            ProductId::fromString($productTitle),
            VariantId::fromString($productTitle . '-123'),
            VariantUnitPrice::fromMoney(
                Cash::make(1000),
                TaxRate::fromString('20'),
                true
            ),
            VariantSalePrice::fromMoney(Money::EUR($price * 100), TaxRate::fromString('20'), true),
            'sku',
        );

        $variant->addData(['title' => ['nl' => $productTitle . ' variant']]);
        $product->createVariant($variant);

        $this->productRepository->save($product);

        Assert::assertNotNull($this->productRepository->find(ProductId::fromString($productTitle)));
        Assert::assertNotNull($this->variantRepository->findVariantForCart(VariantId::fromString($productTitle . '-123')));
    }

    protected function givenThereIsAProductPersonalisation($productTitle, array $personalisations)
    {
        $product = $this->productRepository->find(ProductId::fromString($productTitle));

        $this->productApplication->updateProductPersonalisations(new UpdateProductPersonalisations($product->productId->get(), $personalisations));

        Assert::assertCount(count($personalisations), $this->productRepository->find(ProductId::fromString($productTitle))->getPersonalisations());
    }

    public function givenOrderHasAShippingCountry(string $country)
    {
        $order = $this->getOrder();

        $this->cartApplication->updateShippingAddress(new UpdateShippingAddress($order->orderId->get(), $country));
    }

    public function givenOrderHasABillingCountry(string $country)
    {
        $order = $this->getOrder();

        $this->cartApplication->updateBillingAddress(new UpdateBillingAddress($order->orderId->get(), $country));
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
                $this->shippingProfileRepository->nextTariffReference(),
                $shippingProfile->shippingProfileId,
                Cash::make($shippingCost * 100),
                Cash::make($from * 100),
                Cash::make($to * 100),
            )
        );

        $this->shippingProfileRepository->save($shippingProfile);
    }

    public function givenPaymentMethod($paymentRate, string $paymentMethodId = 'visa')
    {
        $paymentMethod = PaymentMethod::create(
            PaymentMethodId::fromString($paymentMethodId),
            PaymentMethodProviderId::fromString('mollie'),
            Cash::make($paymentRate * 100)
        );
        $paymentMethod->addData(['title' => ['nl' => Str::headline($paymentMethodId)]]);

        $this->paymentMethodRepository->save($paymentMethod);
    }

    public function givenThereIsATaxRateProfile(array $mapping, array $countries = ['NL'], string $taxRateProfileId = 'taxrates-nl')
    {
        $taxRateProfile = TaxRateProfile::create(
            TaxRateProfileId::fromString($taxRateProfileId),
        );
        $taxRateProfile->addData(['title' => ['nl' => Str::headline($taxRateProfileId)]]);

        foreach ($countries as $country) {
            $taxRateProfile->addCountry(CountryId::fromString($country));
        }

        foreach ($mapping as $originalTaxRate => $taxRate) {
            $taxRateProfile->addTaxRateDouble(
                TaxRateDouble::create(
                    $this->taxRateProfileRepository->nextTaxRateDoubleReference(),
                    $taxRateProfile->taxRateProfileId,
                    TaxRate::fromString((string) $originalTaxRate),
                    TaxRate::fromString($taxRate)
                )
            );
        }

        $this->taxRateProfileRepository->save($taxRateProfile);
    }

    public function givenACustomerExists(string $email, bool $is_business = false, string $locale = 'nl_BE'): Customer
    {
        $customer = Customer::create(
            $this->customerRepository->nextReference(),
            Email::fromString($email),
            $is_business,
            Locale::fromString($locale)
        );

        $this->customerRepository->save($customer);

        return $customer;
    }

    public function givenThereIsAPromo(array $mappedData = [], array $discounts = [])
    {
        $promo = $this->createPromo($mappedData, $discounts ?: [ $this->createDiscount([], [ $this->createCondition() ]) ]);

        $this->promoRepository->save($promo);
    }

    protected function whenIAddTheVariantToTheCart($productVariantId, $quantity = 1, array $data = [], array $personalisations = [])
    {
        $order = $this->getOrder();

        $count = count($order->getChildEntities()[Line::class]);

        // Add product to order
        $this->cartApplication->addLine(new AddLine(
            $order->orderId->get(),
            VariantId::fromString($productVariantId)->get(),
            Quantity::fromInt((int)$quantity)->asInt(),
            $personalisations,
            $data
        ));

        $checkFlag = false;
        $lines = $this->orderRepository->find($order->orderId)->getChildEntities()[Line::class];
        foreach ($lines as $line) {
            if ($line['variant_id'] == $productVariantId) {
                $checkFlag = true;
            }
        }

        if (! $checkFlag) {
            throw new \Exception('Cartitem presence check failed. No line found by ' . $productVariantId);
        }
    }

    protected function whenIAddTheFirstVariantToTheCart($productVariantId, $quantity = 1, array $data = [], array $personalisations = [])
    {
        $orderId = $this->cartApplication->createNewOrder();

        // Add product to a new order
        $this->cartApplication->addLine(new AddLine(
            $orderId->get(),
            VariantId::fromString($productVariantId)->get(),
            Quantity::fromInt((int)$quantity)->asInt(),
            $personalisations,
            $data
        ));

        $checkFlag = false;
        $lines = $this->orderRepository->find($orderId)->getChildEntities()[Line::class];
        foreach ($lines as $line) {
            if ($line['variant_id'] == $productVariantId) {
                $checkFlag = true;
            }
        }

        if (! $checkFlag) {
            throw new \Exception('Cartitem presence check failed. No line found by ' . $productVariantId);
        }
    }

    protected function whenIChangeTheProductQuantity($productTitle, $quantity)
    {
        $order = $this->getOrder();
        $lines = $order->getChildEntities()[Line::class];

        // Find matching line by productId
        $lineId = null;
        foreach ($lines as $line) {
            if ($line['variant_id'] == $productTitle) {
                $lineId = LineId::fromString($line['line_id']);
            }
        }

        $this->cartApplication->changeLineQuantity(new ChangeLineQuantity(
            $order->orderId->get(),
            $lineId->get(),
            Quantity::fromInt((int)$quantity)->asInt(),
        ));
    }

    protected function whenIRemoveTheLine($productVariantId)
    {
        $order = $this->getOrder();
        $lines = $order->getChildEntities()[Line::class];

        // Find matching line by productId
        $lineId = null;
        foreach ($lines as $line) {
            if ($line['variant_id'] == $productVariantId) {
                $lineId = LineId::fromString($line['line_id']);
            }
        }

        $this->cartApplication->removeLine(new RemoveLine(
            $order->orderId->get(),
            $lineId->get(),
        ));
    }

    protected function whenIEnterShopperDetails(string $email, bool $is_business = false, string $locale = 'nl_BE', array $data = [])
    {
        $order = $this->getOrder();

        $this->cartApplication->updateShopper(new UpdateShopper(
            $order->orderId->get(),
            $email,
            $is_business,
            $locale,
            $data,
        ));

        $this->assertNotNull($order->getShopper());
    }

    protected function whenIChooseCustomer(string $email)
    {
        $customer = $this->customerRepository->findByEmail(Email::fromString($email));

        $this->cartApplication->chooseCustomer(new ChooseCustomer(
            $this->getOrder()->orderId->get(),
            $customer->customerId->get(),
        ));
    }

    protected function thenIShouldHaveProductInTheCart($times, $quantity, string $orderId = 'xxx')
    {
        $order = $this->orderRepository->find(OrderId::fromString($orderId));
        $lines = $this->orderRepository->find($order->orderId)->getChildEntities()[Line::class];

        Assert::assertCount((int) $times, $lines);
        if (count($lines) > 0) {
            Assert::assertEquals((int) $quantity, $lines[0]['quantity']);
        }
    }

    protected function thenIShouldHaveAShippingCost($cost)
    {
        $this->assertEquals(Cash::make($cost * 100), $this->getOrder()->getShippingCost()->getIncludingVat());
    }

    protected function whenIAddShippingAddress(?string $country = null, ?string $line1 = null, ?string $line2 = null, ?string $postalCode = null, ?string $city = null)
    {
        $this->cartApplication->updateShippingAddress(new UpdateShippingAddress(
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
        $this->cartApplication->updateBillingAddress(new UpdateBillingAddress(
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
        $this->cartApplication->chooseShippingProfile(new ChooseShippingProfile(
            $this->getOrder()->orderId->get(),
            $shippingProfileId
        ));

        if (count($this->getOrder()->getShippings())) {
            $this->assertEquals($shippingProfileId, $this->getOrder()->getShippings()[0]->getShippingProfileId()->get());
        }
    }

    protected function whenIChoosePayment(string $paymentMethodId)
    {
        $this->cartApplication->choosePaymentMethod(new ChoosePaymentMethod(
            $this->getOrder()->orderId->get(),
            $paymentMethodId
        ));

        if (count($this->getOrder()->getPayments())) {
            $this->assertEquals($paymentMethodId, $this->getOrder()->getPayments()[0]->getPaymentMethodId()->get());
        }
    }

    protected function thenTheOverallCartPriceShouldBeEur($total)
    {
        Assert::assertEquals($total * 100, $this->getOrder()->getTotal()->getIncludingVat()->getAmount());
    }

    protected function thenTheCartItemShouldContainData($productVariantId, $dataKey, $dataValue)
    {
        $order = $this->orderRepository->find(OrderId::fromString('xxx'));
        $lines = $order->getLines();

        // Find matching line by variantId
        $line = null;
        foreach ($lines as $_line) {
            if ($_line->getVariantId()->get() == $productVariantId) {
                $line = $_line;
            }
        }

        if (! $line) {
            throw new \Exception('No line found by ' . $productVariantId);
        }

        $this->assertArrayHasKey($dataKey, $line->getData());
        $this->assertEquals($dataValue, $line->getData($dataKey));
    }

    protected function getOrder(): Order
    {
        // Create an order if not already
        try {
            return $this->orderRepository->find(OrderId::fromString('xxx'));
        } catch (CouldNotFindOrder $e) {
            $this->orderRepository->save($order = Order::create(
                OrderId::fromString('xxx'),
                OrderReference::fromString('xx-ref'),
                DefaultOrderState::cart_pending,
            ));

            return $order;
        }
    }
}
