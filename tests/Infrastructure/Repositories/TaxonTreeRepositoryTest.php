<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MemoizedMysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

final class TaxonTreeRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use TaxonHelpers;

    protected function tearDown(): void
    {
        MemoizedMysqlTaxonTreeRepository::clear();

        parent::tearDown();
    }

    public function test_it_can_get_the_entire_tree()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $this->assertContainsOnlyInstancesOf(TaxonNode::class, $repository->getTree());
            $this->assertEquals(3, $repository->getTree()->count());
            $this->assertEquals(8, $repository->getTree()->total());
        }
    }

    public function test_it_can_get_the_tree_per_taxonomy()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $this->assertContainsOnlyInstancesOf(TaxonNode::class, $repository->getTreeByTaxonomy('bbb'));

            $this->assertEquals(2, $repository->getTreeByTaxonomy('bbb')->count());
            $this->assertEquals(6, $repository->getTreeByTaxonomy('bbb')->total());
            $this->assertEquals(1, $repository->getTreeByTaxonomy('ccc')->count());
            $this->assertEquals(2, $repository->getTreeByTaxonomy('ccc')->total());
        }
    }

    public function test_it_can_find_taxon_by_key()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $this->assertNotNull($repository->findTaxonByKey('taxon-fifth'));
        }
    }

    private static function repositories(): \Generator
    {
        yield new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
        yield $mysqlTaxonTreeRepo = new MysqlTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
        yield new MemoizedMysqlTaxonTreeRepository($mysqlTaxonTreeRepo, new TestTraderConfig());
    }
}
