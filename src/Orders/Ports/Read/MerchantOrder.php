<?php

namespace Thinktomorrow\Trader\Orders\Ports\Read;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantOrder as MerchantOrderContract;

/**
 * Order presenter for merchant.
 */
class MerchantOrder extends AbstractPresenter implements MerchantOrderContract
{
    public function id(): string
    {
        return $this->getValue('id');
    }

    public function reference(): string
    {
        return $this->getValue('reference');
    }

    public function isBusiness(): bool
    {
        return $this->getValue('is_business', false);
    }

    public function confirmedAt(): string
    {
        return $this->getValue('confirmed_at', '', function ($confirmedAt) {
            return $confirmedAt->format('d/m/Y H:i');
        });
    }

    public function paidAt(): string
    {
        return $this->getValue('paid_at', '', function ($paidAt) {
            return $paidAt->format('d/m/Y H:i');
        });
    }

    public function shippedAt(): string
    {
        return $this->getValue('shipped_at', '', function ($shippedAt) {
            return $shippedAt->format('d/m/Y H:i');
        });
    }

    public function empty(): bool
    {
        return empty($this->items());
    }

    public function size(): int
    {
        return count($this->items());
    }

    public function items(): array
    {
        return $this->getValue('items', []);
    }

    public function discounts(): array
    {
        return $this->getValue('discounts', []);
    }

    public function hasShipping(): bool
    {
        return $this->getValue('shippingmethod_id') && $this->getValue('shippingrule_id');
    }

    public function shippingMethodId(): int
    {
        return $this->getValue('shippingmethod_id');
    }

    public function shippingRuleId(): int
    {
        return $this->getValue('shippingrule_id');
    }

    public function hasPayment(): bool
    {
        return $this->getValue('paymentmethod_id') && $this->getValue('paymentrule_id');
    }

    public function paymentMethodId(): int
    {
        return $this->getValue('paymentmethod_id');
    }

    public function paymentRuleId(): int
    {
        return $this->getValue('paymentrule_id');
    }

    public function total(): string
    {
        return $this->getValue('total', null, function ($total) {
            return Cash::from($total)->locale();
        });
    }

    public function subtotal(): string
    {
        return $this->getValue('subtotal', null, function ($subtotal) {
            return Cash::from($subtotal)->locale();
        });
    }

    public function tax(): string
    {
        return $this->getValue('tax', null, function ($tax) {
            return Cash::from($tax)->locale();
        });
    }

    public function taxRates(): array
    {
        return $this->getValue('tax_rates', [], function ($taxRates) {
            $rates = [];

            foreach ($taxRates as $key => $taxRate) {
                $rates[$key] = [
                    'percent' => $taxRate['percent']->asPercent(),
                    'tax'     => Cash::from($taxRate['tax'])->locale(),
                    'total'   => Cash::from($taxRate['total'])->locale(),
                ];
            }

            return $rates;
        });
    }

    public function discountTotal(): string
    {
        return $this->getValue('discount_total', null, function ($discountTotal) {
            return Cash::from($discountTotal)->locale();
        });
    }

    public function shippingTotal(): string
    {
        return $this->getValue('shipment_total', null, function ($shippingTotal) {
            return Cash::from($shippingTotal)->locale();
        });
    }

    public function paymentTotal(): string
    {
        return $this->getValue('payment_total', null, function ($paymentTotal) {
            return Cash::from($paymentTotal)->locale();
        });
    }
}
