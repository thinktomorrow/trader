<?php


namespace Purchase\Cart\Reads;


interface CartRead
{
    public function totalAsString(): string { return $this->renderMoney($this->total()); }
    public function discountTotalAsString(): string { return $this->renderMoney($this->discountTotal()); }
    public function subTotalAsString(): string { return $this->renderMoney($this->subTotal()); }
    public function taxTotalAsString(): string { return $this->renderMoney($this->taxTotal()); }

}
