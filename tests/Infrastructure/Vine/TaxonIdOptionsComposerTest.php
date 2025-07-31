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
        $this->createDefaultTaxonomies();
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $result = (new VineTaxonIdOptionsComposer($repository))->getTaxaAsOptions('bbb');

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

            $result = (new VineTaxonIdOptionsComposer($repository))->getTaxaAsOptions('ccc');

            $this->assertEquals([
                [
                    'label' => 'Taxon seventh',
                    'options' => [
                        'seventh' => 'Taxon seventh',
                        'eight' => 'Taxon eight',
                    ],
                ],
            ], $result);
        }
    }

    public function test_it_can_retrieve_options_for_multiselect()
    {
        $this->createDefaultTaxons();

        foreach ($this->repositories() as $repository) {
            $this->assertEquals([
                [
                    'label' => 'Taxon first',
                    'options' => [
                        ['label' => 'Taxon first', 'value' => 'first'],
                        ['label' => 'Taxon second', 'value' => 'second'],
                        ['label' => 'Taxon third', 'value' => 'third'],
                        ['label' => 'Taxon third > Taxon fourth', 'value' => 'fourth'],
                    ],
                ],
                [
                    'label' => 'Taxon fifth',
                    'options' => [
                        ['label' => 'Taxon fifth', 'value' => 'fifth'],
                        ['label' => 'Taxon sixth', 'value' => 'sixth'],
                    ],
                ],
            ], (new VineTaxonIdOptionsComposer($repository))->getTaxaAsOptionsForMultiselect('bbb'));
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
        yield new MysqlTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
    }
}
