<?php

namespace Thinktomorrow\Trader\Payment\Domain\Gateways\Adapters;

interface OffsiteGateway
{
    /**
     * Request an unique and authorized offsite payment url for the visitor.
     *
     * @return string
     */
    public function paymentUrl(): string;
}
