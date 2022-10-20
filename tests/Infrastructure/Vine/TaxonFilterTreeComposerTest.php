<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonFilterTreeComposer;

final class TaxonFilterTreeComposerTest extends TestCase
{
    use RefreshDatabase;
    use TaxonHelpers;

    public function tearDown(): void
    {
        parent::tearDown();

        (new InMemoryTaxonRepository())->clear();
    }

    /** @test */
    public function it_can_retrieve_an_available_taxon_filter_tree()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer($repository);

            $taxonFilterTree = $composer->getAvailableFilters('taxon-first');

            // Top level
            $this->assertEquals(1, $taxonFilterTree->count());

            // Sublevel
            $this->assertTrue($taxonFilterTree[0]->hasChildNodes());
            $this->assertEquals(2, count($taxonFilterTree[0]->getChildNodes()));

            // Sub sub level
            $this->assertTrue($taxonFilterTree[0]->getChildNodes()[1]->hasChildNodes());
            $this->assertEquals(1, count($taxonFilterTree[0]->getChildNodes()[1]->getChildNodes()));
        }
    }

    /** @test */
    public function it_can_retrieve_all_product_ids()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer($repository);

            $productIds = $composer->getProductIds('first');

            $this->assertEquals([
                'aaa','bbb','ccc','ddd',
            ], $productIds);
        }
    }

    /** @test */
    public function taxon_without_product_is_not_added_to_filter_tree()
    {
        $this->createTaxon(Taxon::create(TaxonId::fromString('first'), TaxonKey::fromString('taxon-first')), ['aaa']);

        $this->createTaxon(Taxon::create(TaxonId::fromString('second'), TaxonKey::fromString('taxon-second'), TaxonId::fromString('first')), ['bbb']);
        $this->createTaxon(Taxon::create(TaxonId::fromString('third'), TaxonKey::fromString('taxon-third'), TaxonId::fromString('first')), []);

        foreach ($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer($repository);

            $taxonFilterTree = $composer->getAvailableFilters('taxon-first');
            $this->assertEquals(1, $taxonFilterTree->count());
            $this->assertEquals('second', $taxonFilterTree[0]->getChildNodes()[0]->id);
        }
    }

    /** @test */
    public function it_can_order_filters()
    {
        $this->createTaxon(Taxon::create(TaxonId::fromString('main'), TaxonKey::fromString('taxon-main')), ['aaa']);

        $first = Taxon::create(TaxonId::fromString('first'), TaxonKey::fromString('taxon-first'), TaxonId::fromString('main'));
        $first->changeOrder(3);

        $second = Taxon::create(TaxonId::fromString('second'), TaxonKey::fromString('taxon-second'), TaxonId::fromString('main'));
        $second->changeOrder(1);

        $this->createTaxon($first, ['aaa']);
        $this->createTaxon($second, ['aaa']);

        foreach ($this->repositories() as $i => $repository) {
            $composer = new VineTaxonFilterTreeComposer($repository);

            $taxonFilterTree = $composer->getAvailableFilters('taxon-main');

            $this->assertCount(2, $taxonFilterTree[0]->getChildNodes());
            $this->assertEquals('taxon-second', $taxonFilterTree[0]->getChildNodes()[0]->getKey());
            $this->assertEquals('taxon-first', $taxonFilterTree[0]->getChildNodes()[1]->getKey());
        }
    }

    /** @test */
    public function it_excludes_filters_that_are_offline()
    {
        $this->createTaxon(Taxon::create(TaxonId::fromString('first'), TaxonKey::fromString('taxon-first')), ['aaa']);

        $taxon = Taxon::create(TaxonId::fromString('second'), TaxonKey::fromString('taxon-second'), TaxonId::fromString('first'));
        $taxon->changeState(TaxonState::offline);

        $this->createTaxon($taxon, ['bbb']);

        foreach ($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer($repository);

            $taxonFilterTree = $composer->getAvailableFilters('taxon-first');
            $this->assertEquals(1, $taxonFilterTree->count());
            $this->assertFalse($taxonFilterTree[0]->hasChildNodes());
        }
    }

    /** @test */
    public function it_returns_empty_filter_if_taxon_key_does_not_exist()
    {
        foreach ($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer($repository))->getAvailableFilters('xxx');
            $this->assertCount(0, $taxonFilterTree);
        }
    }

    /** @test */
    public function it_can_compose_active_filters_tree()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer($repository))->getActiveFilters('taxon-first', [
                'taxon-third',
            ]);

            $this->assertCount(1, $taxonFilterTree);
            $this->assertEquals('taxon-third', $taxonFilterTree[0]->getKey());
        }
    }

    /** @test */
    public function it_returns_empty_active_filter_if_taxon_key_does_not_exist()
    {
        foreach ($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer($repository))->getActiveFilters('xxx', ['taxon-first']);
            $this->assertCount(0, $taxonFilterTree);
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonTreeRepository(new TestContainer());
        yield new MysqlTaxonTreeRepository(new TestContainer());
    }
}
