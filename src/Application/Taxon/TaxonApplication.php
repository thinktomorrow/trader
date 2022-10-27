<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon;

use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Taxon\Events\TaxonDeleted;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\TraderConfig;

final class TaxonApplication
{
    private TraderConfig $traderConfig;
    private EventDispatcher $eventDispatcher;
    private TaxonRepository $taxonRepository;

    public function __construct(TraderConfig $traderConfig, EventDispatcher $eventDispatcher, TaxonRepository $taxonRepository)
    {
        $this->traderConfig = $traderConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->taxonRepository = $taxonRepository;
    }

    public function createTaxon(CreateTaxon $createTaxon): TaxonId
    {
        $taxonId = $this->taxonRepository->nextReference();
        $taxonKeyId = $this->taxonRepository->uniqueKeyReference($createTaxon->getTaxonKeyId(), $taxonId);

        $taxon = Taxon::create(
            $taxonId,
            $createTaxon->getParentTaxonId()
        );

        $taxon->updateTaxonKeys([
            TaxonKey::create($taxon->taxonId, $taxonKeyId, $createTaxon->getTaxonKeyLocale())
        ]);

        $taxon->addData($createTaxon->getData());

        $this->taxonRepository->save($taxon);

        $this->eventDispatcher->dispatchAll($taxon->releaseEvents());

        return $taxonId;
    }

    public function updateTaxonKeys(UpdateTaxonKeys $command): void
    {
        $taxon = $this->taxonRepository->find($command->getTaxonId());

        $taxon->updateTaxonKeys($command->getTaxonKeys());

        $this->taxonRepository->save($taxon);

        $this->eventDispatcher->dispatchAll($taxon->releaseEvents());
    }

    public function moveTaxon(MoveTaxon $moveTaxon): void
    {
        $taxon = $this->taxonRepository->find($moveTaxon->getTaxonId());

        if ($moveTaxon->hasParentTaxonId()) {
            $taxon->changeParent($moveTaxon->getParentTaxonId());
        } else {
            $taxon->moveToRoot();
        }

        $this->taxonRepository->save($taxon);

        $this->eventDispatcher->dispatchAll($taxon->releaseEvents());
    }

    public function deleteTaxon(DeleteTaxon $deleteTaxon): void
    {
        $taxon = $this->taxonRepository->find($deleteTaxon->getTaxonId());

        $childTaxa = $this->taxonRepository->getByParentId($taxon->taxonId);

        // Move direct children to either the above parent or the root
        foreach ($childTaxa as $childTaxon) {
            $this->moveTaxon(new MoveTaxon($childTaxon->taxonId->get(), $taxon->getParentId()?->get()));
        }

        $this->taxonRepository->delete($deleteTaxon->getTaxonId());

        $this->eventDispatcher->dispatchAll([
            new TaxonDeleted($deleteTaxon->getTaxonId()),
        ]);
    }
}
