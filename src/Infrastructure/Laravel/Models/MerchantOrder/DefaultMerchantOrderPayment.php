<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;

class DefaultMerchantOrderPayment implements MerchantOrderPayment
{
    use RendersData;
    use RendersMoney;

    protected Price $cost;
    protected string $payment_id;
    protected string $payment_method_id;
    protected iterable $discounts;
    protected array $data;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax = true;

    private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static
    {
        $payment = new static();

        $payment->payment_id = $state['payment_id'];
        $payment->payment_method_id = $state['payment_method_id'];
        $payment->cost = $state['cost'];
        $payment->data = json_decode($state['data'], true);
        $payment->discounts = $discounts;

        return $payment;
    }

    public function getPaymentId(): string
    {
        return $this->payment_id;
    }

    public function getPaymentMethodId(): string
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
