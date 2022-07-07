<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultShippingProfileForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class ShippingProfileForCartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private \Thinktomorrow\Trader\Domain\Model\Country\Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = $this->createCountry(['country_id' => 'BE']);

        (new TestContainer())->add(ShippingProfileForCart::class, DefaultShippingProfileForCart::class);
    }

    /** @test */
    public function it_can_find_profiles_for_cart()
    {
        $shippingProfile = $this->createShippingProfile();

        foreach ($this->repositories() as $i => $repository) {
            $this->countryRepositories()[$i]->save($this->country);

            $this->shippingProfileRepositories()[$i]->save($shippingProfile);

            $this->assertCount(1, $repository->findAllShippingProfilesForCart());
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryShippingProfileRepository();
        yield new MysqlShippingProfileRepository(new TestContainer());
    }

    private function shippingProfileRepositories(): array
    {
        return [
            new InMemoryShippingProfileRepository(),
            new MysqlShippingProfileRepository(new TestContainer()),
        ];
    }

    private function countryRepositories(): array
    {
        return [
            new InMemoryCountryRepository(),
            new MysqlCountryRepository(),
        ];
    }
}
