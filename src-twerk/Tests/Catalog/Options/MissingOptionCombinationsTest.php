<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog\Options;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Options\Application\MissingOptionCombinations;
use Thinktomorrow\Trader\Catalog\Options\Domain\Option;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupRepository;

class MissingOptionCombinationsTest extends TestCase
{
    use DatabaseMigrations;
    use OptionTestHelpers;

    /** @test */
    public function it_can_get_any_warnings_on_missing_combinations()
    {
        $productGroup = $this->createProductGroup();
        $this->defaultOptionSetup($productGroup->getId());

        $product = $this->createProduct([
            'options' => [1,2],
        ], $productGroup->getId());

        $productGroup = app(ProductGroupRepository::class)->findById($productGroup->getId());

        $missingCombos = app(MissingOptionCombinations::class)->scan($productGroup);
        $this->assertCount(2, $missingCombos);
        $this->assertCount(2, $missingCombos[0]);
        $this->assertCount(2, $missingCombos[1]);
        $this->assertInstanceOf(Option::class, $missingCombos[0][0]);
    }
}
