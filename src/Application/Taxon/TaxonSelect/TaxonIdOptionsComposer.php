<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\TaxonSelect;

interface TaxonIdOptionsComposer
{
    public function getOptions(): array;

    public function getRoots(): array;

    public function getOptionsForMultiselect(): array;

    public function exclude(array|string $excludeTaxonIds): static;

    public function include(array|string $includeTaxonRootIds): static;

    public function includeRoots(bool $includeRoots = true): static;
}
