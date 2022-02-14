<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Application\Cart\AddLine;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Order\Entity\Line;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Order\Entity\Order;
use Thinktomorrow\Trader\Application\Cart\ChooseShipping;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Application\Cart\CartApplication;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Shipping\Entity\Rule;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingTotal;
use Thinktomorrow\Trader\Domain\Model\Product\ProductUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Shipping\Entity\Shipping;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryOrderRepository;
use Thinktomorrow\Trader\Domain\Model\ProductGroup\ProductGroupId;
use Thinktomorrow\Trader\Infrastructure\Test\ArrayProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryShippingRepository;
use Thinktomorrow\Trader\Application\Cart\Adjusters\AdjustShippingTotal;
use Thinktomorrow\Trader\Infrastructure\Test\ArrayOrderDetailsRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;

abstract class CartContext extends TestCase
{
    protected CartApplication $cartApplication;
    protected ArrayProductRepository $productRepository;
    protected InMemoryOrderRepository $orderRepository;
    protected ArrayOrderDetailsRepository $orderDetailsRepository;
    protected InMemoryShippingRepository $shippingRepository;

    public function setUp(): void
    {
        $this->cartApplication = new CartApplication(
            new TestContainer(),
            $this->productRepository = new ArrayProductRepository(),
            $this->orderRepository = new InMemoryOrderRepository(),
            $this->orderDetailsRepository = new ArrayOrderDetailsRepository($this->orderRepository, $this->productRepository),
            $this->shippingRepository = new InMemoryShippingRepository(),
            new EventDispatcherSpy(),
        );

        // Container bindings
        (new TestContainer())->add(AdjustShippingTotal::class, new AdjustShippingTotal(
            $this->shippingRepository,
        ));
    }

    public function tearDown(): void
    {
        $this->orderRepository->clear();
        $this->shippingRepository->clear();
    }

    protected function givenThereIsAProductWhichCostsEur($productTitle, $price)
    {
        // Create a product
        $this->productRepository->save(Product::create(
            ProductId::fromString($productTitle),
            ProductGroupId::fromString('xxx'),
            ProductUnitPrice::fromMoney(
                Cash::make($price * 100), TaxRate::fromString('20'), true
            )
        ));

        Assert::assertNotNull($this->productRepository->find(ProductId::fromString($productTitle)));
    }

    public function givenShippingCostsForAPurchaseOfEur($shippingCost, $from, $to, array $countries = ['BE'])
    {
        $shipping = Shipping::create(ShippingId::fromString('bpost_home'), [
            Rule::create(
                ShippingId::fromString('bpost_home'),
                ShippingTotal::fromScalars($shippingCost * 100, 'EUR', '8', true),
                SubTotal::fromScalars($from * 100, 'EUR', '10', true),
                SubTotal::fromScalars($to * 100, 'EUR', '10', true),
                $countries
            )
        ]);

        $this->shippingRepository->save($shipping);

        $order = $this->getOrder();

        // Choose this shipping for the cart
        $this->cartApplication->chooseShipping(new ChooseShipping($order->orderId->get(), $shipping->shippingId->get()));
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
        Assert::assertEquals(ProductId::fromString($productTitle), $lines[$count]->getProductId());
    }

    protected function thenIShouldHaveProductInTheCart($times, $quantity)
    {
        $order = $this->orderRepository->find(OrderId::fromString('xxx'));
        $lines = $this->orderRepository->find($order->orderId)->getChildEntities()[Line::class];

        Assert::assertCount((int) $times, $lines);
        Assert::assertEquals((int) $quantity, $lines[0]->getQuantity()->asInt());
    }

    protected function thenTheOverallCartPriceShouldBeEur($total)
    {
        $cart = $this->orderDetailsRepository->find(OrderId::fromString('xxx'));

        Assert::assertEquals($total * 100, $cart->getTotal()->getIncludingVat()->getAmount());
    }

    private function getOrder(): Order
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
