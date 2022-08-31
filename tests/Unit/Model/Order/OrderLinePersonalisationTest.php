<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;

final class OrderLinePersonalisationTest extends TestCase
{
    private \Thinktomorrow\Trader\Domain\Model\Order\Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = $this->createDefaultOrder();
    }

    public function test_it_can_create_a_personalisation()
    {
        $personalisation = LinePersonalisation::create(
            $lineId = LineId::fromString('xxx'),
            $linePersonalisationId = LinePersonalisationId::fromString('aaa'),
            $personalisationId = PersonalisationId::fromString('bbb'),
            $type = PersonalisationType::fromString(PersonalisationType::TEXT),
            $value = 'value',
            []
        );

        $this->assertEquals($lineId, $personalisation->lineId);
        $this->assertEquals($linePersonalisationId, $personalisation->linePersonalisationId);
        $this->assertEquals($personalisationId, $personalisation->originalPersonalisationId);
        $this->assertEquals($type, $personalisation->getType());
        $this->assertEquals($value, $personalisation->getValue());
    }

    public function test_it_can_create_from_mapped_data()
    {
        $personalisation = LinePersonalisation::fromMappedData([
            'line_personalisation_id' => 'aaa',
            'personalisation_id' => 'bbb',
            'personalisation_type' => 'text',
            'value' => 'value',
            'data' => json_encode(['foo' => 'bar']),
        ], ['line_id' => 'xxx']);

        $this->assertEquals([
            'line_id' => 'xxx',
            'line_personalisation_id' => 'aaa',
            'personalisation_id' => 'bbb',
            'personalisation_type' => 'text',
            'value' => 'value',
            'data' => json_encode(['foo' => 'bar']),
        ], $personalisation->getMappedData());
    }

    /** @test */
    public function it_can_add_personalisation()
    {
        $line = $this->order->getLines()[0];

        $line->addPersonalisation($personalisation = $this->createLinePersonalisation());

        $this->assertEquals([
            $personalisation->getMappedData(),
        ], $line->getChildEntities()[LinePersonalisation::class]);
    }

    /** @test */
    public function it_can_get_all_personalisations()
    {
        $line = $this->order->getLines()[0];

        $line->addPersonalisation($this->createLinePersonalisation());
        $line->addPersonalisation($this->createLinePersonalisation());

        $this->assertCount(2, $line->getPersonalisations());
    }

    /** @test */
    public function it_can_delete_personalisations()
    {
        $line = $this->order->getLines()[0];

        $line->addPersonalisation($this->createLinePersonalisation(['line_personalisation_id' => 'aaa']));
        $line->addPersonalisation($this->createLinePersonalisation(['line_personalisation_id' => 'bbb']));

        $this->assertCount(2, $line->getPersonalisations());

        $line->deletePersonalisation(LinePersonalisationId::fromString('aaa'));

        $this->assertCount(1, $line->getPersonalisations());
    }

    private function createLinePersonalisation(array $values = []): LinePersonalisation
    {
        return LinePersonalisation::fromMappedData(
            array_merge([
                'line_personalisation_id' => 'aaa',
                'personalisation_id' => 'bbb',
                'personalisation_type' => 'text',
                'value' => 'value',
                'data' => json_encode(['foo' => 'bar']),
            ], $values),
            ['line_id' => 'xxx']
        );
    }
}
