<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Vine\NodeCollectionFactory;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNodes;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Application\Taxon\Category\Category;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;

class MysqlTaxonRepository implements TaxonRepository, TaxonTreeRepository
{
    private static $taxonTable = 'trader_taxa';

    public function save(Taxon $taxon): void
    {
        $state = $taxon->getMappedData();

        if (!$this->exists($taxon->taxonId)) {
            DB::table(static::$taxonTable)->insert($state);
        } else {
            DB::table(static::$taxonTable)->where('taxon_id', $taxon->taxonId)->update($state);
        }
    }

    private function exists(TaxonId $taxonId): bool
    {
        return DB::table(static::$taxonTable)->where('taxon_id', $taxonId->get())->exists();
    }

    public function find(TaxonId $taxonId): Taxon
    {
        $taxonState = DB::table(static::$taxonTable)
            ->where(static::$taxonTable . '.taxon_id', $taxonId->get())
            ->first();

        if (!$taxonState) {
            throw new CouldNotFindTaxon('No taxon found by id [' . $taxonId->get() . ']');
        }

        return Taxon::fromMappedData((array) $taxonState, []);
    }

    public function delete(TaxonId $taxonId): void
    {
        DB::table(static::$taxonTable)->where('taxon_id', $taxonId->get())->delete();
    }

    public function nextReference(): TaxonId
    {
        return TaxonId::fromString((string) Uuid::uuid4());
    }

//    public function getAllTaxonFilters(): TaxonFilters
//    {
//        $results = DB::table(static::$taxonTable)
//            ->leftJoin('trader_taxa_products', 'trader_taxa.taxon_id', 'trader_taxa_products.taxon_id')
//            ->select(static::$taxonTable .'.*', DB::raw('GROUP_CONCAT(product_id) AS product_ids'))
//            ->groupBy(static::$taxonTable.'.taxon_id')
//            ->get();
//
//        return TaxonFilters:: fromType(
//            $results
//                ->map(fn($row) => TaxonFilter::fromMappedData((array) $row))
//                ->toArray()
//        );
//    }

    public function findByKey(string $key): Category
    {
        // TODO: Implement findByKey() method.
    }

    public function getAllTaxonNodes(): TaxonNodes
    {
        // Duplicate of getAllTaxonFilters
        $results = DB::table(static::$taxonTable)
            ->leftJoin('trader_taxa_products', 'trader_taxa.taxon_id', 'trader_taxa_products.taxon_id')
            ->select(static::$taxonTable .'.*', DB::raw('GROUP_CONCAT(product_id) AS product_ids'))
            ->groupBy(static::$taxonTable.'.taxon_id')
            ->orderBy(static::$taxonTable.'.order')
            ->get();

        return TaxonNodes::fromType(
            $results->map(fn($row) => TaxonNode::fromMappedData((array) $row))->all()
        );
    }

//    private function composeTaxon($model, ?TaxonNode $parent = null): TaxonNode
//    {
//        return new TaxonNode((string)$model->id, $model->key, $model->label, $parent, [
//            'id' => $model->id,
//            'key' => $model->key,
//            'label' => $model->label,
//            'parent_id' => (string)$model->parent_id,
//            'order_column' => (int) $model->order_column ?? 0,
//            'productgroup_ids' => $model->productgroup_ids ? explode(',', $model->productgroup_ids) : [],
//            'images' => $model->assets('thumb'),
//            'state' => $model->getState(TaxonState::$KEY),
//            // Other stuff such as locale alternatives for the label, urls, breadcrumbs, ....
//        ]);
//    }
}
