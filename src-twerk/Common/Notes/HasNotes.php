<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Notes;

interface HasNotes
{
    public function notes(): NoteCollection;

    public function addNote(Note $note);
}
