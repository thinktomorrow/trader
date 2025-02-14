<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\CouldNotFindVatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVatRateRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVatRateRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class TaxRateProfileRepositoryTest extends TestCase
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
     * @dataProvider taxRateProfiles
     */
    public function it_can_save_and_find_a_profile(VatRate $taxRateProfile)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            $repository->save($taxRateProfile);
            $taxRateProfile->releaseEvents();

            $this->assertEquals($taxRateProfile, $repository->find($taxRateProfile->taxRateProfileId));
        }
    }

    /**
     * @test
     * @dataProvider taxRateProfiles
     */
    public function it_can_delete_a_profile(VatRate $taxRateProfile)
    {
        $profilesNotFound = 0;

        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);
            $repository->save($taxRateProfile);
            $repository->delete($taxRateProfile->taxRateProfileId);

            try {
                $repository->find($taxRateProfile->taxRateProfileId);
            } catch (CouldNotFindVatRate $e) {
                $profilesNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $profilesNotFound);
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(VatRateId::class, $repository->nextReference());
        }
    }

    public function test_it_can_get_profiles_for_country()
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);

            $profile = $this->createTaxRateProfile();
            $profile->addCountry(CountryId::fromString('BE'));

            $repository->save($profile);

            $this->assertEquals($profile, $repository->findVatRateForCountry('BE'));
            $this->assertNull($repository->findVatRateForCountry('NL'));
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryVatRateRepository();
        yield new MysqlVatRateRepository(new TestContainer());
    }

    public function taxRateProfiles(): \Generator
    {
        yield [$this->createTaxRateProfile()];

        $profile = $this->createTaxRateProfile();
        $profile->addCountry(CountryId::fromString('BE'));
        $profile->addBaseRate(BaseRate::create(BaseRateId::fromString('xxx'), $profile->taxRateProfileId, TaxRate::fromString('21'), TaxRate::fromString('10')));

        yield [$profile];
    }
}
