<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Country\Exceptions\CouldNotFindCountry;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCountryRepository;

class CountryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider countries
     */
    public function it_can_save_and_find_a_country(Country $country)
    {
        foreach ($this->repositories() as $repository) {
            $repository->save($country);
            $country->releaseEvents();

            $this->assertEquals($country, $repository->find($country->countryId));
        }
    }

    /**
     * @test
     * @dataProvider countries
     */
    public function it_can_delete_a_country(Country $country)
    {
        $countrysNotFound = 0;

        foreach ($this->repositories() as $repository) {
            $repository->save($country);
            $repository->delete($country->countryId);

            try {
                $repository->find($country->countryId);
            } catch (CouldNotFindCountry $e) {
                $countrysNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $countrysNotFound);
    }

    private function repositories(): \Generator
    {
        yield new InMemoryCountryRepository();
        yield new MysqlCountryRepository();
    }

    public function countries(): \Generator
    {
        yield [$this->createCountry()];

        $country = $this->createCountry(['data' => json_encode(['foo' => 'bar'])]);
        yield [$country];
    }
}
