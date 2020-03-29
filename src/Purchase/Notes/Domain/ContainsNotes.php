<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Notes\Domain;

interface ContainsNotes
{
    public function notes(): NoteCollection;

    public function addNote(Note $note);
}
