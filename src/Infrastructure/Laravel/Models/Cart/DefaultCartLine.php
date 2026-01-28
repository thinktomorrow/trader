<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
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

            if ($taxon['class_type'] !== VariantTaxonItem::class) continue;
            if ($taxon['taxonomy_type'] !== TaxonomyType::variant_property->value) continue;

            $result[] = [
                'label' => data_get($taxon, 'data.taxonomy_data.title.' . app()->getLocale()) ?? null,
                'value' => data_get($taxon, 'data.taxon_data.title.' . app()->getLocale()) ?? null,
            ];
        }

        return $result;
    }
}
