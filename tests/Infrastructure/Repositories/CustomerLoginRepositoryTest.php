<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

final class CustomerLoginRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_customer_login()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->customerLoginRepository();

            $customer = $orderContext->createCustomer();
            $customerLogin = $orderContext->createCustomerLogin($customer);

            $this->assertEquals($customerLogin, $repository->find($customerLogin->customerId));
        }
    }
}
