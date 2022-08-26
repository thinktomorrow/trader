<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderRead;

class DefaultMerchantOrder extends OrderRead implements MerchantOrder
{
    protected ?string $confirmed_at;
    protected ?string $paid_at;
    protected ?string $delivered_at;

    public static function fromMappedData(array $state, array $childObjects, array $discounts): static
    {
        $order = parent::fromMappedData($state, $childObjects, $discounts);

        $order->confirmed_at = $state['confirmed_at'] ?? null;
        $order->paid_at = $state['paid_at'] ?? null;
        $order->delivered_at = $state['delivered_at'] ?? null;

        return $order;
    }

    public function getState(): string
    {
        return $this->state->getValueAsString();
    }

    public function getConfirmedAt(): ?string
    {
        return $this->confirmed_at;
    }

    public function getPaidAt(): ?string
    {
        return $this->paid_at;
    }

    public function getDeliveredAt(): ?string
    {
        return $this->delivered_at;
    }

    public function getShopper(): MerchantOrderShopper
    {
        return parent::getShopper();
    }

    /** @return MerchantOrderShipping[] */
    public function getShippings(): array
    {
        return parent::getShippings();
    }

    /** @return MerchantOrderPayment[] */
    public function getPayments(): array
    {
        return parent::getPayments();
    }

    public function getShippingAddress(): MerchantOrderShippingAddress
    {
        return parent::getShippingAddress();
    }

    public function getBillingAddress(): MerchantOrderBillingAddress
    {
        return parent::getBillingAddress();
    }

    public function inCustomerHands(): bool
    {
        return $this->state->inCustomerHands();
    }
}
