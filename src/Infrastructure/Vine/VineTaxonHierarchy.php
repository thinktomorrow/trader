<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Queries\TaxonHierarchy;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;

final class VineTaxonHierarchy implements TaxonHierarchy
{
    public function __construct(private TaxonTreeRepository $taxonTreeRepository) {}

    public function descendants(TaxonNode $taxon, bool $includeSelf = false): array
    {
        $descendants = $taxon->getChildNodes()->flatten()->all();

        return $includeSelf ? [$taxon, ...$descendants] : $descendants;
    }

    public function ancestors(TaxonNode $taxon): array
    {
        return $taxon->getAncestorNodes()->all();
    }

    public function isAncestorOf(TaxonNode $ancestor, TaxonNode $descendant): bool
    {
        foreach ($this->ancestors($descendant) as $ancestorNode) {
            if ($ancestorNode->getId() === $ancestor->getId()) {
                return true;
            }
        }

        return false;
    }

    public function expandWithDescendants(array $taxonIds): array
    {
        $tree = $this->taxonTreeRepository->getTree();
        $expandedTaxonIds = [];

        foreach (array_unique($taxonIds) as $taxonId) {
            /** @var ?TaxonNode $taxon */
            $taxon = $tree->find(fn (TaxonNode $node): bool => $node->getId() === $taxonId);

            if (! $taxon) {
                continue;
            }

            foreach ($this->descendants($taxon, true) as $descendant) {
                $expandedTaxonIds[] = $descendant->getId();
            }
        }

        return array_values(array_unique($expandedTaxonIds));
    }

    public function containsAny(array $configuredTaxonIds, array $assignedTaxonIds): bool
    {
        return array_intersect($this->expandWithDescendants($configuredTaxonIds), $assignedTaxonIds) !== [];
    }
}
