<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Filter;

use Thinktomorrow\Vine\DefaultNode;

class TaxonFilter extends DefaultNode
{
    private string $id;
    private string $key;
    private string $label;
    private bool $showOnline;
    private array $data;

    public function __construct(string $id, string $key, string $label, bool $showOnline, array $data)
    {
        $this->id = $id;
        $this->key = $key;
        $this->label = $label;
        $this->showOnline = $showOnline;
        $this->data = $data;

        // Add the data[order_column] to the node entry so we can use it for sorting.
        parent::__construct($data);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function showOnline(): bool
    {
        return $this->showOnline;
    }

    public function getNodeId($key = null, $default = null): string
    {
        return $this->id;
    }

    public function getParentNodeId(): ?string
    {
        return $this->data('parent_id');
    }

    private function data(string $key, $default = null)
    {
        // TODO: remove container here, put it in repository and pass context to this object instead.
        $language = '';

        // TODO: allow for dotted content... ... how to use Laravel here?

        // First we search for localized content
//        return Arr::get(
//            $this->data,
//            $key . '.' . $language,
//            Arr::get($this->data, $key, $default)
//        );
    }
}
