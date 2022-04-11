<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;

final class VariantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider variants
     */
    public function it_can_save_and_find_an_variant(Variant $variant)
    {
        foreach($this->repositories() as $repository) {
            $repository->save($variant);
            $variant->releaseEvents();

            $this->assertEquals($variant, $repository->find($variant->variantId));
        }
    }

    /**
     * @test
     * @dataProvider variants
     */
    public function it_can_delete_an_variant(Variant $variant)
    {
        $variantsNotFound = 0;

        foreach($this->repositories() as $repository) {
            $repository->save($variant);
            $repository->delete($variant->variantId);

            try{
                $repository->find($variant->variantId);
            } catch (CouldNotFindVariant $e) {
                $variantsNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $variantsNotFound);
    }

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach($this->repositories() as $repository) {
            $this->assertInstanceOf(VariantId::class, $repository->nextReference());
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryVariantRepository();
        yield new MysqlVariantRepository();
    }

    public function variants(): \Generator
    {
        yield [$this->createdVariant()];
        yield [$this->createdVariantWithOption()];
    }
}
