<?php

namespace Thinktomorrow\Trader\Order\Ports\Web\Merchant;

use Thinktomorrow\Trader\Common\Domain\Price\MoneyRender;
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
        $collection = [];

        foreach($this->getValue('items',[]) as $id => $itemValues)
        {
            $collection[$id] = new Item($itemValues);
        }

        return $collection;
    }

    public function reference()
    {
        return $this->getValue('reference');
    }

    public function total()
    {
        return $this->getValue('total',null,function($total){
            return (new MoneyRender())->locale($total);
        });
    }

    public function subtotal()
    {
        return $this->getValue('subtotal',null,function($subtotal){
            return (new MoneyRender())->locale($subtotal);
        });
    }

    public function tax()
    {
        return $this->getValue('tax',null,function($tax){
            return (new MoneyRender())->locale($tax);
        });
    }

    public function taxRate()
    {
        return $this->getValue('tax_rate',null,function($rate){
            return $rate->asPercent().'%';
        });
    }

    public function shipmentTotal()
    {
        return $this->getValue('shipment_total',null,function($shipmentTotal){
            return (new MoneyRender())->locale($shipmentTotal);
        });
    }

    public function paymentTotal()
    {
        return $this->getValue('payment_total',null,function($paymentTotal){
            return (new MoneyRender())->locale($paymentTotal);
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