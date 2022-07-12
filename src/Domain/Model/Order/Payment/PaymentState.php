<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

use Thinktomorrow\Trader\Domain\Common\State\State;

enum PaymentState: string implements State
{
    case none = "none"; // The order is still in customer hands (incomplete) and a payment is not initialized yet.

    case initialized = "initialized"; // a payment link has been generated, but the customer hasn't yet completed payment.
    case paid = "paid"; // the customer has completed payment and settlement is guaranteed. proceed with shipment.
    case paid_by_merchant = 'paid_by_merchant'; // set to paid by merchant
    case canceled = "canceled"; // the merchant or the customer has canceled the transaction.
    case expired = "expired"; // the payment has expired, e.g. your customer has abandoned the payment.
    case failed = "failed"; // the payment has failed and cannot be completed. this could be that the issuer or acquirer has declined the transaction.
    case refunded = "refunded"; // the merchant has refunded the payment to the customer
    case charged_back = "charged_back"; // the customer has done a chargeback via the issuer or acquirer.
    case unknown = "unknown"; // unknown status

    public function getValueAsString(): string
    {
        return $this->value;
    }

    public static function getDefaultTransitions(): array
    {
        return [
            'initialize' => [
                'from' => [self::none],
                'to' => self::initialized,
            ],
            'pay' => [
                'from' => [self::initialized],
                'to' => self::paid,
            ],
            'pay_by_merchant' => [
                'from' => [self::initialized],
                'to' => self::paid_by_merchant,
            ],
            'cancel' => [
                'from' => [self::initialized],
                'to' => self::canceled,
            ],
            'expire' => [
                'from' => [self::initialized],
                'to' => self::expired,
            ],
            'refund' => [
                'from' => [self::paid],
                'to' => self::refunded,
            ],
            'charge_back' => [
                'from' => [self::paid],
                'to' => self::charged_back,
            ],
        ];
    }
}
