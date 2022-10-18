<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class OrderLineTest extends TestCase
{
    /** @test */
    public function it_can_add_a_line()
    {
        $order = $this->createDefaultOrder();

        $order->addOrUpdateLine(
            LineId::fromString('abcdef'),
            VariantId::fromString('xxx'),
            $linePrice = LinePrice::fromScalars('250', '9', true),
            Quantity::fromInt(2),
            ['foo' => 'bar']
        );

        $this->assertCount(2, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new LineAdded(
                $order->orderId,
                LineId::fromString('abcdef'),
                VariantId::fromString('xxx')
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_a_line()
    {
        $order = $this->createDefaultOrder();

        $order->addOrUpdateLine(
            LineId::fromString('abc'),
            VariantId::fromString('yyy'),
            $linePrice = LinePrice::fromScalars('200', '10', true),
            Quantity::fromInt(3),
            ['foo' => 'bar']
        );

        $firstLine = $order->getChildEntities()[Line::class][0];

        $this->assertCount(1, $order->getChildEntities()[Line::class]);
        $this->assertEquals('yyy', $firstLine['variant_id']);
        $this->assertEquals($linePrice->getMoney()->getAmount(), $firstLine['line_price']);
        $this->assertEquals(3, $firstLine['quantity']);
        $this->assertEquals(json_encode([
            'product_id' => 'xxx',
            'unit_price_including_vat' => '1000',
            'unit_price_excluding_vat' => '900',
            'foo' => 'bar',
            'variant_id' => $firstLine['variant_id'],
        ]), $firstLine['data']);

        $this->assertEquals([
            new LineUpdated(
                $order->orderId,
                LineId::fromString('abc'),
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_delete_a_line()
    {
        $order = $this->createDefaultOrder();

        $this->assertCount(1, $order->getChildEntities()[Line::class]);

        $order->deleteLine(
            LineId::fromString('abc'),
        );

        $this->assertCount(0, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new LineDeleted(
                $order->orderId,
                LineId::fromString('abc'),
                VariantId::fromString('yyy'),
            ),
        ], $order->releaseEvents());
    }

    public function test_it_can_update_line_quantity()
    {
        $order = $this->createDefaultOrder();
        $line = $order->getLines()[0];

        $order->updateLineQuantity($line->lineId, $quantity = Quantity::fromInt(3));
        $this->assertEquals($quantity, $line->getQuantity());
    }

    public function test_it_can_update_line_price()
    {
        $order = $this->createDefaultOrder();
        $line = $order->getLines()[0];

        $order->updateLinePrice($line->lineId, $price = LinePrice::fromMoney(Money::EUR(30), TaxRate::fromString('10'), false));
        $this->assertEquals($price, $line->getLinePrice());
    }

    public function test_it_can_update_line_personalisations()
    {
        $order = $this->createDefaultOrder();
        $line = $order->getLines()[0];

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
        $order = $this->createDefaultOrder();
        $line = $order->getLines()[0];

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
        $order = $this->createDefaultOrder();
        $line = $order->getLines()[0];

        $order->updateLineData($line->lineId, ['foo' => 'bar']);
        $this->assertEquals('bar', $line->getData('foo'));
    }

    public function test_it_can_have_a_discount()
    {
        $order = $this->createDefaultOrder();
        $line = $order->getLines()[0];
        $lineTotal = $line->getTotal();

        $this->assertEquals(LinePrice::fromMoney(Money::EUR(400), $lineTotal->getTaxRate(), $lineTotal->includesVat()), $line->getTotal());

        $line->addDiscount($this->createOrderLineDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh'], $order->getMappedData()));

        $this->assertCount(1, $line->getDiscounts());

        $this->assertEquals(LinePrice::fromMoney(Money::EUR(370), $lineTotal->getTaxRate(), $lineTotal->includesVat()), $line->getTotal());
    }
}
