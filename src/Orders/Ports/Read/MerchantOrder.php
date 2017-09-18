<?php

namespace Thinktomorrow\Trader\Orders\Ports\Read;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;
use Thinktomorrow\Trader\Orders\Application\Reads\Merchant\MerchantOrder as MerchantOrderContract;

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

    public function confirmedAt(): string
    {
        return $this->getValue('confirmed_at', null, function ($confirmedAt) {
            return $confirmedAt->format('d/m/Y H:i');
        });
    }

    public function items(): array
    {
        return $this->getValue('items', []);
    }

    public function discounts(): array
    {
        return $this->getValue('discounts', []);
    }

    public function shipmentMethodId(): int
    {
        return $this->getValue('shippingMethodId', null, function ($shippingMethodId) {
            return $shippingMethodId->get();
        });
    }

    public function shippingRuleId(): int
    {
        return $this->getValue('shippingRuleId', null, function ($shippingRuleId) {
            return $shippingRuleId->get();
        });
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
                    'percent' => $taxRate['percent']->asPercent().'%',
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

    public function stateBadge(): string
    {
        return $this->getValue('state', null, function ($state) {

            // TODO translate state

            $flair = 'default';
            if ($state == 'paid' || $state == 'confirmed') {
                $flair = 'success';
            }
            if ($state == 'refunded' || $state == 'cancelled') {
                $flair = 'danger';
            }

            return "<span class='label label-{$flair}'>{$state}</span>";
        });
    }
}
