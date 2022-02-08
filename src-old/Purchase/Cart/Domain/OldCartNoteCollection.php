<?php declare(strict_types=1);

namespace Purchase\Cart\Domain;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartNote;

class OldCartNoteCollection extends Collection
{
    public function render($tags = [], string $locale = null): string
    {
        if(!is_array($tags)) $tags = [$tags];

        return $this->map(function(CartNote $note) use($tags, $locale){
            return $note->render($tags, $locale);
        })->implode('');
    }
}
