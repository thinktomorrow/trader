<?php
declare(strict_types=1);

namespace Common\Notes;

interface CarriesNotes
{
    public function notes(): NoteCollection;

    public function addNote(Note $note);
}
