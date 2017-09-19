<?php

use Thinktomorrow\Trader\Common\Domain\AggregateId;
use Thinktomorrow\Trader\Tests\UnitTestCase;

class AggregateIdTest extends UnitTestCase
{
    private function getId($id = 1)
    {
        return DummyId::fromInteger($id);
    }

    /** @test */
    public function it_can_get_id_as_integer()
    {
        $id = $this->getId(2);

        $this->assertEquals(2, $id->get());
        $this->assertInternalType('integer', $id->get());
    }

    /** @test */
    public function it_can_be_set_from_string()
    {
        $id = DummyId::fromString('foobar');

        $this->assertEquals('foobar', $id->get());
        $this->assertInternalType('string', $id->get());
    }

    /** @test */
    public function it_cannot_set_by_other_type_than_integer()
    {
        $this->expectException(TypeError::class);

        DummyId::fromInteger('foobar');
    }

    /** @test */
    public function it_can_compair_two_objects()
    {
        $this->assertTrue($this->getId(1)->equals($this->getId(1)));
        $this->assertFalse($this->getId(1)->equals($this->getId(2)));
    }
}

class DummyId
{
    use AggregateId;
}
