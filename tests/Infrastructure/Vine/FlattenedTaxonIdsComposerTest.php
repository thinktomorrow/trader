<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultTaxonNode;
use Thinktomorrow\Trader\Infrastructure\Vine\VineFlattenedTaxonIdsComposer;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;

final class FlattenedTaxonIdsComposerTest extends TestCase
{
    use RefreshDatabase;
    use TaxonHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        (new TestContainer())->add(TaxonNode::class, DefaultTaxonNode::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        (new InMemoryTaxonRepository())->clear();
    }

    /** @test */
    public function it_can_retrieve_all_ids_grouped_by_root()
    {
        $this->createDefaultTaxons();

        foreach($this->repositories() as $repository) {
            $result = (new VineFlattenedTaxonIdsComposer($repository))->getGroupedByRootByKeys(['taxon-first', 'taxon-sixth']);

            $this->assertEquals([
                'first' => ['first','second','third','fourth'],
                'fifth' => ['sixth'],
            ], $result);
        }
    }

    /** @test */
    public function it_can_find_taxon_ids_by_ids()
    {
        $this->createDefaultTaxons();

        foreach($this->repositories() as $repository) {
            $result = (new VineFlattenedTaxonIdsComposer($repository))->getGroupedByRootByIds(['first', 'sixth']);

            $this->assertEquals([
                'first' => ['first','second','third','fourth'],
                'fifth' => ['sixth'],
            ], $result);
        }
    }

    /** @test */
    public function it_returns_unique_values()
    {
        $this->createDefaultTaxons();

        foreach($this->repositories() as $repository) {
            $result = (new VineFlattenedTaxonIdsComposer($repository))->getGroupedByRootByKeys(['taxon-first', 'taxon-second']);

            $this->assertEquals([
                'first' => ['first','second','third','fourth'],
            ], $result);
        }
    }

    /** @test */
    public function it_returns_empty_list_for_non_found_taxons()
    {
        $this->createDefaultTaxons();

        foreach($this->repositories() as $repository) {
            $result = (new VineFlattenedTaxonIdsComposer($repository))->getGroupedByRootByKeys(['xxxx']);

            $this->assertEquals([], $result);
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonTreeRepository(new TestContainer());
        yield new MysqlTaxonTreeRepository(new TestContainer());
    }

}
