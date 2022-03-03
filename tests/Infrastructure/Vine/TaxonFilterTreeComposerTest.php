<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonFilterTreeComposer;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;

final class TaxonFilterTreeComposerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTaxon;

    public function tearDown(): void
    {
        parent::tearDown();

        (new InMemoryTaxonRepository())->clear();
    }

    /** @test */
    public function it_can_retrieve_an_available_taxonFilter_tree()
    {
        $this->createDefaultTaxons();

        foreach($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer($repository);

            $taxonFilterTree = $composer->getAvailableFilters('taxon-first');

            // Top level
            $this->assertEquals(1, $taxonFilterTree->count());

            // Sublevel
            $this->assertTrue($taxonFilterTree[0]->hasChildren());
            $this->assertEquals(2, count($taxonFilterTree[0]->children()));

            // Sub sub level
            $this->assertTrue($taxonFilterTree[0]->children()[1]->hasChildren());
            $this->assertEquals(1, count($taxonFilterTree[0]->children()[1]->children()));
        }
    }

    /** @test */
    public function taxon_without_product_is_not_added_to_filter_tree()
    {
        $this->createTaxon(Taxon::create(TaxonId::fromString('first'), 'taxon-first'), ['aaa']);

        $this->createTaxon(Taxon::create(TaxonId::fromString('second'), 'taxon-second', TaxonId::fromString('first')), ['bbb']);
        $this->createTaxon(Taxon::create(TaxonId::fromString('third'), 'taxon-third', TaxonId::fromString('first')), []);

        foreach($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer($repository);

            $taxonFilterTree = $composer->getAvailableFilters('taxon-first');
            $this->assertEquals(1, $taxonFilterTree->count());
            $this->assertEquals('second', $taxonFilterTree[0]->children()[0]->id);
        }
    }

    /** @test */
    public function it_can_order_filters()
    {
        $this->createTaxon(Taxon::create(TaxonId::fromString('main'), 'taxon-main'), ['aaa']);

        $first = Taxon::create(TaxonId::fromString('first'), 'taxon-first', TaxonId::fromString('main'));
        $first->changeOrder(3);

        $second = Taxon::create(TaxonId::fromString('second'), 'taxon-second', TaxonId::fromString('main'));
        $second->changeOrder(1);

        $this->createTaxon($first, ['aaa']);
        $this->createTaxon($second, ['aaa']);

        foreach($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer($repository);

            $taxonFilterTree = $composer->getAvailableFilters('taxon-main');

            $this->assertCount(2, $taxonFilterTree[0]->children());
            $this->assertEquals('taxon-second', $taxonFilterTree[0]->children()[0]->getKey());
            $this->assertEquals('taxon-first', $taxonFilterTree[0]->children()[1]->getKey());
        }
    }

    /** @test */
    public function it_excludes_filters_that_are_offline()
    {
        $this->createTaxon(Taxon::create(TaxonId::fromString('first'), 'taxon-first'), ['aaa']);

        $taxon = Taxon::create(TaxonId::fromString('second'), 'taxon-second', TaxonId::fromString('first'));
        $taxon->changeState(TaxonState::offline);

        $this->createTaxon($taxon, ['bbb']);

        foreach($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer($repository);

            $taxonFilterTree = $composer->getAvailableFilters('taxon-first');
            $this->assertEquals(1, $taxonFilterTree->count());
            $this->assertFalse($taxonFilterTree[0]->hasChildren());
        }
    }

    /** @test */
    public function it_returns_empty_filter_if_taxon_key_does_not_exist()
    {
        foreach($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer($repository))->getAvailableFilters('xxx');
            $this->assertCount(0, $taxonFilterTree);
        }
    }

    /** @test */
    public function it_can_compose_active_filters_tree()
    {
        $this->createDefaultTaxons();

        foreach($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer($repository))->getActiveFilters('taxon-first', [
                'taxon-third'
            ]);

            $this->assertCount(1, $taxonFilterTree);
            $this->assertEquals('taxon-third', $taxonFilterTree[0]->getKey());
        }
    }

    /** @test */
    public function it_returns_empty_active_filter_if_taxon_key_does_not_exist()
    {
        foreach($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer($repository))->getActiveFilters('xxx', ['taxon-first']);
            $this->assertCount(0, $taxonFilterTree);
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonRepository();
        yield new MysqlTaxonRepository();
    }

    private function createDefaultTaxons()
    {
        $this->createTaxon(Taxon::create(TaxonId::fromString('first'), 'taxon-first'), ['aaa']);
        $this->createTaxon(Taxon::create(TaxonId::fromString('second'), 'taxon-second', TaxonId::fromString('first')), ['bbb']);
        $this->createTaxon(Taxon::create(TaxonId::fromString('third'), 'taxon-third', TaxonId::fromString('first')), ['ccc']);
        $this->createTaxon(Taxon::create(TaxonId::fromString('fourth'), 'taxon-fourth', TaxonId::fromString('third')), ['ddd']);
    }
}
