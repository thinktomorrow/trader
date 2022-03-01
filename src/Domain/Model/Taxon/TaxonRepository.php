<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon;

interface TaxonRepository
{
    public function save(Taxon $taxon): void;

    public function find(TaxonId $taxonId): Taxon;

    public function delete(TaxonId $taxonId): void;

    public function nextReference(): TaxonId;
}
