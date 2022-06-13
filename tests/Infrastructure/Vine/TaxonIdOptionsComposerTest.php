<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonIdOptionsComposer;

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

        foreach ($this->repositories() as $repository) {
            $result = (new VineTaxonIdOptionsComposer($repository))->getOptions();

            $this->assertEquals([
                [
                    'group' => 'Taxon first',
                    'values' => [
                        'second' => 'Taxon second',
                        'third' => 'Taxon third',
                        'fourth' => 'Taxon third > Taxon fourth',
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
    public function it_can_retrieve_only_roots()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $this->assertEquals([
                'first' => 'Taxon first',
                'fifth' => 'Taxon fifth',
            ], (new VineTaxonIdOptionsComposer($repository))->getRoots());
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonTreeRepository(new TestContainer());
        yield new MysqlTaxonTreeRepository(new TestContainer());
    }
}
