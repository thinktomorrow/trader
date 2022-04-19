<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;

final class TaxonTreeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_all_records()
    {
        $this->createTaxons();

        foreach($this->repositories() as $repository) {
            $this->assertContainsOnlyInstancesOf(TaxonNode::class, $repository->getAllTaxonNodes());
            $this->assertCount(5, $repository->getAllTaxonNodes());
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonRepository();
        yield new MysqlTaxonRepository();
    }

    private function createTaxons()
    {
        $taxons = [
            Taxon::create(TaxonId::fromString('first'), 'taxon-first'),
            Taxon::create(TaxonId::fromString('second'), 'taxon-second', TaxonId::fromString('first')),
            Taxon::create(TaxonId::fromString('third'), 'taxon-third', TaxonId::fromString('first')),
            Taxon::create(TaxonId::fromString('fourth'), 'taxon-fourth', TaxonId::fromString('third')),
            Taxon::create(TaxonId::fromString('fifth'), 'taxon-fifth'),
        ];

        foreach($this->repositories() as $repository) {
            foreach($taxons as $taxon) {
                $repository->save($taxon);
            }
        }
    }
}
