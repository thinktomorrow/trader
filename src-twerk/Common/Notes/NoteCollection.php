<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Notes;

use Countable;
use IteratorAggregate;
use Thinktomorrow\Trader\Common\Domain\Locale;

interface NoteCollection extends Countable, IteratorAggregate
{
    public function getAllNotes(Locale $locale, array $tags = [], string $glue = ''): string;
}
