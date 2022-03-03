<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories\ProductOptions;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantForProductOptionRepository;

final class VariantForProductOptionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_all_variants()
    {
        $product = $this->createdProductWithVariant();

        // TODO: Save the product first...

        /** @var VariantForProductOptionRepository $repository */
        foreach($this->repositories() as $repository) {
            $variants = $repository->getVariantsForProductOption(ProductId::fromString('xxx'));
            dd($variants);
        }
    }

    private function repositories(): \Generator
    {
//        yield new InMemoryCustomerRepository();
        yield new MysqlVariantRepository();
    }

    public function customers(): \Generator
    {
        yield [$this->createdCustomer()];

        yield [Customer::create(
            CustomerId::fromString('xxx'),
            Email::fromString('ben@thinktomorrow.be'),
            'Ben', 'Cavens'
        )];
    }
}
