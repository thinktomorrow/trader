<?php

namespace Thinktomorrow\Trader\Tests\Common;

use Thinktomorrow\Trader\Common\Ports\Web\GetDynamicValue;
use Thinktomorrow\Trader\Tests\UnitTestCase;

class GetDynamicValueTest extends UnitTestCase
{
    private $stub;

    public function setUp()
    {
        parent::setUp();

        $this->stub = new class() {
            private $values;
            use GetDynamicValue;

            public function __construct()
            {
                $this->values = [
                    'foo'  => 'bar',
                    'zoo'  => ['horror' => 'show'],
                    'hell' => (object) ['raiser' => (object) ['box' => 'never touch it']],
                ];
            }
        };
    }

    /** @test */
    public function it_can_retrieve_value_as_property()
    {
        $this->assertEquals('bar', $this->stub->foo);
    }

    /** @test */
    public function it_can_retrieve_value_as_method()
    {
        $this->assertEquals('bar', $this->stub->foo());
    }

    /** @test */
    public function unknown_value_gives_null()
    {
        $this->assertNull($this->stub->unknown);
        $this->assertNull($this->stub->unknown());
    }

    /** @test */
    public function fake_method_is_only_assumed_when_no_arguments_are_passed()
    {
        $this->expectException(\RuntimeException::class);
        $this->stub->foo('fake');
    }

    /** @test */
    public function value_can_be_retrieved_via_get_method()
    {
        $this->assertEquals('bar', $this->stub->getValue('foo'));
    }

    /** @test */
    public function unknown_value_can_have_specific_default()
    {
        $this->assertEquals('hello', $this->stub->getValue('crazy', 'hello'));
    }

    /** @test */
    public function nested_value_can_be_retrieved_via_dot_syntax()
    {
        $this->assertEquals('show', $this->stub->getValue('zoo.horror'));
    }

    /** @test */
    public function not_found_nested_value_returns_default()
    {
        $this->assertEquals('clown', $this->stub->getValue('zoo.horrific', 'clown'));
    }

    /** @test */
    public function nested_value_in_object_can_be_retrieved_via_dot_syntax()
    {
        $this->assertEquals('never touch it', $this->stub->getValue('hell.raiser.box'));
    }

    /** @test */
    public function camelcased_also_works_for_nested_value_retrieval()
    {
        $this->assertEquals('show', $this->stub->getValue('zooHorror'));
        $this->assertEquals('never touch it', $this->stub->getValue('hellRaiserBox'));

        $this->assertEquals('show', $this->stub->zooHorror);
        $this->assertEquals('never touch it', $this->stub->hellRaiserBox);
    }

    /** @test */
    public function value_can_be_manipulated_at_runtime()
    {
        $this->assertSame('BAR', $this->stub->getValue('foo', null, function ($value) {
            return strtoupper($value);
        }));
    }
}
