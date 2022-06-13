<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\PaymentMethod;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;

class PaymentMethod implements Aggregate
{
    use HasData;

    public readonly PaymentMethodId $paymentMethodId;
    private Money $rate;

    private function __construct()
    {
    }

    public static function create(PaymentMethodId $paymentMethodId, Money $rate): static
    {
        $method = new static();

        $method->paymentMethodId = $paymentMethodId;
        $method->rate = $rate;

        return $method;
    }

    public function updateRate(Money $rate): void
    {
        $this->rate = $rate;
    }

    public function getRate(): Money
    {
        return $this->rate;
    }

    public function getMappedData(): array
    {
        return [
            'payment_method_id' => $this->paymentMethodId->get(),
            'rate'              => $this->rate->getAmount(),
            'data'              => json_encode($this->data),
        ];
    }

    public static function make(PaymentMethodId $paymentMethodId, Money $rate): static
    {
        $method = new static();

        $method->paymentMethodId = $paymentMethodId;
        $method->rate = $rate;

        return $method;
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $method = new static();

        $method->paymentMethodId = PaymentMethodId::fromString($state['payment_id']);
        $method->rate = Cash::make($state['rate']);
        $method->data = json_decode($state['data'], true);

        return $method;
    }

    public function getChildEntities(): array
    {
        return [];
    }
}
