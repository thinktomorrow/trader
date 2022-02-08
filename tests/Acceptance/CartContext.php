<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use PHPUnit\Framework\Assert;
use Thinktomorrow\Trader\Domain\Model\Order\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Application\Order\AddLine;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Application\Order\CartApplication;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\ArrayOrderRepository;
use Thinktomorrow\Trader\Domain\Model\ProductGroup\ProductGroupId;
use Thinktomorrow\Trader\Infrastructure\Test\ArrayProductRepository;

class CartContext extends FeatureContext
{
    private CartApplication $cartApplication;
    private ArrayProductRepository $productRepository;
    private ArrayOrderRepository $orderRepository;

    public function __construct()
    {
        $this->cartApplication = new CartApplication(
            $this->productRepository = new ArrayProductRepository(),
            $this->orderRepository = new ArrayOrderRepository(),
            new EventDispatcherSpy(),
        );
    }

    /**
     * @Given there is a :productName, which costs €:price
     */
    public function thereIsAWhichCostsEur($productName, $price)
    {
        // Create an order
        $this->orderRepository->save($order = Order::create(
            OrderId::fromString('xxx'),
            CustomerId::fromString('yyy'),
        ));

        // Create a product
        $this->productRepository->save($product = Product::create(
            ProductId::fromString('yyy'),
            ProductGroupId::fromString('xxx'),
        ));

        // Add product to order
        $this->cartApplication->addLine(new AddLine(
            $order->orderId->get(),
            LineNumber::fromInt(1)->asInt(),
            $product->productId->get(),
            Quantity::fromInt(2)->asInt(),
        ));

        $lines = $this->orderRepository->find($order->orderId)->getChildEntities()[Line::class];

        Assert::assertCount(1, $lines);
        Assert::assertEquals($product->productId, $lines[0]->getProductId());
        Assert::assertEquals(2, $lines[0]->getQuantity()->asInt());
    }

    /**
     * @When I add the :arg1 to the cart
     */
    public function iAddTheToTheCart($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then I should have :arg1 product in the cart
     */
    public function iShouldHaveProductInTheCart($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then the overall cart price should be €:arg1
     */
    public function theOverallCartPriceShouldBeEur($arg1)
    {
        throw new PendingException();
    }
}
