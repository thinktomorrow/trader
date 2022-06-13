<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;

class MysqlPaymentMethodRepository implements PaymentMethodRepository
{
    public function __construct()
    {
    }

    public function save(PaymentMethod $paymentMethod): void
    {
        // TODO: Implement save() method.
    }

    public function find(PaymentMethodId $paymentMethodId): PaymentMethod
    {
        // TODO: Implement find() method.
    }

    public function delete(PaymentMethodId $paymentMethodId): void
    {
        // TODO: Implement delete() method.
    }

    public function nextReference(): PaymentMethodId
    {
        // TODO: Implement nextReference() method.
    }
}
