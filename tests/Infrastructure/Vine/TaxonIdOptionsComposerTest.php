<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
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

    public function test_it_can_retrieve_options()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $result = (new VineTaxonIdOptionsComposer($repository))->getOptions();

            $this->assertEquals([
                [
                    'label' => 'Taxon first',
                    'options' => [
                        'first' => 'Taxon first',
                        'second' => 'Taxon second',
                        'third' => 'Taxon third',
                        'fourth' => 'Taxon third > Taxon fourth',
                    ],
                ],
                [
                    'label' => 'Taxon fifth',
                    'options' => [
                        'fifth' => 'Taxon fifth',
                        'sixth' => 'Taxon sixth',
                    ],
                ],
            ], $result);
        }
    }

    public function test_it_can_retrieve_only_roots()
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
        yield new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
        yield new MysqlTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
    }
}
