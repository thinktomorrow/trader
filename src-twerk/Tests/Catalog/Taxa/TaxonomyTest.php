<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog\Taxa;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Products\Application\CreateProductGroup;
use Thinktomorrow\Trader\Catalog\Taxa\Application\CreateRootTaxon;
use Thinktomorrow\Trader\Catalog\Taxa\Application\CreateTaxon;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\Taxon;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\TaxonRepository;

class TaxonomyTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_retrieve_children()
    {
        $parentTaxon = app(CreateRootTaxon::class)->handle('root', [
            'label' => [
                'nl' => 'root label',
            ],
        ]);
        $taxon = app(CreateTaxon::class)->handle('first', $parentTaxon, [
            'label' => [
                'nl' => 'first label',
            ],
        ]);
        $taxon2 = app(CreateTaxon::class)->handle('second', $parentTaxon, [
            'label' => [
                'nl' => 'second label',
            ],
        ]);

        /** @var Taxon $parent */
        $parent = app()->make(TaxonRepository::class)->findByKey($parentTaxon->getKey());

        $this->assertEquals('root', $parent->getKey());
        $this->assertTrue($parent->isRootNode());
    }

    /** @test */
    public function a_productgroup_can_have_taxonomy()
    {
        $taxon = app(CreateRootTaxon::class)->handle('taxon-1', [
            'label' => [
                'nl' => 'taxon label',
            ],
        ]);

        $productGroup = app(CreateProductGroup::class)->handle([
            'taxon-1',
        ], [
            'label' => [
                'nl' => 'productgroup label',
            ],
        ]);

        $this->assertCount(1, $productGroup->getTaxonomy());
        $this->assertEquals($taxon, $productGroup->getTaxonomy()->first());
    }

    /** @test */
    public function it_can_generate_url()
    {
        $parentTaxon = app(CreateRootTaxon::class)->handle('root', [
            'label' => [
                'nl' => 'root label',
            ],
        ]);
        app(CreateTaxon::class)->handle('first', $parentTaxon, [
            'label' => [
                'nl' => 'first label',
            ],
        ]);

        /** @var Taxon $parent */
        $taxon = app()->make(TaxonRepository::class)->findByKey('first');

        $this->assertEquals('https://twerk.be/c/root/first', $taxon->getUrl());
    }
}
