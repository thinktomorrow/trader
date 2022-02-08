<?php declare(strict_types=1);

namespace Base\Common\Notes;

use Common\Collection;
use Common\Notes\NoteCollection;
use Common\Domain\Locales\LocaleId;

class BaseNoteCollection extends Collection implements NoteCollection
{
    public function render(LocaleId $localeId, array $tags = [], $glue = ''): string
    {
        $renderedItems = array_map(function(Note $note) use($localeId, $tags){
            return $note->render($localeId, $tags);
        });

        return implode($glue, $renderedItems);
    }
}
