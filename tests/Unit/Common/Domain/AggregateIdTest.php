<?php

use Thinktomorrow\Trader\Common\Domain\AggregateId;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class AggregateIdTest extends UnitTestCase
{
    private function getId($id = 1)
    {
        return DummyId::fromInteger($id);
    }

    /** @test */
    function it_can_get_id_as_integer()
    {
        $id = $this->getId(2);

        $this->assertEquals(2,$id->get());
        $this->assertInternalType('integer',$id->get());
    }

    /** @test */
    function it_cannot_set_by_other_type_than_integer()
    {
        $this->setExpectedException(TypeError::class);

        DummyId::fromInteger('foobar');
    }

    /** @test */
    function it_can_compair_two_objects()
    {
        $this->assertTrue($this->getId(1)->equals($this->getId(1)));
        $this->assertFalse($this->getId(1)->equals($this->getId(2)));
    }
}

class DummyId
{
    use AggregateId;
}