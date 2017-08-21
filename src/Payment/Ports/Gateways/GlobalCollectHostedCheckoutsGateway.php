<?php

namespace Thinktomorrow\Trader\Payment\Ports\Gateways;

use Thinktomorrow\Trader\Payment\Domain\Gateways\Adapters\OffsiteGateway;

class GlobalCollectHostedCheckoutsGateway implements OffsiteGateway
{
    public function paymentUrl(): string
    {
        // call the omnipay service and let it return the required data

        // Returned data should be kept for future references

        // How to check transaction status?
    }
}
