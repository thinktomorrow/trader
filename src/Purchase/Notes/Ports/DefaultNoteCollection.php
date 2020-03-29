<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Notes\Ports;

use ArrayIterator;
use Thinktomorrow\Trader\Purchase\Notes\Domain\NoteCollection;

class DefaultNoteCollection implements NoteCollection
{
    /** @var array */
    private $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function render(string $locale, $tags = [], $glue = ''): string
    {
        if(!is_array($tags)) $tags = [$tags];

        $renderedItems = array_map(function(Note $note) use($locale, $tags){
            return $note->render($locale, $tags);
        });

        return implode($glue, $renderedItems);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function count()
    {
        return count($this->items);
    }
}
