<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilter;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilters;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;

final class InMemoryTaxonRepository implements TaxonRepository, TaxonTreeRepository
{
    private static array $taxons = [];

    // Lookup of 'connected' product-taxon ids
    private static array $productIds = [];

    private string $nextReference = 'ccc-123';

    public function save(Taxon $taxon): void
    {
        static::$taxons[$taxon->taxonId->get()] = $taxon;
    }

    public function find(TaxonId $taxonId): Taxon
    {
        if (!isset(static::$taxons[$taxonId->get()])) {
            throw new CouldNotFindTaxon('No taxon found by id ' . $taxonId);
        }

        return static::$taxons[$taxonId->get()];
    }

    public function delete(TaxonId $taxonId): void
    {
        if (!isset(static::$taxons[$taxonId->get()])) {
            throw new CouldNotFindTaxon('No taxon found by id ' . $taxonId);
        }

        unset(static::$taxons[$taxonId->get()]);
    }

    public function nextReference(): TaxonId
    {
        return TaxonId::fromString($this->nextReference);
    }

    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function setProductIds(TaxonId $taxonId, array $productIds): void
    {
        static::$productIds[$taxonId->get()] = $productIds;
    }

    public function clear()
    {
        static::$taxons = [];
    }

    public function getAllTaxonFilters(): TaxonFilters
    {
        $result = [];

        /** @var Taxon $taxon */
        foreach (static::$taxons as $taxon) {
            $result[] = TaxonFilter::fromMappedData([
                'taxon_id'    => $taxon->taxonId->get(),
                'parent_id'   => $taxon->getMappedData()['parent_id'],
                'key'         => $taxon->getMappedData()['key'],
                'data'        => json_encode([
                    'label' => ucfirst($taxon->getMappedData()['key']),
                ]),
                'state'       => $taxon->getMappedData()['state'],
                'order'       => $taxon->getMappedData()['order'],
                'product_ids' => $this->getCommaSeparatedProductIds($taxon->taxonId),
            ]);
        }

        return TaxonFilters::fromType($result);
    }

    private function getCommaSeparatedProductIds(TaxonId $taxonId): string
    {
        if (!isset(static::$productIds[$taxonId->get()])) {
            return '';
        }

        return implode(',', static::$productIds[$taxonId->get()]);
    }
}
