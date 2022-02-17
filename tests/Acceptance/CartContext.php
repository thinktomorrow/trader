<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use Money\Money;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\Line;
use Thinktomorrow\Trader\Application\Cart\AddLine;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\Product\ProductSalePrice;
use Thinktomorrow\Trader\Application\Cart\ChooseShippingProfile;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Application\Cart\CartApplication;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Domain\Model\Product\ProductUnitPrice;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Application\Cart\ChooseShippingCountry;
use Thinktomorrow\Trader\Domain\Model\ProductGroup\ProductGroupId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffNumber;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryShippingRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Infrastructure\Test\ArrayOrderDetailsRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Application\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryShippingProfileRepository;

abstract class CartContext extends TestCase
{
    protected CartApplication $cartApplication;
    protected InMemoryProductRepository $productRepository;
    protected InMemoryOrderRepository $orderRepository;
    protected ArrayOrderDetailsRepository $orderDetailsRepository;
    protected InMemoryShippingProfileRepository $shippingProfileRepository;
    private InMemoryShippingRepository $shippingRepository;

    public function setUp(): void
    {
        $container = new TestContainer();

        // ProductRepository $productRepository,
        //        OrderRepository $orderRepository,
        //        OrderDetailsRepository $orderDetailsRepository,
        //        ShippingRepository $shippingRepository,
        //        ShippingProfileRepository $shippingProfileRepository,
        //        EventDispatcher $eventDispatcher

        $this->cartApplication = new CartApplication(
            new TestTraderConfig(),
            $this->productRepository = new InMemoryProductRepository(),
            $this->orderRepository = new InMemoryOrderRepository(),
            $this->shippingRepository = new InMemoryShippingRepository(),
            $this->shippingProfileRepository = new InMemoryShippingProfileRepository(),
            new InMemoryPaymentMethodRepository(),
            new EventDispatcherSpy(),
        );

        // Container bindings
        (new TestContainer())->add(AdjustShipping::class, new AdjustShipping(
            $this->shippingProfileRepository,
        ));
    }

    public function tearDown(): void
    {
        $this->productRepository->clear();
        $this->orderRepository->clear();
        $this->shippingRepository->clear();
        $this->shippingProfileRepository->clear();
    }

    protected function givenThereIsAProductWhichCostsEur($productTitle, $price)
    {
        // Create a product
        $this->productRepository->save(Product::create(
            ProductId::fromString($productTitle),
            ProductGroupId::fromString('xxx'),
            ProductUnitPrice::fromMoney(
                Cash::make(1000), TaxRate::fromString('20'), true
            ),
            ProductSalePrice::fromMoney(Money::EUR($price * 100), TaxRate::fromString('20'), true),
        ));

        Assert::assertNotNull($this->productRepository->find(ProductId::fromString($productTitle)));
    }

    public function givenOrderHasAShippingCountry(string $country)
    {
        $order = $this->getOrder();

        $this->cartApplication->chooseShippingCountry(new ChooseShippingCountry($order->orderId->get(), $country));
    }

    public function givenShippingCostsForAPurchaseOfEur($shippingCost, $from, $to, array $countries = ['BE'])
    {
        $shippingProfile = ShippingProfile::create(ShippingProfileId::fromString('bpost_home'));

        foreach($countries as $country) {
            $shippingProfile->addCountry(ShippingCountry::fromString($country));
        }

        $shippingProfile->addTariff(
            TariffNumber::fromInt(1),
            Cash::make($shippingCost * 100),
            Cash::make($from * 100),
            Cash::make($to * 100),
        );

        $this->shippingProfileRepository->save($shippingProfile);

        $order = $this->getOrder();

        // Choose this shipping profile for the cart
        $this->cartApplication->chooseShippingProfile(new ChooseShippingProfile($order->orderId->get(), $shippingProfile->shippingProfileId->get()));
    }

    protected function whenIAddTheProductToTheCart($productTitle, $quantity)
    {
        $order = $this->getOrder();

        $count = count($order->getChildEntities()[Line::class]);

        // Add product to order
        $this->cartApplication->addLine(new AddLine(
            $order->orderId->get(),
            LineNumber::fromInt($count + 1)->asInt(),
            ProductId::fromString($productTitle)->get(),
            Quantity::fromInt((int)$quantity)->asInt(),
        ));

        $lines = $this->orderRepository->find($order->orderId)->getChildEntities()[Line::class];
        Assert::assertEquals(ProductId::fromString($productTitle)->get(), $lines[$count]['product_id']);
    }

    protected function thenIShouldHaveProductInTheCart($times, $quantity)
    {
        $order = $this->orderRepository->find(OrderId::fromString('xxx'));
        $lines = $this->orderRepository->find($order->orderId)->getChildEntities()[Line::class];

        Assert::assertCount((int) $times, $lines);
        Assert::assertEquals((int) $quantity, $lines[0]['quantity']);
    }

    protected function thenTheOverallCartPriceShouldBeEur($total)
    {
        Assert::assertEquals($total * 100, $this->getOrder()->getTotal()->getIncludingVat()->getAmount());
    }

    protected function getOrder(): Order
    {
        // Create an order if not already
        try{
            return $this->orderRepository->find(OrderId::fromString('xxx'));
        } catch (CouldNotFindOrder $e) {
            $this->orderRepository->save($order = Order::create(
                OrderId::fromString('xxx'),
                CustomerId::fromString('yyy'),
            ));

            return $order;
        }
    }
}
