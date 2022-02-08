<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog\Taxa;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Taxa\Application\CreateRootTaxon;
use Thinktomorrow\Trader\Catalog\Taxa\Application\CreateTaxon;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\TaxonRepository;

class DeleteTaxonTest extends TestCase
{
    use DatabaseMigrations;

    /** @var mixed|TaxonRepository */
    private $taxonRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->taxonRepository = app()->make(TaxonRepository::class);
    }

    /** @test */
    public function it_can_delete_a_taxon()
    {
        $taxon = app(CreateRootTaxon::class)->handle('first', [
            'label' => [
                'nl' => 'root label',
            ],
        ]);

        $this->taxonRepository->delete($taxon);

        $this->assertEquals(0, $this->taxonRepository->getRootNodes()->total());
        $this->assertNull($this->taxonRepository->findByKey('first'));
    }

    /** @test */
    public function when_a_taxon_with_children_is_deleted_the_children_are_moved_to_parent()
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

        $taxon2 = app(CreateTaxon::class)->handle('second', $taxon, [
            'label' => [
                'nl' => 'second label',
            ],
        ]);

        $this->taxonRepository->delete(
            $this->taxonRepository->findByKey('first')
        );

        $this->assertEquals(2, $this->taxonRepository->getRootNodes()->total());

        $taxon = $this->taxonRepository->findByKey('second');
        $this->assertEquals($parentTaxon->getNodeId(), $taxon->getParentNode()->getNodeId());
    }

    /** @test */
    public function when_a_root_taxon_with_children_is_deleted_the_children_are_moved_to_root()
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

        $this->taxonRepository->delete(
            $this->taxonRepository->findByKey('root')
        );

        $this->assertEquals(1, $this->taxonRepository->getRootNodes()->total());

        $taxon = $this->taxonRepository->findByKey('first');
        $this->assertTrue($taxon->isRootNode());
    }
}
