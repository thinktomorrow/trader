<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Exceptions\CouldNotFindTaxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;

final class TaxonomyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('taxonomies')]
    public function test_it_can_save_and_find_a_taxonomy(Taxonomy $taxonomy)
    {
        foreach ($this->taxonomyRepositories() as $taxonomyRepository) {
            $taxonomyRepository->save($taxonomy);
            $taxonomy->releaseEvents();

            $this->assertEquals($taxonomy, $taxonomyRepository->find($taxonomy->taxonomyId));
        }
    }

    public function test_it_can_find_many_taxonomies_by_taxon_id()
    {
        $taxonomy = Taxonomy::create(
            TaxonomyId::fromString('xxx'),
            TaxonomyType::property,
        );

        $taxon = Taxon::create(
            TaxonId::fromString('yyy'),
            $taxonomy->taxonomyId,
        );

        foreach ($this->taxonomyRepositories() as $i => $taxonomyRepository) {
            $taxonomyRepository->save($taxonomy);
            $taxonomy->releaseEvents();

            $this->taxonRepositories()[$i]->save($taxon);
            $taxon->releaseEvents();

            $this->assertEquals([$taxonomy], $taxonomyRepository->findManyByTaxa([$taxon->taxonId->get()]));
        }
    }

    #[DataProvider('taxonomies')]
    public function test_it_can_delete_a_taxonomy(Taxonomy $taxonomy)
    {
        $taxonomiesNotFound = 0;

        foreach ($this->taxonomyRepositories() as $taxonomyRepository) {
            $taxonomyRepository->save($taxonomy);
            $taxonomyRepository->delete($taxonomy->taxonomyId);

            try {
                $taxonomyRepository->find($taxonomy->taxonomyId);
            } catch (CouldNotFindTaxonomy $e) {
                $taxonomiesNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->taxonomyRepositories())), $taxonomiesNotFound);
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach ($this->taxonomyRepositories() as $taxonomyRepository) {
            $this->assertInstanceOf(TaxonomyId::class, $taxonomyRepository->nextReference());
        }
    }

    private static function taxonomyRepositories(): \Generator
    {
        yield new InMemoryTaxonomyRepository();
        yield new MysqlTaxonomyRepository();
    }

    private function taxonRepositories(): array
    {
        return [
            new InMemoryTaxonRepository(),
            new MysqlTaxonRepository(),
        ];
    }

    public static function taxonomies(): \Generator
    {
        $taxonomy = Taxonomy::create(
            TaxonomyId::fromString('xxx'),
            TaxonomyType::property,
        );

        $taxonomy->addData(['foo' => 'bar']);

        yield [$taxonomy];

        $taxonomy = Taxonomy::create(
            TaxonomyId::fromString('xxx'),
            TaxonomyType::property,
        );

        $taxonomy->addData(['foo' => 'bar']);

        $taxonomy->changeState(TaxonomyState::queued_for_deletion);
        $taxonomy->changeOrder(555);

        yield [$taxonomy];
    }
}
