<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\TaxonSelect;

interface TaxonIdOptionsComposer
{
    public function getTaxaAsOptions(string $taxonomyId): array;

    public function getTaxaAsOptionsForMultiselect(string $taxonomyId): array;

    public function excludeTaxa(array|string $excludeTaxonIds): static;
}
