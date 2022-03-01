<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Vine\VineNestedTaxonIdsComposer;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;

final class NestedTaxonIdsComposerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTaxon;

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
            $result = (new VineNestedTaxonIdsComposer($repository))->getGroupedByRootByKeys(['taxon-first', 'taxon-sixth']);

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
            $result = (new VineNestedTaxonIdsComposer($repository))->getGroupedByRootByIds(['first', 'sixth']);

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
            $result = (new VineNestedTaxonIdsComposer($repository))->getGroupedByRootByKeys(['taxon-first', 'taxon-second']);

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
            $result = (new VineNestedTaxonIdsComposer($repository))->getGroupedByRootByKeys(['xxxx']);

            $this->assertEquals([], $result);
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonRepository();
        yield new MysqlTaxonRepository();
    }

    private function createDefaultTaxons()
    {
        $this->createTaxon(Taxon::create(TaxonId::fromString('first'), 'taxon-first'));
            $this->createTaxon(Taxon::create(TaxonId::fromString('second'), 'taxon-second', TaxonId::fromString('first')));
            $this->createTaxon(Taxon::create(TaxonId::fromString('third'), 'taxon-third', TaxonId::fromString('first')));
                $this->createTaxon(Taxon::create(TaxonId::fromString('fourth'), 'taxon-fourth', TaxonId::fromString('third')));
        $this->createTaxon(Taxon::create(TaxonId::fromString('fifth'), 'taxon-fifth'));
            $this->createTaxon(Taxon::create(TaxonId::fromString('sixth'), 'taxon-sixth', TaxonId::fromString('fifth')));
    }
}
