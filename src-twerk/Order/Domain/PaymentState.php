<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Domain;

use Thinktomorrow\Trader\Common\State\StateValueDefaults;

class PaymentState
{
    use StateValueDefaults;

    /* @var string identify the state key */
    public static string $KEY = 'payment_state';

    const INITIALISED = "initialized"; // A payment link has been generated, but the customer hasn't yet completed payment.
    const PAID = "paid"; // The customer has completed payment and settlement is guaranteed. Proceed with shipment.
    const CANCELED = "canceled"; // The merchant or the customer has canceled the transaction.
    const EXPIRED = "expired"; // The payment has expired, e.g. your customer has abandoned the payment.
    const FAILED = "failed"; // The payment has failed and cannot be completed. This could be that the issuer or acquirer has declined the transaction.
    const REFUNDED = "refunded"; // The merchant has refunded the payment to the customer
    const CHARGED_BACK = "charged_back"; // The customer has done a chargeback via the issuer or acquirer.
    const UNKNOWN = "unknown"; // unknown status
}
