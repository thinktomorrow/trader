<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Taxa\Ports;

use App\ShopAdmin\Catalog\Taxa\TaxonState;
use DB;
use Illuminate\Support\Str;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\Taxon;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\TaxonRepository;
use Thinktomorrow\Trader\Catalog\Taxa\Ports\Vine\TaxonSource;
use Thinktomorrow\Vine\NodeCollection;
use Thinktomorrow\Vine\NodeCollectionFactory;

class DbTaxonRepository implements TaxonRepository
{
    private ?NodeCollection $tree = null;

    private function generateTaxonomyTreeIfNotAlready(): void
    {
        if (! $this->tree) {
            $this->generateTaxonomyTree();
        }
    }

    public function generateTaxonomyTree(): void
    {
        $queryResults = TaxonModel::leftJoin('trader_taxa_products', 'trader_taxa.id', 'trader_taxa_products.taxon_id')
            ->select((new TaxonModel())->getTable() .'.*', DB::raw('GROUP_CONCAT(productgroup_id) AS productgroup_ids'))
            ->groupBy((new TaxonModel())->getTable().'.id')
            ->with('assetRelation')
            ->get();

        $this->tree = (new NodeCollectionFactory)->strict()->fromSource(
            new TaxonSource($queryResults, fn ($model) => $this->composeTaxon($model, null))
        );
    }

    public function findByKey(string $key): ?Taxon
    {
        $this->generateTaxonomyTreeIfNotAlready();

        return $this->tree->find(function (Taxon $taxon) use ($key) {
            return $taxon->getKey() == $key;
        });
    }

    public function findManyByKeys(array $keys): NodeCollection
    {
        $this->generateTaxonomyTreeIfNotAlready();

        return $this->tree->findMany(function (Taxon $taxon) use ($keys) {
            return in_array($taxon->getKey(), $keys);
        });
    }

    public function findById(string $id): ?Taxon
    {
        $this->generateTaxonomyTreeIfNotAlready();

        return $this->tree->find(function (Taxon $taxon) use ($id) {
            return $taxon->getId() == $id;
        });
    }

    public function create(array $values, ?Taxon $parent): Taxon
    {
        $model = TaxonModel::create(array_merge($values, [
            'key' => $this->composeUniqueKey($values['key']),
            'parent_id' => $parent ? $parent->getId() : null,
        ]));

        $this->generateTaxonomyTree();

        return $this->composeTaxon($model, $parent);
    }

    public function save(Taxon $taxon): void
    {
        // TODO: $this->composeUniqueKey($values['key'])
        // TODO: Implement save() method.

        // $this->generateTaxonomyTree();
    }

    public function delete(Taxon $taxon): void
    {
        /** @var Taxon $childNode */
        foreach ($taxon->getChildNodes() as $childNode) {
            if ($taxon->isRootNode()) {
                $this->moveTaxonToRoot($childNode->getId());
            } else {
                $this->moveTaxonToParent($childNode->getId(), $taxon->getParentNode()->getId());
            }
        }

        TaxonModel::find($taxon->getId())->delete();

        $this->generateTaxonomyTree();
    }

    private function moveTaxonToParent(string $taxonId, string $parentTaxonId): void
    {
        TaxonModel::findOrFail($taxonId)->update(['parent_id' => $parentTaxonId]);
    }

    private function moveTaxonToRoot(string $taxonId): void
    {
        TaxonModel::findOrFail($taxonId)->update(['parent_id' => null]);
    }

    public function getRootNodes(): NodeCollection
    {
        $this->generateTaxonomyTreeIfNotAlready();

        return $this->tree->copy();
    }

    private function composeTaxon(TaxonModel $model, ?Taxon $parent = null): Taxon
    {
        return new TaxonNode((string)$model->id, $model->key, $model->label, $parent, [
            'id' => $model->id,
            'key' => $model->key,
            'label' => $model->label,
            'parent_id' => (string)$model->parent_id,
            'order_column' => (int) $model->order_column ?? 0,
            'productgroup_ids' => $model->productgroup_ids ? explode(',', $model->productgroup_ids) : [],
            'images' => $model->assets('thumb'),
            'state' => $model->getState(TaxonState::$KEY),
            // Other stuff such as locale alternatives for the label, urls, breadcrumbs, ....
        ]);
    }

    private function composeUniqueKey(string $key): string
    {
        $originalKey = $key = Str::slug($key);
        $append = 1;

        while (TaxonModel::findByKey($key)) {
            $key = $originalKey . '-' . $append++;
        }

        return $key;
    }
}
