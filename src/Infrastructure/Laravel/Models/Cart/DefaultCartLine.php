<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadLine;

class DefaultCartLine extends OrderReadLine implements CartLine
{
    public function getUrl(): string
    {
        return $this->getPurchasableReference()->getId();
    }

    public function getVariants(): array
    {
        $taxa = $this->getData('taxa', []);

        $result = [];

        foreach ($taxa as $taxon) {
            $result[] = [
                'label' => data_get($taxon, 'taxonomy_data.title.' . app()->getLocale()) ?? null,
                'value' => data_get($taxon, 'data.title.' . app()->getLocale()) ?? null,
            ];
        }

        return $result;
    }
}
