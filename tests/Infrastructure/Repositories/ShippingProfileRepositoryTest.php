<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Money\Money;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class ShippingProfileRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use PrepareWorld;

    private \Thinktomorrow\Trader\Domain\Model\Country\Country $country;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     * @dataProvider shippingProfiles
     */
    public function it_can_save_and_find_a_profile(ShippingProfile $shippingProfile)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

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

        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);
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
            $this->prepareCountries($i);

            $profile = $this->createShippingProfile();
            $profile->addCountry(CountryId::fromString('BE'));

            $repository->save($profile);

            $this->assertEquals([
                \Thinktomorrow\Trader\Application\Country\Country::fromMappedData($this->createCountry(['country_id' => 'BE'])->getMappedData()),
            ], $repository->getAvailableShippingCountries());
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryShippingProfileRepository();
        yield new MysqlShippingProfileRepository(new TestContainer());
    }

    public function shippingProfiles(): \Generator
    {
        yield [$this->createShippingProfile()];

        $profile = $this->createShippingProfile();
        $profile->addCountry(CountryId::fromString('BE'));
        $profile->addTariff(Tariff::create(TariffId::fromString('xxx'), $profile->shippingProfileId, Money::EUR(10), Money::EUR(20), Money::EUR(30)));

        yield [$profile];
    }
}
