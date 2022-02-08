<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Taxa\Application\CreateRootTaxon;
use Thinktomorrow\Trader\Catalog\Taxa\Application\CreateTaxon;

class CreateTaxonTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_create_a_taxon()
    {
        $parentTaxon = app(CreateRootTaxon::class)->handle('root', [
            'label' => [
                'nl' => 'second label',
            ],
        ]);
        $taxon = app(CreateTaxon::class)->handle('xxx', $parentTaxon, [
            'label' => [
                'nl' => 'second label',
            ],
        ]);

        $this->assertEquals('1', $parentTaxon->getId());
        $this->assertEquals('root', $parentTaxon->getKey());
        $this->assertNull($parentTaxon->getParent());

        $this->assertEquals('2', $taxon->getId());
        $this->assertEquals('xxx', $taxon->getKey());
        $this->assertEquals($parentTaxon, $taxon->getParent());
    }

    /** @test */
    public function the_key_is_forced_to_be_unique()
    {
        $parentTaxon = app(CreateRootTaxon::class)->handle('root', [
            'label' => [
                'nl' => 'second label',
            ],
        ]);

        $taxon = app(CreateTaxon::class)->handle('root', $parentTaxon, [
            'label' => [
                'nl' => 'second label',
            ],
        ]);

        $this->assertNotEquals($parentTaxon->getKey(), $taxon->getKey());
    }
}
