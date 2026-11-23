<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;

final class FindCartPromoIncentives
{
    public function __construct(private FindPromoSuggestionsForProduct $suggestions) {}

    /** @return Collection<int, PromoSuggestion> */
    public function get(Cart $cart, string $locale): Collection
    {
        $incentives = collect();
        $appliedPromoIds = collect($cart->getAllDiscounts())
            ->map(fn ($discount): ?string => $discount->getData('promo_id'))
            ->filter()
            ->all();

        foreach ($cart->getLines() as $line) {
            $reference = $line->getPurchasableReference();

            if (! $reference->isVariant()) {
                continue;
            }

            $incentives = $incentives->concat($this->suggestions->cartIncentivesForProduct(
                PromoPreviewProduct::fromCartLine($line),
                $locale,
                $cart,
            ));
        }

        return $incentives
            ->reject(fn (PromoSuggestion $suggestion): bool => in_array($suggestion->promoId, $appliedPromoIds, true))
            ->unique(fn (PromoSuggestion $suggestion): string => $suggestion->promoId)
            ->values();
    }
}
