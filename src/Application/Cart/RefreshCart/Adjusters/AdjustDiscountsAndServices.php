<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Promo\PromoApplicationScope;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

final class AdjustDiscountsAndServices implements Adjuster
{
    public function __construct(
        private AdjustDiscounts $adjustDiscounts,
        private AdjustShipping $adjustShipping,
        private AdjustPayment $adjustPayment,
    ) {}

    public function adjust(Order $order): void
    {
        $selectedPromos = $this->adjustDiscounts->resetDiscountsAndSelectPromos($order);
        $this->adjustDiscounts->applySelectedPromos($order, $selectedPromos, PromoApplicationScope::Lines);

        $this->adjustShipping->adjust($order);
        $this->adjustPayment->adjust($order);

        $this->adjustDiscounts->applySelectedPromos($order, $selectedPromos, PromoApplicationScope::ServicesAndOrder);
    }
}
