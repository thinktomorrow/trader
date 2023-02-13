<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\PaymentMethodForCart;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\PaymentMethodForCartRepository;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Exceptions\CouldNotFindPaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState;

class MysqlPaymentMethodRepository implements PaymentMethodRepository, PaymentMethodForCartRepository
{
    private static $paymentMethodTable = 'trader_payment_methods';
    private static $paymentMethodCountryTable = 'trader_payment_method_countries';
    private static $countryTable = 'trader_countries';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function save(PaymentMethod $paymentMethod): void
    {
        $state = $paymentMethod->getMappedData();

        if (! $this->exists($paymentMethod->paymentMethodId)) {
            DB::table(static::$paymentMethodTable)->insert($state);
        } else {
            DB::table(static::$paymentMethodTable)->where('payment_method_id', $paymentMethod->paymentMethodId->get())->update($state);
        }

        $this->upsertCountryIds($paymentMethod);
    }

    private function upsertCountryIds(PaymentMethod $paymentMethod): void
    {
        DB::table(static::$paymentMethodCountryTable)
            ->where('payment_method_id', $paymentMethod->paymentMethodId->get())
            ->delete();

        DB::table(static::$paymentMethodCountryTable)
            ->insert(array_map(fn (string $countryId) => [
                'payment_method_id' => $paymentMethod->paymentMethodId->get(),
                'country_id' => $countryId,
            ], $paymentMethod->getChildEntities()[CountryId::class]));
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

        $countryStates = DB::table(static::$paymentMethodCountryTable)
            ->join(static::$countryTable, static::$paymentMethodCountryTable.'.country_id', '=', static::$countryTable.'.country_id')
            ->where(static::$paymentMethodCountryTable . '.payment_method_id', $paymentMethodId->get())
            ->where(static::$countryTable . '.active', '1')
            ->select(static::$countryTable.'.country_id')
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();

        return PaymentMethod::fromMappedData((array)$paymentMethodState, [
            CountryId::class => $countryStates,
        ]);
    }

    public function delete(PaymentMethodId $paymentMethodId): void
    {
        DB::table(static::$paymentMethodTable)->where('payment_method_id', $paymentMethodId->get())->delete();
    }

    public function nextReference(): PaymentMethodId
    {
        return PaymentMethodId::fromString((string)Uuid::uuid4());
    }

    public function findAllPaymentMethodsForCart(?string $countryId = null): array
    {
        $builder = DB::table(static::$paymentMethodTable)
            ->whereIn('state', PaymentMethodState::onlineStates())
            ->orderBy('order_column', 'ASC');

        if ($countryId) {
            $builder->leftJoin(static::$paymentMethodCountryTable, static::$paymentMethodTable.'.payment_method_id', '=', static::$paymentMethodCountryTable.'.payment_method_id')
                ->where(static::$paymentMethodCountryTable . '.country_id', $countryId)
                ->select(static::$paymentMethodTable.'.*');
        }

        return $builder
            ->get()
            ->map(fn ($paymentMethodState) => $this->container->get(PaymentMethodForCart::class)::fromMappedData((array)$paymentMethodState))
            ->toArray();
    }
}
