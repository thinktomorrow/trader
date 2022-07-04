<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class CountryTest extends TestCase
{
    use TestHelpers;

    /** @test */
    public function it_can_create_a_country()
    {
        $country = Country::create(
            $countryId = CountryId::fromString('yyy'),
            ['foo' => 'bar']
        );

        $this->assertEquals([
            'country_id' => $countryId->get(),
            'data' => json_encode(['foo' => 'bar']),
        ], $country->getMappedData());
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $country = $this->createCountry(['data' => json_encode(['foo' => 'bar'])]);

        $this->assertEquals(CountryId::fromString('BE'), $country->countryId);
        $this->assertEquals('bar', $country->getData('foo'));
    }

    /** @test */
    public function adding_data_merges_with_existing_data()
    {
        $country = $this->createCountry();

        $country->addData(['bar' => 'baz']);
        $country->addData(['foo' => 'bar', 'bar' => 'boo']);

        $this->assertEquals(json_encode(['bar' => 'boo', 'foo' => 'bar']), $country->getMappedData()['data']);
    }

    /** @test */
    public function it_can_delete_data()
    {
        $country = $this->createCountry();

        $country->addData(['foo' => 'bar', 'bar' => 'boo']);
        $country->deleteData('bar');

        $this->assertEquals(json_encode(['foo' => 'bar']), $country->getMappedData()['data']);
    }
}
