<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderEvent;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;

abstract class OrderRead
{
    use RendersData;
    use RendersMoney;
    use WithImmutableOrderTotals;
    use WithFormattedOrderTotals;

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

    /** @var MerchantOrderEvent[] */
    protected array $orderEvents;
    protected array $data;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $childObjects, array $discounts): static
    {
        $order = new static();

        if (!$state['order_state'] instanceof OrderState) {
            throw new \InvalidArgumentException('Order state is expected to be instance of OrderState. Instead ' . gettype($state['order_state']) . ' is passed.');
        }

        $order->orderId = $state['order_id'];
        $order->state = $state['order_state'];
        $order->state = $state['order_state'];
        $order->orderReference = $state['order_ref'];
        $order->invoiceReference = $state['invoice_ref'];

        $order->initializeOrderTotalsFromState($state);

        $order->lines = $childObjects[MerchantOrderLine::class];
        $order->shippingAddress = $childObjects[MerchantOrderShippingAddress::class];
        $order->billingAddress = $childObjects[MerchantOrderBillingAddress::class];
        $order->shippings = $childObjects[MerchantOrderShipping::class];
        $order->payments = $childObjects[MerchantOrderPayment::class];
        $order->shopper = $childObjects[MerchantOrderShopper::class];
        $order->orderEvents = $childObjects[MerchantOrderEvent::class];

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
        return array_reduce((array)$this->getLines(), fn($carry, $line) => $carry + $line->getQuantity(), 0);
    }

    public function isVatExempt(): bool
    {
        return $this->dataAsPrimitive('is_vat_exempt') ?? false;
    }

    public function getShopper(): MerchantOrderShopper
    {
        return $this->shopper;
    }

    public function getShippings(): array
    {
        return $this->shippings;
    }

    public function findShipping(string $shippingId): ?MerchantOrderShipping
    {
        foreach ($this->shippings as $shipping) {
            if ($shipping->getShippingId() == $shippingId) {
                return $shipping;
            }
        }

        return null;
    }

    public function getPayments(): array
    {
        return $this->payments;
    }

    public function findPayment(string $paymentId): ?MerchantOrderPayment
    {
        foreach ($this->payments as $payment) {
            if ($payment->getPaymentId() == $paymentId) {
                return $payment;
            }
        }

        return null;
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

    public function getData(string $key, ?string $language = null, $default = null)
    {
        return $this->data($key, $language, $default);
    }
}
