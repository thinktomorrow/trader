<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Details;

use Thinktomorrow\Trader\Domain\Model\Payment\PaymentTotal;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentState;

final class Payment
{
    public readonly PaymentId $paymentId;
    public readonly PaymentState $paymentState;
    public readonly PaymentTotal $paymentTotal;

    private function __construct(){}

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $payment = new static();

        $payment->paymentId = PaymentId::fromString($state['id']);
        $payment->paymentState = PaymentState::from($state['state']);
        $payment->paymentTotal = PaymentTotal::fromScalars($state['cost'], 'EUR', $state['tax_rate'], $state['includes_vat']);

        return $payment;
    }
}
