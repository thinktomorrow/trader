<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;

final class TaxonRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider taxons
     */
    public function it_can_save_an_taxon(Taxon $taxon)
    {
        foreach($this->taxonRepositories() as $taxonRepository) {
            $taxonRepository->save($taxon);
            $taxon->releaseEvents();

            $this->assertEquals($taxon, $taxonRepository->find($taxon->taxonId));
        }
    }

    /**
     * @test
     * @dataProvider taxons
     */
    public function it_can_find_an_taxon(Taxon $taxon)
    {
        foreach($this->taxonRepositories() as $taxonRepository) {
            $taxonRepository->save($taxon);
            $taxon->releaseEvents();

            $this->assertEquals($taxon, $taxonRepository->find($taxon->taxonId));
        }
    }

    /**
     * @test
     * @dataProvider taxons
     */
    public function it_can_delete_an_taxon(Taxon $taxon)
    {
        $taxonsNotFound = 0;

        foreach($this->taxonRepositories() as $taxonRepository) {
            $taxonRepository->save($taxon);
            $taxonRepository->delete($taxon->taxonId);

            try{
                $taxonRepository->find($taxon->taxonId);
            } catch (CouldNotFindTaxon $e) {
                $taxonsNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->taxonRepositories())), $taxonsNotFound);
    }

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach($this->taxonRepositories() as $taxonRepository) {
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
        yield [Taxon::create(
            TaxonId::fromString('xxx'),
            'taxon-key',
            TaxonId::fromString('parent'),
        )];

        $taxon = Taxon::create(
            TaxonId::fromString('xxx'),
            'taxon-key',
            TaxonId::fromString('parent'),
        );

        $taxon->changeState(TaxonState::queued_for_deletion);
        $taxon->changeOrder(555);

        yield [$taxon];

        // As Root
        yield [Taxon::create(
            TaxonId::fromString('xxx'),
            'taxon-key',
        )];
    }
}
