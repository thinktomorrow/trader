<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonIdOptionsComposer;
use Thinktomorrow\Trader\Infrastructure\Vine\VineFlattenedTaxonIdsComposer;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;

final class TaxonIdOptionsComposerTest extends TestCase
{
    use RefreshDatabase;
    use TaxonHelpers;

    public function tearDown(): void
    {
        parent::tearDown();

        (new InMemoryTaxonRepository())->clear();
    }

    /** @test */
    public function it_can_retrieve_options()
    {
        $this->createDefaultTaxons();

        foreach($this->repositories() as $repository) {
            $result = (new VineTaxonIdOptionsComposer($repository))->getOptions();

            $this->assertEquals([
                [
                    'group' => 'Taxon first',
                    'values' => [
                        'second' => 'Taxon second',
                        'third' => 'Taxon third',
                        'fourth' => 'Taxon third > Taxon fourth'
                    ],
                ],
                [
                    'group' => 'Taxon fifth',
                    'values' => [
                        'sixth' => 'Taxon sixth',
                    ],
                ],
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
        yield new InMemoryTaxonTreeRepository();
        yield new MysqlTaxonTreeRepository();
    }
}
