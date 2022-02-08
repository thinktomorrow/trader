<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Notes;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Common\Domain\Locale;

class DefaultNoteCollection extends Collection implements NoteCollection
{
    public function render(Locale $localeId, array $tags = [], $glue = ''): string
    {
        // TODO: avoid rendering here... Allow for rendering in views...

        $renderedItems = array_map(function (Note $note) use ($localeId, $tags) {
            return $note->render($localeId, $tags);
        });

        return implode($glue, $renderedItems);
    }

    public function getAllNotes(Locale $locale, array $tags = [], string $glue = ''): string
    {
        // TODO: avoid rendering here... Allow for rendering in views...
    }
}
