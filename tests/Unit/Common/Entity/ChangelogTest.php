<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Entity;

final class ChangelogTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function it_can_record_version_notes()
    {
        $object = new RecordsChangelogStub();

        $this->assertEquals([], $object->getPreviousChangelog());
    }

    /** @test */
    public function it_can_retrieve_changelog_of_previous_version()
    {
        $object = new RecordsChangelogStub();

        $object->recordInChangelog('xxx');
        $object->incrementChangelogVersion();

        $this->assertEquals(['xxx'], $object->getPreviousChangelog());
    }
}
