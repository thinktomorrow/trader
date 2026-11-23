<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

final class FindAppliedPromoBadgesForCartLine
{
    public function __construct(private PromoRepository $promoRepository) {}

    /** @return Collection<int, PromoSuggestion> */
    public function get(CartLine $line, string $locale): Collection
    {
        $appliedIds = collect($line->getDiscounts())
            ->map(fn ($discount): ?string => $discount->getData('promo_discount_id'))
            ->filter()
            ->all();

        if ($appliedIds === []) {
            return collect();
        }

        $badges = collect();

        foreach ($this->promoRepository->getAvailablePromos() as $promo) {
            if (! $promo->getData('show_on_cart_line', false)) {
                continue;
            }

            foreach ($promo->getDiscounts() as $discount) {
                if (in_array($discount->getDiscountId()->get(), $appliedIds, true)) {
                    $badges->push(PromoSuggestion::fromPromo($promo, $discount, $locale));
                }
            }
        }

        return $badges;
    }
}
