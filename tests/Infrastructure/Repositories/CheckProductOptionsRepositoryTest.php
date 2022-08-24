<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCheckProductOptionsRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class CheckProductOptionsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_check_if_option_combination_is_already_used()
    {
        $product = $this->createdProductWithOptions();
        (new MysqlProductRepository(new MysqlVariantRepository(new TestContainer())))->save($product);

        $variant = $product->getVariants()[0];

        foreach ($this->repositories() as $repository) {
            // Check if combo already exists
            $this->assertTrue($repository->exists($variant->getMappedData()['option_value_ids']));

            // Passed variant is ignored in the check
            $this->assertFalse($repository->exists($variant->getMappedData()['option_value_ids'], $variant->variantId->get()));

            $this->assertFalse($repository->exists(['aaa']));
            $this->assertFalse($repository->exists(['aaa', 'bbb']));
        }
    }

    private function repositories(): \Generator
    {
        yield new MysqlCheckProductOptionsRepository();
    }
}
