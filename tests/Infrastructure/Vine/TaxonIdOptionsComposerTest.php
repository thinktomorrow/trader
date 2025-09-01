<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->createDefaultTaxonomies();
        $this->createDefaultTaxons();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        (new InMemoryTaxonRepository())->clear();
    }

    public function test_it_can_retrieve_options()
    {
        foreach ($this->repositories() as $i => $repository) {
            $taxonomyRepo = iterator_to_array($this->entityTaxonomyRepositories())[$i];
            $result = (new VineTaxonIdOptionsComposer($taxonomyRepo, $repository))->getTaxaAsOptions('bbb');

            $this->assertEquals([
                'first' => 'Taxon first',
                'second' => 'Taxon first: Taxon second',
                'third' => 'Taxon first: Taxon third',
                'fourth' => 'Taxon first: Taxon third > Taxon fourth',
                'fifth' => 'Taxon fifth',
                'sixth' => 'Taxon fifth: Taxon sixth',
            ], $result);

            $result = (new VineTaxonIdOptionsComposer($taxonomyRepo, $repository))->getTaxaAsOptions('ccc');

            $this->assertEquals([
                'seventh' => 'Taxon seventh',
                'eight' => 'Taxon seventh: Taxon eight',
            ], $result);
        }
    }

    public function test_it_can_retrieve_options_for_multiselect()
    {
        foreach ($this->repositories() as $i => $repository) {

            $taxonomyRepo = iterator_to_array($this->entityTaxonomyRepositories())[$i];

            $result = (new VineTaxonIdOptionsComposer($taxonomyRepo, $repository))
                ->setLocale(Locale::fromString('nl'))
                ->getTaxaAsOptionsForMultiselect('bbb');

            $this->assertEquals([
                ['label' => 'Taxon first', 'value' => 'first'],
                ['label' => 'Taxon first: Taxon second', 'value' => 'second'],
                ['label' => 'Taxon first: Taxon third', 'value' => 'third'],
                ['label' => 'Taxon first: Taxon third > Taxon fourth', 'value' => 'fourth'],
                ['label' => 'Taxon fifth', 'value' => 'fifth'],
                ['label' => 'Taxon fifth: Taxon sixth', 'value' => 'sixth'],
            ], $result);
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
        yield new MysqlTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
    }
}
