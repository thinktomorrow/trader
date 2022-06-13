<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class TaxonTreeRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use TaxonHelpers;

    /** @test */
    public function it_can_get_the_entire_tree()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $this->assertContainsOnlyInstancesOf(TaxonNode::class, $repository->getTree());
            $this->assertEquals(2, $repository->getTree()->count());
            $this->assertEquals(6, $repository->getTree()->total());
        }
    }

    /** @test */
    public function it_can_find_taxon_by_key()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $this->assertNotNull($repository->findTaxonByKey('taxon-fifth'));
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonTreeRepository(new TestContainer());
        yield new MysqlTaxonTreeRepository(new TestContainer());
    }
}
