<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxonomy;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Events\TaxonomyDeleted;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;
use Thinktomorrow\Trader\TraderConfig;

final class TaxonomyApplication
{
    private TraderConfig $traderConfig;
    private EventDispatcher $eventDispatcher;
    private TaxonomyRepository $taxonomyRepository;

    public function __construct(TraderConfig $traderConfig, EventDispatcher $eventDispatcher, TaxonomyRepository $taxonomyRepository)
    {
        $this->traderConfig = $traderConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    public function createTaxonomy(CreateTaxonomy $createTaxonomy): TaxonomyId
    {
        $taxonomyId = $this->taxonomyRepository->nextReference();

        $taxonomy = Taxonomy::create(
            $taxonomyId,
            $createTaxonomy->getTaxonomyType(),
        );

        $taxonomy->showAsGridFilter($createTaxonomy->showsAsGridFilter());
        $taxonomy->showInGrid($createTaxonomy->showsOnListing());
        $taxonomy->allowMultipleValues($createTaxonomy->allowsMultipleValues());
        $taxonomy->addData($createTaxonomy->getData());

        $this->taxonomyRepository->save($taxonomy);

        $this->eventDispatcher->dispatchAll($taxonomy->releaseEvents());

        return $taxonomyId;
    }

    public function deleteTaxonomy(DeleteTaxonomy $deleteTaxonomy): void
    {
        $this->taxonomyRepository->delete($deleteTaxonomy->getTaxonomyId());

        $this->eventDispatcher->dispatchAll([
            new TaxonomyDeleted($deleteTaxonomy->getTaxonomyId()),
        ]);
    }
}
