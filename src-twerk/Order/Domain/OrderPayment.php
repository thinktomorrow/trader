<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Domain;

use Thinktomorrow\Trader\Order\Domain\PaymentState;
use Money\Money;
use Thinktomorrow\Trader\Common\Cash\RendersMoney;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Taxes\Taxable;
use Thinktomorrow\Trader\Taxes\TaxRate;

class OrderPayment implements Discountable, Taxable
{
    use MethodDefaults;
    use RendersMoney;

    private ?string $id;
    private string $method;
    private PaymentState $paymentState;
    private Money $subTotal;
    private TaxRate $taxRate;
    private AppliedDiscountCollection $discounts;
    private array $data;

    public function __construct(?string $id, string $method, PaymentState $paymentState, Money $subTotal, TaxRate $taxRate, AppliedDiscountCollection $discounts, array $data)
    {
        if (! is_null($id) && ! $id) {
            throw new \InvalidArgumentException('empty strings for id value is not allowed. Use null instead');
        }

        if (! $method) {
            throw new \InvalidArgumentException('The method parameter cannot be empty');
        }

        $this->id = $id;
        $this->method = $method;
        $this->paymentState = $paymentState;
        $this->subTotal = $subTotal;
        $this->taxRate = $taxRate;
        $this->discounts = $discounts;
        $this->data = $data;

        foreach ($discounts as $discount) {
            $this->addDiscount($discount);
        }
    }

    public static function empty(): self
    {
        return new static(null, 'unknown', PaymentState::fromString(PaymentState::UNKNOWN), Money::EUR(0), TaxRate::default(), new AppliedDiscountCollection(), []);
    }

    public function getPaymentState(): PaymentState
    {
        return $this->paymentState;
    }

    public function replaceSubTotal(Money $subTotal): self
    {
        return new static($this->id, $this->method, $this->paymentState, $subTotal, $this->taxRate, $this->discounts, $this->data);
    }

    public function replaceMethod(string $method): self
    {
        return new static($this->id, $method, $this->paymentState, $this->subTotal, $this->taxRate, $this->discounts, $this->data);
    }

    public function replacePaymentState(PaymentState $paymentState): self
    {
        return new static($this->id, $this->method, $paymentState, $this->subTotal, $this->taxRate, $this->discounts, $this->data);
    }

    public function replaceData(array $data): self
    {
        return new static($this->id, $this->method, $this->paymentState, $this->subTotal, $this->taxRate, $this->discounts, array_merge($this->data, $data));
    }
}
