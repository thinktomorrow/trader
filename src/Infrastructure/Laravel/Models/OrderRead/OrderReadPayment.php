<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;

abstract class OrderReadPayment
{
    use RendersData;
    use RendersMoney;
    use WithServiceTotals;
    use WithFormattedServiceTotals;

    protected string $payment_id;
    protected ?string $payment_method_id;
    protected PaymentState $state;
    protected iterable $discounts;
    protected array $data;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static
    {
        $payment = new static();

        if (!$state['payment_state'] instanceof PaymentState) {
            throw new \InvalidArgumentException('Payment state is expected to be instance of PaymentState. Instead ' . gettype($state['payment_state']) . ' is passed.');
        }

        $payment->payment_id = $state['payment_id'];
        $payment->payment_method_id = $state['payment_method_id'] ?: null;
        $payment->state = $state['payment_state'];
        $payment->initializeServiceTotalsFromState($state);
        $payment->data = json_decode($state['data'], true);
        $payment->discounts = $discounts;

        return $payment;
    }

    public function getPaymentId(): string
    {
        return $this->payment_id;
    }

    public function getPaymentMethodId(): ?string
    {
        return $this->payment_method_id;
    }

    public function getDiscounts(): iterable
    {
        return $this->discounts;
    }

    public function getTitle(): ?string
    {
        return $this->data('title');
    }

    public function getDescription(): ?string
    {
        return $this->data('description');
    }

    public function getData(string $key, $default = null): mixed
    {
        return $this->data($key, null, $default);
    }
}
