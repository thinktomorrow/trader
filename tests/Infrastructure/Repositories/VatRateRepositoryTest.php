<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\CouldNotFindVatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVatRateRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVatRateRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\TraderConfig;

class VatRateRepositoryTest extends TestCase
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
     * @dataProvider vatRates
     */
    public function it_can_save_and_find_a_vat_rate(VatRate $vatRate, ?VatRate $originVatRate = null)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            if ($originVatRate) {
                $repository->save($originVatRate);
                $originVatRate->releaseEvents();
            }

            $repository->save($vatRate);
            $vatRate->releaseEvents();

            $this->assertEquals($vatRate, $repository->find($vatRate->vatRateId));
        }
    }

    /**
     * @test
     * @dataProvider vatRates
     */
    public function it_can_delete_a_vat_rate(VatRate $vatRate, ?VatRate $originVatRate = null)
    {
        $vatRatesNotFound = 0;

        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            if ($originVatRate) {
                $repository->save($originVatRate);
                $originVatRate->releaseEvents();
            }

            $repository->save($vatRate);
            $repository->delete($vatRate->vatRateId);

            try {
                $repository->find($vatRate->vatRateId);
            } catch (CouldNotFindVatRate $e) {
                $vatRatesNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $vatRatesNotFound);
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(VatRateId::class, $repository->nextReference());
        }
    }

    public function test_it_can_get_vat_rates_for_country()
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            $originVatRate = $this->createVatRate(['vat_rate_id' => 'originVatRate-123', 'country_id' => 'NL', 'rate' => '20'], ['rate' => '20']);
            $repository->save($originVatRate);

            $vatRate = $this->createVatRate([], ['rate' => '20']);
            $repository->save($vatRate);

            $this->assertEquals([$vatRate], iterator_to_array($repository->getVatRatesForCountry(CountryId::fromString('BE'))));
            $this->assertEquals([$originVatRate], iterator_to_array($repository->getVatRatesForCountry(CountryId::fromString('NL'))));
            $this->assertEquals([], iterator_to_array($repository->getVatRatesForCountry(CountryId::fromString('FR'))));
        }
    }

    public function test_it_can_find_standard_vat_rate_for_country()
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            $vatRate = $this->createVatRateWithoutBaseRates(['country_id' => 'BE', 'rate' => '30', 'is_standard' => true]);
            $nonStandardVatRate = $this->createVatRateWithoutBaseRates(['country_id' => 'NL', 'rate' => '20', 'is_standard' => false]);

            $repository->save($vatRate);
            $repository->save($nonStandardVatRate);

            $this->assertEquals($vatRate, $repository->findStandardVatRateForCountry(CountryId::fromString('BE')));
            $this->assertNull($repository->findStandardVatRateForCountry(CountryId::fromString('NL')));
        }
    }

    public function test_it_cannot_find_offline_standard_vat_rate_for_country()
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            $vatRate = $this->createVatRateWithoutBaseRates(['country_id' => 'BE', 'rate' => '30', 'is_standard' => true, 'state' => VatRateState::offline->value]);

            $repository->save($vatRate);

            $this->assertNull($repository->findStandardVatRateForCountry(CountryId::fromString('BE')));
        }
    }

    public function test_it_can_get_primary_vat_rates()
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            $vatRate = $this->createVatRateWithoutBaseRates(['country_id' => 'BE', 'rate' => '30', 'is_standard' => true]);
            $secondVatRate = $this->createVatRateWithoutBaseRates(['country_id' => 'BE', 'rate' => '20', 'is_standard' => false]);

            $repository->save($vatRate);
            $repository->save($secondVatRate);

            $this->assertCount(2, iterator_to_array($repository->getPrimaryVatRates()));
            $this->assertContainsEquals($vatRate, iterator_to_array($repository->getPrimaryVatRates()));
            $this->assertContainsEquals($secondVatRate, iterator_to_array($repository->getPrimaryVatRates()));
        }
    }

    public function test_it_cannot_get_offline_primary_vat_rates()
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            $vatRate = $this->createVatRateWithoutBaseRates(['country_id' => 'BE', 'rate' => '30', 'is_standard' => true, 'state' => VatRateState::offline->value]);

            $repository->save($vatRate);

            $this->assertEquals([], iterator_to_array($repository->getPrimaryVatRates()));
        }
    }

    public function test_it_can_get_standard_primary_vat_rate()
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            $vatRate = $this->createVatRateWithoutBaseRates(['country_id' => 'BE', 'rate' => '30', 'is_standard' => true]);
            $secondVatRate = $this->createVatRateWithoutBaseRates(['country_id' => 'BE', 'rate' => '20', 'is_standard' => false]);

            $repository->save($vatRate);
            $repository->save($secondVatRate);

            $this->assertEquals($vatRate->getRate(), $repository->getStandardPrimaryVatRate());
        }
    }

    public function test_it_gets_fallback_rate_when_standard_primary_vat_rate_is_offline()
    {
        $fallbackRate = app(TraderConfig::class)->getFallBackStandardVatRate();

        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            $vatRate = $this->createVatRateWithoutBaseRates(['country_id' => 'BE', 'rate' => '30', 'is_standard' => true, 'state' => VatRateState::offline->value]);

            $repository->save($vatRate);

            $this->assertEquals($fallbackRate, $repository->getStandardPrimaryVatRate());
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryVatRateRepository(new TestTraderConfig());
        yield new MysqlVatRateRepository(new TestContainer());
    }

    public function vatRates(): \Generator
    {
        yield [$this->createVatRate([], ['rate' => '20']), $this->createVatRate(['vat_rate_id' => 'originVatRate-123', 'country_id' => 'NL', 'rate' => '20'])];
        yield [$this->createVatRate(['country_id' => 'BE', 'rate' => '30', 'is_standard' => true], ['rate' => '20']), $this->createVatRate(['vat_rate_id' => 'originVatRate-123', 'country_id' => 'NL', 'rate' => '20'])];
    }
}
