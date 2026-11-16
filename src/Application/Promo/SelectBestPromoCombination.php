<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderPricingSnapshot;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustPayment;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ApplicableDiscountFactory;
use Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination\ApplyPromoCombination;
use Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination\FindBestCombination;
use Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination\PromoCandidates;
use Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination\SelectedServiceProfiles;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\TraderConfig;

/** Selects the compatible promos that yield the lowest order total. */
final class SelectBestPromoCombination
{
    private ApplyPromoCombination $applicator;

    private FindBestCombination $finder;

    public function __construct(
        OrderRepository $orderRepository,
        private TraderConfig $config,
        AdjustOrderPricingSnapshot $adjustOrderPricingSnapshot,
        private AdjustShipping $adjustShipping,
        private AdjustPayment $adjustPayment,
        private ApplicableDiscountFactory $applicableDiscountFactory,
    ) {
        $this->applicator = new ApplyPromoCombination($orderRepository, $adjustShipping, $adjustPayment);
        $this->finder = new FindBestCombination($this->applicator, $adjustOrderPricingSnapshot);
    }

    /** @param list<Promo> $availablePromos */
    public function apply(Order $order, array $availablePromos): void
    {
        $this->applicator->clearDiscounts($order);

        if (! $order->inCustomerHands()) {
            return;
        }

        $candidates = new PromoCandidates($order, $availablePromos, $this->applicableDiscountFactory, $this->config);

        // Preload the order services to avoid loading them on each combination search
        $profiles = $candidates->promos() === [] ? null : new SelectedServiceProfiles(
            $this->adjustShipping->selectedProfileFor($order),
            $this->adjustPayment->selectedMethodFor($order),
        );

        $winner = $this->finder->find($order, $candidates, $profiles);

        $this->applicator->apply($order, $winner, $candidates, profiles: $profiles);
    }
}
