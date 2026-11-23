<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Illuminate\Support\Collection;

final readonly class PromoSuggestions
{
    /**
     * @param  Collection<int, PromoSuggestion>  $applicablePromos
     * @param  Collection<int, PromoSuggestion>  $conditionalPromos
     */
    public function __construct(public Collection $applicablePromos, public Collection $conditionalPromos) {}
}
