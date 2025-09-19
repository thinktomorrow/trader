<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Cart\PaymentMethod\PaymentMethodForCartRepository;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Exceptions\CouldNotFindPaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\PaymentMethod\DefaultPaymentMethodForCart;

class InMemoryPaymentMethodRepository implements PaymentMethodRepository, PaymentMethodForCartRepository, InMemoryRepository
{
    public static array $paymentMethods = [];

    private string $nextReference = 'ppp-123';

    public function save(PaymentMethod $paymentMethod): void
    {
        static::$paymentMethods[$paymentMethod->paymentMethodId->get()] = $paymentMethod;
    }

    public function find(PaymentMethodId $paymentMethodId): PaymentMethod
    {
        if (! isset(static::$paymentMethods[$paymentMethodId->get()])) {
            throw new CouldNotFindPaymentMethod('No payment found by id ' . $paymentMethodId);
        }

        return static::$paymentMethods[$paymentMethodId->get()];
    }

    public function delete(PaymentMethodId $paymentMethodId): void
    {
        if (! isset(static::$paymentMethods[$paymentMethodId->get()])) {
            throw new CouldNotFindPaymentMethod('No available payment found by id ' . $paymentMethodId);
        }

        unset(static::$paymentMethods[$paymentMethodId->get()]);
    }

    public function nextReference(): PaymentMethodId
    {
        return PaymentMethodId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public static function clear()
    {
        static::$paymentMethods = [];
    }

    public function findAllPaymentMethodsForCart(?string $countryId = null): array
    {
        $paymentMethods = [];

        foreach (static::$paymentMethods as $paymentMethod) {
            if ($paymentMethod->getState() == PaymentMethodState::online && (! $countryId || $paymentMethod->hasCountry(CountryId::fromString($countryId)))) {
                $paymentMethods[] = $paymentMethod;
            }
        }

        return array_map(fn ($paymentMethod) => DefaultPaymentMethodForCart::fromMappedData($paymentMethod->getMappedData()), $paymentMethods);
    }
}
