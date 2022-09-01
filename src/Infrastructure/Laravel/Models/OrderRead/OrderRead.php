<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Money\Money;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLogEntry;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Common\Price\PriceTotal;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;

abstract class OrderRead
{
    use RendersData;
    use RendersMoney;

    protected string $orderId;
    protected string $orderReference;
    protected ?string $invoiceReference;
    protected OrderState $state;
    protected iterable $lines;
    protected ?MerchantOrderShippingAddress $shippingAddress;
    protected ?MerchantOrderBillingAddress $billingAddress;
    /** @var MerchantOrderShipping[] */
    protected array $shippings;

    /** @var MerchantOrderPayment[] */
    protected array $payments;

    protected ?MerchantOrderShopper $shopper;
    protected array $discounts;

    /** @var MerchantOrderLogEntry[] */
    protected array $logEntries;
    protected array $data;

    protected PriceTotal $total;
    protected Money $taxTotal;
    protected PriceTotal $subtotal;
    protected Price $discountTotal;
    protected Price $shippingCost;
    protected Price $paymentCost;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax = true;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $childObjects, array $discounts): static
    {
        $order = new static();

        $order->orderId = $state['order_id'];
        $order->state = OrderState::from($state['order_state']);
        $order->orderReference = $state['order_ref'];
        $order->invoiceReference = $state['invoice_ref'];

        $order->total = $state['total'];
        $order->taxTotal = $state['taxTotal'];
        $order->subtotal = $state['subtotal'];
        $order->discountTotal = $state['discountTotal'];
        $order->shippingCost = $state['shippingCost'];
        $order->paymentCost = $state['paymentCost'];

        $order->lines = $childObjects[MerchantOrderLine::class];
        $order->shippingAddress = $childObjects[MerchantOrderShippingAddress::class];
        $order->billingAddress = $childObjects[MerchantOrderBillingAddress::class];
        $order->shippings = $childObjects[MerchantOrderShipping::class];
        $order->payments = $childObjects[MerchantOrderPayment::class];
        $order->shopper = $childObjects[MerchantOrderShopper::class];
        $order->logEntries = $childObjects[MerchantOrderLogEntry::class];

        $order->data = json_decode($state['data'], true);
        $order->discounts = $discounts;

        return $order;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getOrderReference(): string
    {
        return $this->orderReference;
    }

    public function getInvoiceReference(): ?string
    {
        return $this->invoiceReference;
    }

    public function getLines(): iterable
    {
        return $this->lines;
    }

    public function isEmpty(): bool
    {
        return $this->getSize() < 1;
    }

    public function getSize(): int
    {
        return count($this->getLines());
    }

    public function getQuantity(): int
    {
        return array_reduce((array) $this->getLines(), fn ($carry, $line) => $carry + $line->getQuantity(), 0);
    }

    public function includeTax(bool $includeTax = true): void
    {
        $this->include_tax = $includeTax;
    }

    public function getTotalPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->total->getIncludingVat() : $this->total->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getSubtotalPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->subtotal->getIncludingVat() : $this->subtotal->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getShippingCost(): ?string
    {
        if (! $this->shippingCost->getMoney()->isPositive()) {
            return null;
        }

        return $this->renderMoney(
            $this->include_tax ? $this->shippingCost->getIncludingVat() : $this->shippingCost->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getPaymentCost(): ?string
    {
        if (! $this->paymentCost->getMoney()->isPositive()) {
            return null;
        }

        return $this->renderMoney(
            $this->include_tax ? $this->paymentCost->getIncludingVat() : $this->paymentCost->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getDiscountPrice(): ?string
    {
        if (! $this->discountTotal->getMoney()->isPositive()) {
            return null;
        }

        return $this->renderMoney(
            $this->include_tax ? $this->discountTotal->getIncludingVat() : $this->discountTotal->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getTaxPrice(): string
    {
        return $this->renderMoney(
            $this->taxTotal,
            $this->getLocale()
        );
    }

    public function getShopper(): MerchantOrderShopper
    {
        return $this->shopper;
    }

    public function getShippings(): array
    {
        return $this->shippings;
    }

    public function getPayments(): array
    {
        return $this->payments;
    }

    public function getShippingAddress(): ?MerchantOrderShippingAddress
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): ?MerchantOrderBillingAddress
    {
        return $this->billingAddress;
    }

    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    public function getEnteredCoupon(): ?string
    {
        return $this->data('coupon_code');
    }

    public function getAllDiscounts(): iterable
    {
        $allDiscounts = $this->getDiscounts();

        foreach ($this->getShippings() as $shipping) {
            $allDiscounts = array_merge($allDiscounts, $shipping->getDiscounts());
        }

        foreach ($this->getPayments() as $payment) {
            $allDiscounts = array_merge($allDiscounts, $payment->getDiscounts());
        }

        foreach ($this->getLines() as $line) {
            $allDiscounts = array_merge($allDiscounts, $line->getDiscounts());
        }

        // TODO:: make sure they are unique...
        return $allDiscounts;
    }
}
