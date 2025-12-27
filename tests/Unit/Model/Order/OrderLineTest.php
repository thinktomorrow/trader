<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;

class OrderLineTest extends TestCase
{
    public function test_it_can_add_a_line()
    {
        $order = $this->orderContext->createDefaultOrder();
        $line = $this->orderContext->createLine($order->orderId->get(), 'line-ccc');

        $order->addOrUpdateLine($line);

        $this->assertCount(3, $order->getLines());

        $this->assertEquals(
            new LineAdded(
                $order->orderId,
                LineId::fromString('order-aaa:line-ccc'),
                PurchasableReference::fromString('variant@variant-aaa')
            ),
            last($order->releaseEvents())
        );
    }

    public function test_it_can_update_a_line()
    {
        $order = $this->orderContext->createDefaultOrder();
        $line = $this->orderContext->createLine($order->orderId->get(), 'line-aaa', [
            'line_price' => 200,
        ]);

        $order->addOrUpdateLine($line);

        $this->assertCount(2, $order->getLines());

        $foundLine = $order->findLine($line->lineId);

        $this->assertEquals(DefaultItemPrice::fromExcludingVat(Money::EUR(83), VatPercentage::fromString('21')), $foundLine->getLinePrice());

        $this->assertEquals(
            new LineUpdated(
                $order->orderId,
                LineId::fromString('order-aaa:line-aaa'),
            ),
            last($order->releaseEvents())
        );
    }

    public function test_it_can_delete_a_line()
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->assertCount(2, $order->getLines());

        $order->deleteLine(
            LineId::fromString('order-aaa:line-aaa'),
        );

        $this->assertCount(1, $order->getLines());
        $this->assertInstanceOf(Line::class, $order->findLine(LineId::fromString('order-aaa:line-bbb')));

        $this->assertEquals(
            new LineDeleted(
                $order->orderId,
                LineId::fromString('order-aaa:line-aaa'),
                PurchasableReference::fromString('variant@variant-aaa'),
            )
            , last($order->releaseEvents())
        );
    }

    public function test_it_can_update_line_quantity()
    {
        $order = $this->orderContext->createDefaultOrder();
        $line = $order->findLine(LineId::fromString('order-aaa:line-aaa'));

        $order->updateLineQuantity($line->lineId, $quantity = Quantity::fromInt(3));
        $this->assertEquals($quantity, $line->getQuantity());
    }

    public function test_it_can_mark_as_reduced_from_stock()
    {
        $order = $this->orderContext->createDefaultOrder();
        $line = $order->findLine(LineId::fromString('order-aaa:line-aaa'));

        $this->assertFalse($line->reducedFromStock());

        $line->reduceFromStock();

        $this->assertTrue($line->reducedFromStock());
    }

    public function test_it_can_update_line_price()
    {
        $order = $this->orderContext->createDefaultOrder();
        $line = $order->findLine(LineId::fromString('order-aaa:line-aaa'));

        $order->updateLinePrice($line->lineId, $price = DefaultItemPrice::fromMoney(Money::EUR(30), VatPercentage::fromString('10'), false));
        $this->assertEquals($price, $line->getLinePrice());
    }

    public function test_it_can_update_line_personalisations()
    {
        $order = $this->orderContext->createDefaultOrder();
        $line = $order->findLine(LineId::fromString('order-aaa:line-aaa'));

        $order->updateLinePersonalisations($line->lineId, [
            $personalisation = LinePersonalisation::create(
                LineId::fromString('xxx'),
                LinePersonalisationId::fromString('aaa'),
                PersonalisationId::fromString('bbb'),
                PersonalisationType::fromString(PersonalisationType::TEXT),
                'value',
                []
            ),
        ]);
        $this->assertEquals([$personalisation], $line->getPersonalisations());
    }

    public function test_it_cannot_update_line_personalisations_when_line_is_not_found()
    {
        $order = $this->orderContext->createDefaultOrder();
        $line = $order->findLine(LineId::fromString('order-aaa:line-aaa'));

        $order->updateLinePersonalisations(LineId::fromString('unknown'), [
            LinePersonalisation::create(
                LineId::fromString('xxx'),
                LinePersonalisationId::fromString('aaa'),
                PersonalisationId::fromString('bbb'),
                PersonalisationType::fromString(PersonalisationType::TEXT),
                'value',
                []
            ),
        ]);
        $this->assertEquals([], $line->getPersonalisations());
    }

    public function test_it_can_update_line_data()
    {
        $order = $this->orderContext->createDefaultOrder();
        $line = $order->findLine(LineId::fromString('order-aaa:line-aaa'));

        $order->updateLineData($line->lineId, ['foo' => 'bar']);
        $this->assertEquals('bar', $line->getData('foo'));
    }

    public function test_it_can_add_a_discount_to_payment()
    {
        $order = $this->orderContext->createDefaultOrder();
        $line = $order->findLine(LineId::fromString('order-aaa:line-aaa'));
        $discount = $this->orderContext->createLineDiscount();
        $line->addDiscount($discount);

        $this->assertEquals(DefaultItemPrice::fromExcludingVat(Money::EUR(83), VatPercentage::fromString('21')), $line->getLinePrice());
        $this->assertEquals(DefaultDiscountPrice::fromExcludingVat(Money::EUR(15)), $line->getSumOfDiscountPrices());
        $this->assertEquals(DefaultItemPrice::fromExcludingVat(Money::EUR(68), VatPercentage::fromString('21')), $line->getTotal());
    }
}
