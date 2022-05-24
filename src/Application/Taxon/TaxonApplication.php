<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon;

use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;

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
        $taxonKey = $this->taxonRepository->uniqueKeyReference($createTaxon->getTaxonKey());

        $taxon = Taxon::create(
            $taxonId,
            $createTaxon->getTaxonKey(),
            $createTaxon->getParentTaxonId()
        );

        $taxon->addData($createTaxon->getData());

        $this->taxonRepository->save($taxon);

        $this->eventDispatcher->dispatchAll($taxon->releaseEvents());

        return $taxonId;
    }

    public function moveTaxon(MoveTaxon $moveTaxon): void
    {

    }

    public function deleteTaxon(DeleteTaxon $deleteTaxon): void
    {

    }
}
