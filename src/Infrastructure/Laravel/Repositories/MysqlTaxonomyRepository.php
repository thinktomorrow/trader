<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Application\Taxonomy\TaxonomyItem;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Exceptions\CouldNotFindTaxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyState;

class MysqlTaxonomyRepository implements TaxonomyRepository
{
    private static $taxonomyTable = 'trader_taxonomies';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function save(Taxonomy $taxonomy): void
    {
        $state = $taxonomy->getMappedData();

        if (! $this->exists($taxonomy->taxonomyId)) {
            DB::table(static::$taxonomyTable)->insert($state);
        } else {
            DB::table(static::$taxonomyTable)->where('taxonomy_id', $taxonomy->taxonomyId->get())->update($state);
        }
    }

    private function exists(TaxonomyId $taxonomyId): bool
    {
        return DB::table(static::$taxonomyTable)->where('taxonomy_id', $taxonomyId->get())->exists();
    }

    public function find(TaxonomyId $taxonomyId): Taxonomy
    {
        $taxonomyState = DB::table(static::$taxonomyTable)
            ->where(static::$taxonomyTable . '.taxonomy_id', $taxonomyId->get())
            ->first();

        if (! $taxonomyState) {
            throw new CouldNotFindTaxonomy('No taxonomy found by id [' . $taxonomyId->get() . ']');
        }

        return Taxonomy::fromMappedData((array)$taxonomyState);
    }

    public function findMany(array $taxonomyIds): array
    {
        $taxonomyStates = DB::table(static::$taxonomyTable)
            ->whereIn('taxonomy_id', $taxonomyIds)
            ->orderBy('order')
            ->get();

        return $taxonomyStates->map(fn ($taxonomyState) => Taxonomy::fromMappedData((array)$taxonomyState))->all();
    }

    public function getForFilter(): array
    {
        $taxonomyStates = DB::table(static::$taxonomyTable)
            ->where('state', TaxonomyState::online->value)
            ->where('shows_as_grid_filter', 1)
            ->orderBy('order')
            ->get();

        $taxonomyItem = $this->container->get(TaxonomyItem::class);

        return $taxonomyStates->map(fn ($state) => $taxonomyItem::fromMappedData((array)$state))->all();
    }

    public function findManyByTaxa(array $taxonIds): array
    {
        $taxonomyStates = DB::table(static::$taxonomyTable)
            ->join('trader_taxa', 'trader_taxonomies.taxonomy_id', '=', 'trader_taxa.taxonomy_id')
            ->whereIn('trader_taxa.taxon_id', $taxonIds)
            ->orderBy(static::$taxonomyTable . '.order')
            ->select('trader_taxonomies.*')
            ->distinct()
            ->get();

        return $taxonomyStates->map(fn ($taxonomyState) => Taxonomy::fromMappedData((array)$taxonomyState))->all();
    }

    public function delete(TaxonomyId $taxonomyId): void
    {
        DB::table(static::$taxonomyTable)->where('taxonomy_id', $taxonomyId->get())->delete();
    }

    public function nextReference(): TaxonomyId
    {
        return TaxonomyId::fromString((string)Uuid::uuid4());
    }
}
