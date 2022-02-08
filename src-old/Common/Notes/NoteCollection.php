<?php declare(strict_types=1);

namespace Common\Notes;

use Countable;
use IteratorAggregate;
use Common\Domain\Locales\LocaleId;

interface NoteCollection extends Countable, IteratorAggregate
{
    public function render(LocaleId $localeIdId, array $tags = [], string $glue = ''): string;
}
