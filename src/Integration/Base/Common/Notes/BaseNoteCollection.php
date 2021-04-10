<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Integration\Base\Common\Notes;

use \ArrayIterator;
use Thinktomorrow\Trader\Common\Collection;
use Thinktomorrow\Trader\Common\Notes\NoteCollection;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;

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
