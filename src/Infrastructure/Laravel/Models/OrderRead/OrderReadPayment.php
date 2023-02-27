<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;

abstract class OrderReadPayment
{
    use RendersData;
    use RendersMoney;

    protected Price $cost;
    protected string $payment_id;
    protected ?string $payment_method_id;
    protected PaymentState $state;
    protected iterable $discounts;
    protected array $data;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax = true;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static
    {
        $payment = new static();

        if (! $state['payment_state'] instanceof  PaymentState) {
            throw new \InvalidArgumentException('Payment state is expected to be instance of PaymentState. Instead ' . gettype($state['payment_state']) . ' is passed.');
        }

        $payment->payment_id = $state['payment_id'];
        $payment->payment_method_id = $state['payment_method_id'] ?: null;
        $payment->state = $state['payment_state'];
        $payment->cost = $state['cost'];
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

    public function getCostPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->cost->getIncludingVat() : $this->cost->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function includeTax(bool $includeTax = true): void
    {
        $this->include_tax = $includeTax;
    }

    public function getDiscounts(): iterable
    {
        // TODO: Implement getDiscounts() method.
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
}
