<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Products\Application\CreateProductGroup;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupState;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductState;
use Thinktomorrow\Trader\Catalog\Taxa\Application\CreateRootTaxon;

class GridTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_lists_online_productgroups()
    {
        $this->createCatalog();

        $response = $this->get('c/blue');

        $response->assertSuccessful();
        $this->assertGridCounts($response->viewData('productGroups'), 1, 2);
    }

    /** @test */
    public function it_does_not_lists_offline_productgroups()
    {
        $taxon = app(CreateRootTaxon::class)->handle('blue', ['label' => ['nl' => 'root taxon label']]);
        $productGroup = app(CreateProductGroup::class)->handle([$taxon->getKey()], []);
        $product = $this->createProduct([], $productGroup->getId());

        $this->changeProductGroupState($productGroup->getId(), ProductGroupState::DRAFT);

        $response = $this->get('c/blue');
        $response->assertSuccessful();

        $this->assertGridCounts($response->viewData('productGroups'), 0, 0);
    }

    /** @test */
    public function it_only_lists_productgroups_with_available_products()
    {
        $taxon = app(CreateRootTaxon::class)->handle('blue', ['label' => ['nl' => 'root taxon label']]);
        $productGroup = app(CreateProductGroup::class)->handle([$taxon->getKey()], []);
        $product = $this->createProduct([], $productGroup->getId());

        $this->changeProductGroupState($product->getId(), ProductGroupState::ONLINE);
        $this->changeProductState($product->getId(), ProductState::UNAVAILABLE);

        $response = $this->get('c/blue');
        $response->assertSuccessful();

        $this->assertGridCounts($response->viewData('productGroups'), 0, 0);
    }
}
