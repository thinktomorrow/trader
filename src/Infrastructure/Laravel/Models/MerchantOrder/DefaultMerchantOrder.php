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
    public function getState(): string
    {
        return $this->state->getValueAsString();
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
