<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;

final readonly class PromoSuggestion
{
    public function __construct(
        public string $promoId,
        public string $discountId,
        public string $discountKey,
        public ?string $pdpDescription,
        public ?string $productBadgeText,
        public ?string $cartDescription,
    ) {}

    public static function fromPromo(Promo $promo, Discount $discount, string $locale): self
    {
        return new self(
            $promo->promoId->get(),
            $discount->getDiscountId()->get(),
            $discount::getMapKey(),
            self::localizedText($promo, 'pdp_description', $locale),
            self::localizedText($promo, 'product_badge_text', $locale),
            self::localizedText($promo, 'cart_description', $locale),
        );
    }

    private static function localizedText(Promo $promo, string $key, string $locale): ?string
    {
        $value = $promo->getData($key);
        $value = is_array($value) ? ($value[$locale] ?? $value[explode('_', $locale)[0]] ?? null) : $value;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
