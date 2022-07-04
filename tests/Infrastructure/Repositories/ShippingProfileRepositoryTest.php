<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Money\Money;
use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryShippingProfileRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;

class ShippingProfileRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private \Thinktomorrow\Trader\Domain\Model\Country\Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = $this->createCountry(['country_id' => 'BE']);
    }

    /**
     * @test
     * @dataProvider shippingProfiles
     */
    public function it_can_save_and_find_a_profile(ShippingProfile $shippingProfile)
    {
        foreach ($this->repositories() as $i => $repository) {

            $this->countryRepositories()[$i]->save($this->country);

            $repository->save($shippingProfile);
            $shippingProfile->releaseEvents();

            $this->assertEquals($shippingProfile, $repository->find($shippingProfile->shippingProfileId));
        }
    }

    /**
     * @test
     * @dataProvider shippingProfiles
     */
    public function it_can_delete_a_product(ShippingProfile $shippingProfile)
    {
        $profilesNotFound = 0;

        foreach ($this->repositories() as $repository) {
            $repository->save($shippingProfile);
            $repository->delete($shippingProfile->shippingProfileId);

            try {
                $repository->find($shippingProfile->shippingProfileId);
            } catch (CouldNotFindShippingProfile $e) {
                $profilesNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $profilesNotFound);
    }

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(ShippingProfileId::class, $repository->nextReference());
        }
    }

    /** @test */
    public function it_can_get_available_shipping_countries()
    {
        foreach ($this->repositories() as $i => $repository) {

            $this->countryRepositories()[$i]->save($this->country);
            $country2 = $this->createCountry(['country_id' => 'NL']);
            $this->countryRepositories()[$i]->save($country2);

            $profile = $this->createShippingProfile();
            $profile->addCountry(CountryId::fromString('BE'));

            $repository->save($profile);

            $this->assertEquals([
                $this->country,
            ], $repository->getAvailableShippingCountries());
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryShippingProfileRepository();
        yield new MysqlShippingProfileRepository();
    }

    private function countryRepositories(): array
    {
        return [
            new InMemoryCountryRepository(),
            new MysqlCountryRepository()
        ];
    }

    public function shippingProfiles(): \Generator
    {
        yield [$this->createShippingProfile()];

        $profile = $this->createShippingProfile();
        $profile->addCountry(CountryId::fromString('BE'));
        $profile->addTariff(Tariff::create($profile->shippingProfileId, Money::EUR(10), Money::EUR(20), Money::EUR(30)));

        yield [$profile];
    }
}
