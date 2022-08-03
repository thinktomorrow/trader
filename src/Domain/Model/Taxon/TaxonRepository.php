<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon;

interface TaxonRepository
{
    public function save(Taxon $taxon): void;

    public function find(TaxonId $taxonId): Taxon;

    public function findByKey(TaxonKey $taxonKey): Taxon;

    /** @return Taxon[] */
    public function getByParentId(TaxonId $taxonId): array;

    public function delete(TaxonId $taxonId): void;

    public function nextReference(): TaxonId;

    public function uniqueKeyReference(TaxonKey $taxonKey, TaxonId $allowedTaxonId): TaxonKey;
}
