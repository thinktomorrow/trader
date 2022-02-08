<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Taxa\Application;

use Illuminate\Contracts\Events\Dispatcher;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\Events\TaxonCreated;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\Taxon;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\TaxonRepository;

class CreateTaxon
{
    private TaxonRepository $taxonRepository;
    private Dispatcher $eventDispatcher;

    public function __construct(TaxonRepository $taxonRepository, Dispatcher $eventDispatcher)
    {
        $this->taxonRepository = $taxonRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle(string $key, Taxon $parent, array $data): Taxon
    {
        $taxon = $this->taxonRepository->create([
            'key' => $key,
            'data' => $data,
        ], $parent);

        $this->eventDispatcher->dispatch(new TaxonCreated($taxon->getId()));

        return $taxon;
    }
}
