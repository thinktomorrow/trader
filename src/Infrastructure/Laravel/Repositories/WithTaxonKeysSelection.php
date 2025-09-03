<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;

trait WithTaxonKeysSelection
{
    protected function composeTaxonKeysSelect(): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "trader_taxa_keys.key || '::::' || trader_taxa_keys.locale";
        }

        return "CONCAT(
            trader_taxa_keys.key, '::::',trader_taxa_keys.locale
        )";
    }

    /**
     * @return TaxonKey[]
     */
    protected function extractTaxonKeys(array $state): array
    {
        if (empty($state['taxon_keys'])) {
            return [];
        }

        $pairs = [];

        foreach (explode(',', $state['taxon_keys']) as $pair) {
            [$key, $locale] = explode('::::', $pair);

            $pairs[] = TaxonKey::create(TaxonId::fromString($state['taxon_id']), TaxonKeyId::fromString($key), Locale::fromString($locale));
        }

        // Sort by locale
        usort($pairs, fn (TaxonKey $a, TaxonKey $b) => $a->taxonKeyId->get() <=> $b->taxonKeyId->get());

        return $pairs;
    }
}
