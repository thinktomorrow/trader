<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentFailed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentInitialized;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentMarkedPaidByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentRefunded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentRefundedByMerchant;

enum DefaultPaymentState: string implements PaymentState
{
    case none = "none"; // The order is still in customer hands (incomplete) and a payment is not initialized yet.

    case initialized = "initialized"; // a payment link has been generated, but the customer hasn't yet completed payment.
    case paid = "paid"; // the customer has completed payment and settlement is guaranteed. proceed with shipment.
    case paid_by_merchant = 'paid_by_merchant'; // set to paid by merchant
    case canceled = "canceled"; // the merchant or the customer has canceled the transaction.
    case expired = "expired"; // the payment has expired, e.g. your customer has abandoned the payment.
    case failed = "failed"; // the payment has failed and cannot be completed. this could be that the issuer or acquirer has declined the transaction.
    case refunded = "refunded"; // the merchant has refunded the payment to the customer via the payment provider.
    case refunded_by_merchant = "refunded_by_merchant"; // the merchant has manually refunded the payment to the customer
    case charged_back = "charged_back"; // the customer has done a chargeback via the issuer or acquirer.
    case unknown = "unknown"; // unknown status

    public static function fromString(string $state): self
    {
        return static::from($state);
    }

    public function getValueAsString(): string
    {
        return $this->value;
    }

    public function equals($other): bool
    {
        return (get_class($this) === get_class($other) && $this->getValueAsString() === $other->getValueAsString());
    }

    public static function getDefaultState(): self
    {
        return static::none;
    }

    public static function getStates(): array
    {
        return static::cases();
    }

    public static function getTransitions(): array
    {
        return [
            'initialize' => [
                'from' => [self::none],
                'to' => self::initialized,
            ],
            'pay_by_merchant' => [
                'from' => [self::initialized, self::none],
                'to' => self::paid_by_merchant,
            ],
            'pay' => [
                'from' => [self::initialized, self::paid_by_merchant],
                'to' => self::paid,
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
            'refund_by_merchant' => [
                'from' => [self::paid],
                'to' => self::refunded_by_merchant,
            ],
            'charge_back' => [
                'from' => [self::paid],
                'to' => self::charged_back,
            ],
        ];
    }

    public static function getEventMapping(): array
    {
        return [
            self::initialized->value => PaymentInitialized::class,
            self::paid->value => PaymentPaid::class,
            self::paid_by_merchant->value => PaymentMarkedPaidByMerchant::class,
            self::canceled->value => PaymentFailed::class,
            self::failed->value => PaymentFailed::class,
            self::expired->value => PaymentFailed::class,
            self::refunded->value => PaymentRefunded::class,
            self::refunded_by_merchant->value => PaymentRefundedByMerchant::class,
            self::charged_back->value => PaymentRefunded::class,
        ];
    }
}
