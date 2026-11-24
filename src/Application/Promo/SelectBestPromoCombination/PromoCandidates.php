<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination;

use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ApplicableDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ApplicableDiscountFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ProductDiscount;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\TraderConfig;

/** Per-selection discount lookup; product applicability is measured on the original order. */
final class PromoCandidates
{
    /** @var array<string, list<ApplicableDiscount>> */
    private array $discountsByPromo = [];

    /** @var array<string, array<string, list<ProductDiscount>>> */
    private array $productDiscountsByPromoAndLine = [];

    /** @var list<Promo> */
    private array $promos;

    /** @param list<Promo> $availablePromos */
    public function __construct(Order $order, array $availablePromos, ApplicableDiscountFactory $factory, private TraderConfig $config)
    {
        // Keep equally priced combinations deterministic regardless of the input order.
        usort($availablePromos, fn (Promo $a, Promo $b): int => strcmp($a->promoId->get(), $b->promoId->get()));

        foreach ($availablePromos as $promo) {
            $this->discountsByPromo[$promo->promoId->get()] = $factory->forPromo($promo);
        }

        $this->promos = array_values(array_filter($availablePromos, function (Promo $promo) use ($order): bool {
            // A discount may become applicable after another promo changes the order.
            foreach ($this->discountsFor($promo) as $discount) {
                if ($discount->couldApply($order)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /** @return list<Promo> */
    public function promos(): array
    {
        return $this->promos;
    }

    /** @return list<ApplicableDiscount> */
    public function discountsFor(Promo $promo): array
    {
        return $this->discountsByPromo[$promo->promoId->get()];
    }

    /** Build this index only if the unconditional-order shortcut did not apply. */
    public function indexProductDiscounts(Order $order): void
    {
        foreach ($this->promos as $promo) {
            $promoId = $promo->promoId->get();

            foreach ($order->getLines() as $line) {
                $lineId = $line->getDiscountableId()->get();

                foreach ($this->discountsFor($promo) as $discount) {
                    if (! $discount instanceof ProductDiscount || ! $discount->isApplicable($order, $line)) {
                        continue;
                    }

                    $discount->setCalculationTaxMode($this->config->getItemDiscountTaxMode());
                    $this->productDiscountsByPromoAndLine[$promoId][$lineId][] = $discount;
                }
            }
        }
    }

    /** @return array<string, list<ProductDiscount>> */
    public function productDiscountsFor(Promo $promo): array
    {
        return $this->productDiscountsByPromoAndLine[$promo->promoId->get()] ?? [];
    }

    /**
     * Return every originally applicable product discount of the selected promos.
     * The caller must resolve competing discounts on each line before applying them.
     *
     * @param  list<Promo>  $selected
     * @return array<string, list<ProductDiscount>>
     */
    public function productDiscountsForSelection(Order $order, array $selected): array
    {
        $choices = [];

        foreach ($order->getLines() as $line) {
            $lineId = $line->getDiscountableId()->get();
            $choices[$lineId] = [];

            foreach ($selected as $promo) {
                foreach ($this->productDiscountsFor($promo)[$lineId] ?? [] as $discount) {
                    $choices[$lineId][] = $discount;
                }
            }
        }

        return $choices;
    }
}
