<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\ShopAdmin;

use App\ShopAdmin\Catalog\Products\ProductGroupModel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Chief\Managers\Register\Registry;
use Thinktomorrow\Trader\Tests\Catalog\Options\OptionTestHelpers;

class ProductGroupAdminTest extends TestCase
{
    use DatabaseMigrations;
    use OptionTestHelpers;
    use ChiefTestHelpers;

    private \Thinktomorrow\Chief\Managers\Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // Chief setup
        $this->setUpDefaultAuthorization();

        $this->manager = app(Registry::class)->manager(ProductGroupModel::managedModelKey());
    }

    /** @test */
    public function itCanViewProductgroupIndexPage()
    {
        $this->createProductGroup();

        $this->asAdmin()->get($this->manager->route('index'))
            ->assertSuccessful();
    }

    /** @test */
    public function itCanViewProductgroupCreatePage()
    {
        $this->asAdmin()->get($this->manager->route('create'))
            ->assertSuccessful();
    }

    /** @test */
    public function itCanViewProductgroupEditPage()
    {
        $productGroup = $this->createProductGroup();
        $product = $this->createProduct([], $productGroup->getId());

        $this->asAdmin()->get($this->manager->route('edit', $productGroup->getId()))
            ->assertSuccessful();
    }
}
