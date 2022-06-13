<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Vine\Node;
use Thinktomorrow\Vine\Source;

class TaxonSource implements Source
{
    private iterable $records;
    public string $sortChildrenBy = 'order';

    public function __construct(iterable $records)
    {
        $this->records = $records;
    }

    public function nodeEntries(): iterable
    {
        return $this->records;
    }

    public function createNode($entry): Node
    {
        if (! $entry instanceof TaxonNode) {
            throw new \InvalidArgumentException('Entry is expected to be a ' . TaxonNode::class .'.');
        }

        return $entry;
    }
}
