<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;

final class TaxonRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider taxons
     */
    public function it_can_save_and_find_a_taxon(Taxon $taxon)
    {
        foreach ($this->taxonRepositories() as $taxonRepository) {
            $taxonRepository->save($taxon);
            $taxon->releaseEvents();

            $this->assertEquals($taxon, $taxonRepository->find($taxon->taxonId));
        }
    }

    /**
     * @test
     * @dataProvider taxons
     */
    public function it_can_save_and_find_a_taxon_by_key(Taxon $taxon)
    {
        foreach ($this->taxonRepositories() as $taxonRepository) {
            $taxonRepository->save($taxon);
            $taxon->releaseEvents();

            $this->assertEquals($taxon, $taxonRepository->findByKey($taxon->taxonKey));
        }
    }

    /**
     * @test
     * @dataProvider taxons
     */
    public function it_can_delete_a_taxon(Taxon $taxon)
    {
        $taxonsNotFound = 0;

        foreach ($this->taxonRepositories() as $taxonRepository) {
            $taxonRepository->save($taxon);
            $taxonRepository->delete($taxon->taxonId);

            try {
                $taxonRepository->find($taxon->taxonId);
            } catch (CouldNotFindTaxon $e) {
                $taxonsNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->taxonRepositories())), $taxonsNotFound);
    }

    /**
     * @test
     */
    public function it_can_generate_a_next_reference()
    {
        foreach ($this->taxonRepositories() as $taxonRepository) {
            $this->assertInstanceOf(TaxonId::class, $taxonRepository->nextReference());
        }
    }

    private function taxonRepositories(): \Generator
    {
        yield new InMemoryTaxonRepository();
        yield new MysqlTaxonRepository();
    }

    public function taxons(): \Generator
    {
        $taxon = Taxon::create(
            TaxonId::fromString('xxx'),
            TaxonKey::fromString('taxon-key'),
            TaxonId::fromString('parent'),
        );
        $taxon->addData(['foo' => 'bar']);

        yield [$taxon];

        $taxon = Taxon::create(
            TaxonId::fromString('xxx'),
            TaxonKey::fromString('taxon-key'),
            TaxonId::fromString('parent'),
        );

        $taxon->addData(['foo' => 'bar']);

        $taxon->changeState(TaxonState::queued_for_deletion);
        $taxon->changeOrder(555);

        yield [$taxon];

        // As Root
        yield [Taxon::create(
            TaxonId::fromString('xxx'),
            TaxonKey::fromString('taxon-key'),
        )];
    }
}
