<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Exceptions\CouldNotFindPaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class PaymentMethodRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider paymentMethods
     */
    public function it_can_save_and_find_a_method(PaymentMethod $paymentMethod)
    {
        foreach ($this->repositories() as $repository) {
            $repository->save($paymentMethod);
            $paymentMethod->releaseEvents();

            $this->assertEquals($paymentMethod, $repository->find($paymentMethod->paymentMethodId));
        }
    }

    /**
     * @test
     * @dataProvider paymentMethods
     */
    public function it_can_delete_a_product(PaymentMethod $paymentMethod)
    {
        $methodsNotFound = 0;

        foreach ($this->repositories() as $repository) {
            $repository->save($paymentMethod);
            $repository->delete($paymentMethod->paymentMethodId);

            try {
                $repository->find($paymentMethod->paymentMethodId);
            } catch (CouldNotFindPaymentMethod $e) {
                $methodsNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $methodsNotFound);
    }

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(PaymentMethodId::class, $repository->nextReference());
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryPaymentMethodRepository();
        yield new MysqlPaymentMethodRepository(new TestContainer());
    }

    public function paymentMethods(): \Generator
    {
        yield [$this->createPaymentMethod()];
    }
}
