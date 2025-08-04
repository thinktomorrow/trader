<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Exceptions\CouldNotFindTaxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;

final class TaxonomyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('taxonomies')]
    public function test_it_can_save_and_find_a_taxonomy(Taxonomy $taxonomy)
    {
        foreach ($this->taxonRepositories() as $taxonomyRepository) {
            $taxonomyRepository->save($taxonomy);
            $taxonomy->releaseEvents();

            $this->assertEquals($taxonomy, $taxonomyRepository->find($taxonomy->taxonomyId));
        }
    }

    #[DataProvider('taxonomies')]
    public function test_it_can_delete_a_taxonomy(Taxonomy $taxonomy)
    {
        $taxonomiesNotFound = 0;

        foreach ($this->taxonRepositories() as $taxonomyRepository) {
            $taxonomyRepository->save($taxonomy);
            $taxonomyRepository->delete($taxonomy->taxonomyId);

            try {
                $taxonomyRepository->find($taxonomy->taxonomyId);
            } catch (CouldNotFindTaxonomy $e) {
                $taxonomiesNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->taxonRepositories())), $taxonomiesNotFound);
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach ($this->taxonRepositories() as $taxonomyRepository) {
            $this->assertInstanceOf(TaxonomyId::class, $taxonomyRepository->nextReference());
        }
    }

    private static function taxonRepositories(): \Generator
    {
        yield new InMemoryTaxonomyRepository();
        yield new MysqlTaxonomyRepository();
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
