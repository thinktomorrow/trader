<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;

class InvoiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_generate_a_next_invoice_reference()
    {
        foreach ($this->orderRepositories() as $orderRepository) {
            $this->assertInstanceOf(InvoiceReference::class, $orderRepository->nextInvoiceReference());
        }
    }

    /** @test */
    public function it_can_get_last_invoice_reference()
    {
        $order = $this->createOrder(['order_id' => 'yyy', 'order_ref' => 'yy-ref', 'invoice_ref' => '2208001'], [], [], [], [], null, null, $this->createOrderShopper(['shopper_id' => 'sss']));

        foreach ($this->orderRepositories() as $orderRepository) {
            $orderRepository->save($order);
            $this->assertEquals(InvoiceReference::fromString('2208001'), $orderRepository->lastInvoiceReference());
        }
    }

    private function orderRepositories(): \Generator
    {
        yield new InMemoryOrderRepository();
        yield new MysqlOrderRepository();
    }

    public function orders(): \Generator
    {
        yield $this->createDefaultOrder();

        $orderWithDiscount = $this->createDefaultOrder();
        $orderWithDiscount->addDiscount($this->createOrderDiscount(['discount_id' => 'def', 'promo_discount_id' => 'ghi'], $orderWithDiscount->getMappedData()));
        yield $orderWithDiscount;

        $orderWithLineDiscount = $this->createDefaultOrder();
        $orderWithLineDiscount->getLines()[0]->addDiscount($this->createOrderDiscount([
            'discount_id' => 'def',
            'promo_discount_id' => 'ghi',
            'discountable_id' => $orderWithLineDiscount->getLines()[0]->lineId->get(),
            'discountable_type' => DiscountableType::line->value,
        ], $orderWithLineDiscount->getMappedData()));
        yield $orderWithLineDiscount;

        $orderWithShippingDiscount = $this->createDefaultOrder();
        $orderWithShippingDiscount->getShippings()[0]->addDiscount($this->createOrderDiscount([
            'discount_id' => 'def',
            'promo_discount_id' => 'ghi',
            'discountable_id' => $orderWithShippingDiscount->getShippings()[0]->shippingId->get(),
            'discountable_type' => DiscountableType::shipping->value,
        ], $orderWithShippingDiscount->getMappedData()));
        yield $orderWithShippingDiscount;

        yield Order::create(
            OrderId::fromString('xxx'),
            OrderReference::fromString('xx-ref')
        );
    }
}
