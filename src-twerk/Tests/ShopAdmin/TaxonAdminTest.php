<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\ShopAdmin;

use App\ShopAdmin\Catalog\Taxa\TaxonModel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Chief\Managers\Register\Registry;
use Thinktomorrow\Trader\Tests\Catalog\Options\OptionTestHelpers;

class TaxonAdminTest extends TestCase
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

        $this->manager = app(Registry::class)->manager(TaxonModel::managedModelKey());
    }

    /** @test */
    public function itCanViewTaxonIndexPage()
    {
        $rootTaxon = $this->createTaxon('brands');
        $taxon = $this->createTaxon('nike', null, $rootTaxon);

        $this->asAdmin()->get($this->manager->route('index'))
            ->assertSuccessful();

        $this->asAdmin()->get($this->manager->route('taxon-index', $rootTaxon->getId()))
            ->assertSuccessful();
    }

    /** @test */
    public function itCanViewTaxonCreatePage()
    {
        $this->asAdmin()->get($this->manager->route('create'))
            ->assertSuccessful();

        $rootTaxon = $this->createTaxon('brands');
        $this->asAdmin()->get($this->manager->route('taxon-create', $rootTaxon->getId()))
            ->assertSuccessful();
    }

    /** @test */
    public function itCanViewTaxonEditPage()
    {
        $rootTaxon = $this->createTaxon('brands');
        $taxon = $this->createTaxon('nike', null, $rootTaxon);

        $this->asAdmin()->get($this->manager->route('edit', $rootTaxon->getId()))
            ->assertSuccessful();
    }
}
