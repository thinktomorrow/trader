<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Notes\Domain;

use Countable;
use IteratorAggregate;

interface NoteCollection extends Countable, IteratorAggregate
{
    public function render(string $locale, $tags = [], $glue = ''): string;
}
