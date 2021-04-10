<?php

namespace Thinktomorrow\Trader\Tests\Integration\Base\Notes;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Integration\Base\Common\Notes\BaseNote;

class BaseNoteTest extends TestCase
{
    /** @test */
    public function it_can_tag_a_note()
    {
        $note = new BaseNote();
    }

    /** @test */
    public function it_can_render_a_note()
    {

    }

}
