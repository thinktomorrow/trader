<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
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
        // This is a product associated with a nested child node
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('fourth'), ['ddd']);

        // Set this product online for mysql as well
        $this->createProductInMysql('ddd');

        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository);

            $taxonFilterTree = $composer->getAvailableFilters(Locale::fromString('nl'), 'taxon-first');

            // Top level
            $this->assertEquals(1, $taxonFilterTree->count());

            // Sublevel
            $this->assertTrue($taxonFilterTree[0]->hasChildNodes());
            $this->assertEquals(1, count($taxonFilterTree[0]->getChildNodes()));

            // Sub sub level
            $this->assertTrue($taxonFilterTree[0]->getChildNodes()[0]->hasChildNodes());
            $this->assertEquals(1, count($taxonFilterTree[0]->getChildNodes()[0]->getChildNodes()));
        }
    }

    /** @test */
    public function it_can_retrieve_all_product_ids()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository);

            $productIds = $composer->getProductIds('first');

            $this->assertEquals([
                'aaa','bbb','ccc','ddd',
            ], $productIds);
        }
    }

    /** @test */
    public function it_can_retrieve_all_online_product_ids()
    {
        $onlineProductIds = ['ccc','ddd'];

        // Mysql
        foreach ($onlineProductIds as $productId) {
            $this->createProductInMysql($productId);
        }

        $this->createTaxon(
            Taxon::create(TaxonId::fromString('first')),
            ['aaa', 'bbb', 'ccc', 'ddd']
        );

        foreach ($this->repositories() as $i => $repository) {
            if ($i == 0) {
                (new InMemoryTaxonRepository)->setOnlineProductIds(TaxonId::fromString('first'), $onlineProductIds);
            }

            $composer = new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository);

            $this->assertEquals([
                'ccc','ddd',
            ], $composer->getOnlineProductIds('first'));

            $this->assertEquals([
                'aaa','bbb','ccc','ddd',
            ], $composer->getProductIds('first'));
        }
    }

    /** @test */
    public function taxon_without_product_is_not_added_to_filter_tree()
    {
        // Create online products and set online products relation for in memory
        $this->createProductInMysql('aaa');
        $this->createProductInMysql('bbb');
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('first'), ['aaa']);
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('second'), ['bbb']);

        $taxon = Taxon::create(TaxonId::fromString('first'));
        $taxon->updateTaxonKeys([TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-first'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon,  ['aaa']);

        $taxon = Taxon::create(TaxonId::fromString('second'), TaxonId::fromString('first'));
        $taxon->updateTaxonKeys([TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-second'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon,  ['bbb']);

        // No product
        $taxon = Taxon::create(TaxonId::fromString('third'), TaxonId::fromString('first'));
        $taxon->updateTaxonKeys([TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-third'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon,  []);

        // Offline product
        $taxon = Taxon::create(TaxonId::fromString('fourth'), TaxonId::fromString('first'));
        $taxon->updateTaxonKeys([TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-fourth'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon,  ['ccc']);

        foreach ($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository);

            $taxonFilterTree = $composer->getAvailableFilters(Locale::fromString('nl'), 'taxon-first');
            $this->assertEquals(1, $taxonFilterTree->count());
            $this->assertEquals('second', $taxonFilterTree[0]->getChildNodes()[0]->id);
        }
    }

    /** @test */
    public function it_can_order_filters()
    {
        $this->createProductInMysql('aaa');
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('main'), ['aaa']);
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('first'), ['aaa']);
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('second'), ['aaa']);

        $taxon = Taxon::create(TaxonId::fromString('main'));
        $taxon->updateTaxonKeys([TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-main'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon,  ['aaa']);

        $first = Taxon::create(TaxonId::fromString('first'), TaxonId::fromString('main'));
        $first->changeOrder(3);
        $first->updateTaxonKeys([TaxonKey::create($first->taxonId, TaxonKeyId::fromString('taxon-first'), Locale::fromString('nl'))]);
        $this->createTaxon($first,  ['aaa']);

        $second = Taxon::create(TaxonId::fromString('second'), TaxonId::fromString('main'));
        $second->changeOrder(1);
        $second->updateTaxonKeys([TaxonKey::create($second->taxonId, TaxonKeyId::fromString('taxon-second'), Locale::fromString('nl'))]);
        $this->createTaxon($second,  ['aaa']);

        foreach ($this->repositories() as $i => $repository) {
            $composer = new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository);

            $taxonFilterTree = $composer->getAvailableFilters(Locale::fromString('nl'), 'taxon-main');

            $this->assertCount(2, $taxonFilterTree[0]->getChildNodes());
            $this->assertEquals('taxon-second', $taxonFilterTree[0]->getChildNodes()[0]->getKey());
            $this->assertEquals('taxon-first', $taxonFilterTree[0]->getChildNodes()[1]->getKey());
        }
    }

    /** @test */
    public function it_excludes_filters_that_are_offline()
    {
        $this->createProductInMysql('aaa');
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('first'), ['aaa']);
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('second'), ['aaa']);

        $taxon = Taxon::create(TaxonId::fromString('first'));
        $taxon->updateTaxonKeys([TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-first'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon,  ['aaa']);

        $taxon = Taxon::create(TaxonId::fromString('second'), TaxonId::fromString('first'));
        $taxon->changeState(TaxonState::offline);
        $taxon->updateTaxonKeys([TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-second'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon,  ['aaa']);

        foreach ($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository);

            $taxonFilterTree = $composer->getAvailableFilters(Locale::fromString('nl'), 'taxon-first');
            $this->assertEquals(1, $taxonFilterTree->count());
            $this->assertFalse($taxonFilterTree[0]->hasChildNodes());
        }
    }

    /** @test */
    public function it_excludes_filters_that_are_categories_but_are_not_in_same_main_category_group()
    {
        $this->createProductInMysql('aaa');
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('first'), ['aaa']);
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('second'), ['aaa']);
        (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('third'), ['aaa']);

        $taxon = Taxon::create(TaxonId::fromString('first'));
        $taxon->updateTaxonKeys([TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-first'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon,  ['aaa']);

        $taxon2 = Taxon::create(TaxonId::fromString('second'), TaxonId::fromString('first'));
        $taxon2->updateTaxonKeys([TaxonKey::create($taxon2->taxonId, TaxonKeyId::fromString('taxon-second'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon2,  ['aaa']);

        $taxon3 = Taxon::create(TaxonId::fromString('third'));
        $taxon3->updateTaxonKeys([TaxonKey::create($taxon3->taxonId, TaxonKeyId::fromString('taxon-third'), Locale::fromString('nl'))]);
        $this->createTaxon($taxon3,  ['aaa']);

        foreach ($this->repositories() as $repository) {
            $composer = new VineTaxonFilterTreeComposer(new TestTraderConfig(['category_root_id' => $taxon->taxonId->get()]), $repository);

            $taxonFilterTree = $composer->getAvailableFilters(Locale::fromString('nl'), 'taxon-first');
            $this->assertEquals(2, $taxonFilterTree->count());

            $this->assertEquals($taxon->taxonId, $taxonFilterTree->first()->getNodeId());
            $this->assertEquals($taxon3->taxonId, $taxonFilterTree[1]->getNodeId());
        }
    }

    /** @test */
    public function it_returns_empty_filter_if_taxon_key_does_not_exist()
    {
        foreach ($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository))->getAvailableFilters(Locale::fromString('nl'), 'xxx');
            $this->assertCount(0, $taxonFilterTree);
        }
    }

    /** @test */
    public function it_can_compose_active_filters_tree()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository))->getActiveFilters(Locale::fromString('nl'), 'taxon-first', [
                'taxon-third',
            ]);

            $this->assertCount(1, $taxonFilterTree);
            $this->assertEquals('taxon-third', $taxonFilterTree[0]->getKey());
        }
    }

    /** @test */
    public function it_uses_main_taxon_as_filter_tree_when_no_actively_filters_are_used()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository))->getActiveFilters(Locale::fromString('nl'), 'taxon-first', [

            ]);

            $this->assertCount(1, $taxonFilterTree);
            $this->assertEquals('taxon-first', $taxonFilterTree[0]->getKey());
        }
    }

    /** @test */
    public function it_returns_empty_active_filter_if_taxon_key_does_not_exist()
    {
        foreach ($this->repositories() as $repository) {
            $taxonFilterTree = (new VineTaxonFilterTreeComposer(new TestTraderConfig(), $repository))->getActiveFilters(Locale::fromString('nl'), 'xxx', ['taxon-first']);
            $this->assertCount(0, $taxonFilterTree);
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
        yield new MysqlTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
    }
}
