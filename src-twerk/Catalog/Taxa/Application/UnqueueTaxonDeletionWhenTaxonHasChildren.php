<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Taxa\Application;

use Thinktomorrow\Trader\Catalog\Products\Domain\Events\TaxonQueuedForDeletion;
use App\ShopAdmin\Catalog\Taxa\TaxonState;
use App\ShopAdmin\Catalog\Taxa\TaxonStateMachine;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\TaxonRepository;
use Thinktomorrow\Trader\Catalog\Taxa\Ports\TaxonModel;

class UnqueueTaxonDeletionWhenTaxonHasChildren
{
    private TaxonRepository $taxonRepository;

    public function __construct(TaxonRepository $taxonRepository)
    {
        $this->taxonRepository = $taxonRepository;
    }

    public function onTaxonQueuedForDeletion(TaxonQueuedForDeletion $event): void
    {
        $taxon = $this->taxonRepository->findById($event->taxonId);

        if (! $taxon->hasChildNodes()) {
            return;
        }

        $stateMachine = new TaxonStateMachine(TaxonState::$KEY);
        $stateMachine->apply($model = TaxonModel::find($taxon->getId()), 'restore');
        $model->save();
    }
}
