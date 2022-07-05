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
use Thinktomorrow\Trader\Application\Cart\Line\AddLineToNewOrder;
use Thinktomorrow\Trader\Application\Cart\Line\ChangeLineQuantity;
use Thinktomorrow\Trader\Application\Cart\Line\RemoveLine;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustDiscounts;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustLines;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCartAction;
use Thinktomorrow\Trader\Application\Cart\UpdateBillingAddress;
use Thinktomorrow\Trader\Application\Cart\UpdateShippingAddress;
use Thinktomorrow\Trader\Application\Cart\UpdateShopper;
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
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
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
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCartRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryMerchantOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPromoRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryShippingProfileRepository;
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
    protected InMemoryVariantRepository $variantRepository;
    protected InMemoryCartRepository $cartRepository;
    protected InMemoryPaymentMethodRepository $paymentMethodRepository;
    protected InMemoryCustomerRepository $customerRepository;
    protected InMemoryPromoRepository $promoRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = new InMemoryProductRepository();
        $this->cartRepository = new InMemoryCartRepository();
        $this->merchantOrderRepository = new InMemoryMerchantOrderRepository();
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

        $this->orderRepository = new InMemoryOrderRepository();

        // Adjusters are loaded via container so set them up here
        (new TestContainer())->add(ApplyPromoToOrder::class, new ApplyPromoToOrder($this->orderRepository));
        (new TestContainer())->add(AdjustLines::class, new AdjustLines(new InMemoryVariantRepository()));
        (new TestContainer())->add(AdjustDiscounts::class, new AdjustDiscounts($this->promoRepository, (new TestContainer())->get(ApplyPromoToOrder::class)));

        $this->cartApplication = new CartApplication(
            new TestTraderConfig(),
            new TestContainer(),
            $this->variantRepository = new InMemoryVariantRepository(),
            $this->orderRepository,
            new RefreshCartAction(),
            $this->shippingProfileRepository = new InMemoryShippingProfileRepository(),
            $this->paymentMethodRepository = new InMemoryPaymentMethodRepository(),
            $this->customerRepository = new InMemoryCustomerRepository(),
            new EventDispatcherSpy(),
        );

        $this->promoApplication = new CouponPromoApplication(
            new TestTraderConfig(),
            new TestContainer(),
            $this->orderRepository,
            $this->promoRepository,
            (new TestContainer())->get(ApplyPromoToOrder::class),
            new EventDispatcherSpy(),
        );

        // Container bindings
        (new TestContainer())->add(AdjustShipping::class, new AdjustShipping(
            $this->shippingProfileRepository,
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
        );

        $variant->addData(['title' => ['nl' => $productTitle . ' variant']]);
        $product->createVariant($variant);

        $this->productRepository->save($product);

        Assert::assertNotNull($this->productRepository->find(ProductId::fromString($productTitle)));
        Assert::assertNotNull($this->variantRepository->findVariantForCart(VariantId::fromString($productTitle . '-123')));
    }

    public function givenOrderHasAShippingCountry(string $country)
    {
        $order = $this->getOrder();

        $this->cartApplication->updateShippingAddress(new UpdateShippingAddress($order->orderId->get(), $country));
    }

    public function givenShippingCostsForAPurchaseOfEur($shippingCost, $from, $to, array $countries = ['BE'], string $shippingProfileId = 'bpost_home')
    {
        $shippingProfile = ShippingProfile::create(ShippingProfileId::fromString($shippingProfileId));
        $shippingProfile->addData(['title' => ['nl' => Str::headline($shippingProfileId)]]);

        foreach ($countries as $country) {
            $shippingProfile->addCountry(CountryId::fromString($country));
        }

        $shippingProfile->addTariff(
            Tariff::create(
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
        $paymentMethod = PaymentMethod::create(PaymentMethodId::fromString($paymentMethodId), Cash::make($paymentRate * 100));
        $paymentMethod->addData(['title' => ['nl' => Str::headline($paymentMethodId)]]);

        $this->paymentMethodRepository->save($paymentMethod);
    }

    public function givenACustomerExists(string $email, bool $is_business = false, string $locale = 'nl_BE')
    {
        $customer = Customer::create(
            $this->customerRepository->nextReference(),
            Email::fromString($email),
            $is_business,
            Locale::fromString($locale)
        );

        $this->customerRepository->save($customer);
    }

    public function givenThereIsAPromo(array $mappedData = [], array $discounts = [])
    {
        $promo = $this->createPromo($mappedData, $discounts ?: [ $this->createDiscount([], [ $this->createCondition() ]) ]);

        $this->promoRepository->save($promo);
    }

    protected function whenIAddTheVariantToTheCart($productVariantId, $quantity = 1, array $data = [])
    {
        $order = $this->getOrder();

        $count = count($order->getChildEntities()[Line::class]);

        // Add product to order
        $this->cartApplication->addLine(new AddLine(
            $order->orderId->get(),
            VariantId::fromString($productVariantId)->get(),
            Quantity::fromInt((int)$quantity)->asInt(),
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

    protected function whenIAddTheFirstVariantToTheCart($productVariantId, $quantity = 1, array $data = [])
    {
        // Add product to a new order
        $orderId = $this->cartApplication->addLineToNewOrder(new AddLineToNewOrder(
            VariantId::fromString($productVariantId)->get(),
            Quantity::fromInt((int)$quantity)->asInt(),
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

        $this->assertEquals($shippingProfileId, $this->getOrder()->getShippings()[0]->getShippingProfileId()->get());
    }

    protected function whenIChoosePayment(string $paymentMethodId)
    {
        $this->cartApplication->choosePaymentMethod(new ChoosePaymentMethod(
            $this->getOrder()->orderId->get(),
            $paymentMethodId
        ));

        $this->assertEquals($paymentMethodId, $this->getOrder()->getPayment()->getPaymentMethodId()->get());
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
                OrderReference::fromString('xx-ref')
            ));

            return $order;
        }
    }
}
