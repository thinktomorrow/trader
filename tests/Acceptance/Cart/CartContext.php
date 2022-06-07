<?php
declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Money\Money;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Application\Cart\CartApplication;
use Thinktomorrow\Trader\Application\Cart\Line\RemoveLine;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Application\Cart\ChooseShippingProfile;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Application\Cart\ChooseShippingCountry;
use Thinktomorrow\Trader\Application\Cart\Line\ChangeLineQuantity;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffNumber;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Application\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryShippingProfileRepository;

abstract class CartContext extends TestCase
{
    protected CartApplication $cartApplication;
    protected InMemoryProductRepository $productRepository;
    protected InMemoryOrderRepository $orderRepository;
    protected InMemoryShippingProfileRepository $shippingProfileRepository;
    protected InMemoryVariantRepository $variantRepository;

    public function setUp(): void
    {
        $this->cartApplication = new CartApplication(
            new TestTraderConfig(),
            $this->variantRepository = new InMemoryVariantRepository(),
            $this->orderRepository = new InMemoryOrderRepository(),
            $this->shippingProfileRepository = new InMemoryShippingProfileRepository(),
            new InMemoryPaymentMethodRepository(),
            new InMemoryCustomerRepository(),
            new EventDispatcherSpy(),
        );

        $this->productRepository = new InMemoryProductRepository();

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
    }

    protected function givenThereIsAProductWhichCostsEur($productTitle, $price)
    {
        // Create a product
        $product = Product::create(ProductId::fromString($productTitle));
        $product->createVariant(Variant::create(
            ProductId::fromString($productTitle),
            VariantId::fromString($productTitle . '-123'),
            VariantUnitPrice::fromMoney(
                Cash::make(1000), TaxRate::fromString('20'), true
            ),
            VariantSalePrice::fromMoney(Money::EUR($price * 100), TaxRate::fromString('20'), true),
        ));

        $this->productRepository->save($product);

        Assert::assertNotNull($this->productRepository->find(ProductId::fromString($productTitle)));
        Assert::assertNotNull($this->variantRepository->findVariantForCart(VariantId::fromString($productTitle . '-123')));
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
        foreach($lines as $line) {
            if($line['variant_id'] == $productVariantId) {
                $checkFlag = true;
            }
        }

        if(!$checkFlag){
            throw new \Exception('Cartitem presence check failed. No line found by ' . $productVariantId);
        }
    }

    protected function whenIChangeTheProductQuantity($productTitle, $quantity)
    {
        $order = $this->getOrder();
        $lines = $order->getChildEntities()[Line::class];

        // Find matching line by productId
        $lineId = null;
        foreach($lines as $line) {
            if($line['variant_id'] == $productTitle) {
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
        foreach($lines as $line) {
            if($line['variant_id'] == $productVariantId) {
                $lineId = LineId::fromString($line['line_id']);
            }
        }

        $this->cartApplication->removeLine(new RemoveLine(
            $order->orderId->get(),
            $lineId->get(),
        ));
    }

    protected function thenIShouldHaveProductInTheCart($times, $quantity)
    {
        $order = $this->orderRepository->find(OrderId::fromString('xxx'));
        $lines = $this->orderRepository->find($order->orderId)->getChildEntities()[Line::class];

        Assert::assertCount((int) $times, $lines);
        if(count($lines) > 0) {
            Assert::assertEquals((int) $quantity, $lines[0]['quantity']);
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
        foreach($lines as $_line) {
            if($_line->getVariantId()->get() == $productVariantId) {
                $line = $_line;
            }
        }

        if(!$line) {
            throw new \Exception('No line found by ' . $productVariantId);
        }

        $this->assertArrayHasKey($dataKey, $line->getData());
        $this->assertEquals($dataValue, $line->getData($dataKey));
    }

    protected function getOrder(): Order
    {
        // Create an order if not already
        try{
            return $this->orderRepository->find(OrderId::fromString('xxx'));
        } catch (CouldNotFindOrder $e) {
            $this->orderRepository->save($order = Order::create(
                OrderId::fromString('xxx'),
            ));

            return $order;
        }
    }
}
