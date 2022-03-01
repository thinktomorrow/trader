<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Vine\Node;
use Thinktomorrow\Vine\Source;

class TaxonSource implements Source
{
    private iterable $records;
    private \Closure $createEntry;

    public string $sortChildrenBy = 'order';

    public function __construct(iterable $records, \Closure $createEntry)
    {
        $this->records = $records;
        $this->createEntry = $createEntry;
    }

    public function nodeEntries(): iterable
    {
        return $this->records;
    }

    public function createNode($entry): Node
    {
        return call_user_func($this->createEntry, $entry);
    }
}
