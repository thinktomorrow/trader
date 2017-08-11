<?php

namespace Thinktomorrow\Trader\Order\Ports\Web\Merchant;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;

/**
 * Order presenter for merchant
 *
 * @package src\Order
 */
class Order extends AbstractPresenter
{
    public function items(): array
    {
        return $this->getValue('items',[]);
    }

    public function discounts(): array
    {
        return $this->getValue('discounts',[]);
    }

    public function reference()
    {
        return $this->getValue('reference');
    }

    public function total()
    {
        return $this->getValue('total',null,function($total){
            return (new Cash())->locale($total);
        });
    }

    public function subtotal()
    {
        return $this->getValue('subtotal',null,function($subtotal){
            return (new Cash())->locale($subtotal);
        });
    }

    public function tax()
    {
        return $this->getValue('tax',null,function($tax){
            return (new Cash())->locale($tax);
        });
    }

    public function taxRates()
    {
        return $this->getValue('tax_rates',[],function($taxRates){
            $rates = [];

            foreach($taxRates as $key => $taxRate)
            {
                $rates[$key] = [
                    'percent' => $taxRate['percent']->asPercent().'%',
                    'tax' => (new Cash())->locale($taxRate['tax']),
                    'total' => (new Cash())->locale($taxRate['total']),
                ];
            }

            return $rates;
        });
    }

    public function discountTotal()
    {
        return $this->getValue('discount_total',null,function($discountTotal){
            return (new Cash())->locale($discountTotal);
        });
    }

    public function shipmentTotal()
    {
        return $this->getValue('shipment_total',null,function($shipmentTotal){
            return (new Cash())->locale($shipmentTotal);
        });
    }

    public function paymentTotal()
    {
        return $this->getValue('payment_total',null,function($paymentTotal){
            return (new Cash())->locale($paymentTotal);
        });
    }

    public function confirmedAt()
    {
        return $this->getValue('confirmed_at', null, function($confirmedAt){
            return $confirmedAt->format('d/m/Y H:i');
        });
    }

    public function stateBadge()
    {
        return $this->getValue('state',null,function($state){

            // TODO translate state

            $flair = 'default';
            if($state == 'paid' || $state == 'confirmed') $flair = 'success';
            if($state == 'refunded' || $state == 'cancelled') $flair = 'danger';

            return "<span class='label label-{$flair}'>{$state}</span>";
        });
    }
}