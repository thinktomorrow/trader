<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Exceptions\CouldNotFindPaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;

class MysqlPaymentMethodRepository implements PaymentMethodRepository
{
    private static $paymentMethodTable = 'trader_payment_methods';

    public function save(PaymentMethod $paymentMethod): void
    {
        $state = $paymentMethod->getMappedData();

        if (! $this->exists($paymentMethod->paymentMethodId)) {
            DB::table(static::$paymentMethodTable)->insert($state);
        } else {
            DB::table(static::$paymentMethodTable)->where('payment_method_id', $paymentMethod->paymentMethodId->get())->update($state);
        }
    }

    private function exists(PaymentMethodId $paymentMethodId): bool
    {
        return DB::table(static::$paymentMethodTable)->where('payment_method_id', $paymentMethodId->get())->exists();
    }

    public function find(PaymentMethodId $paymentMethodId): PaymentMethod
    {
        $paymentMethodState = DB::table(static::$paymentMethodTable)
            ->where(static::$paymentMethodTable . '.payment_method_id', $paymentMethodId->get())
            ->first();

        if (! $paymentMethodState) {
            throw new CouldNotFindPaymentMethod('No payment method found by id [' . $paymentMethodId->get() . ']');
        }

        return PaymentMethod::fromMappedData((array)$paymentMethodState, []);
    }

    public function delete(PaymentMethodId $paymentMethodId): void
    {
        DB::table(static::$paymentMethodTable)->where('payment_method_id', $paymentMethodId->get())->delete();
    }

    public function nextReference(): PaymentMethodId
    {
        return PaymentMethodId::fromString((string)Uuid::uuid4());
    }
}
