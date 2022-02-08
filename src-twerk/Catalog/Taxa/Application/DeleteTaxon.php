<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Taxa\Application;

use Thinktomorrow\Trader\Catalog\Products\Domain\Events\TaxonQueuedForDeletion;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\TaxonRepository;

class DeleteTaxon
{
    private TaxonRepository $taxonRepository;

    public function __construct(TaxonRepository $taxonRepository)
    {
        $this->taxonRepository = $taxonRepository;
    }

    public function onTaxonQueuedForDeletion(TaxonQueuedForDeletion $event): void
    {
        $taxon = $this->taxonRepository->findById($event->taxonId);

        if ($taxon->hasChildNodes()) {
            return;
        }

        $this->taxonRepository->delete($taxon);
    }
}
