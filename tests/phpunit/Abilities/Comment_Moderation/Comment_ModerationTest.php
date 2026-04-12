<?php

namespace WordPress\AI\Tests\Abilities\Comment_Moderation;

use PHPUnit\Framework\TestCase;
use WordPress\AI\Abilities\Comment_Moderation\Comment_Moderation;

class Comment_ModerationTest extends TestCase {

    public function test_get_id() {
        $this->assertEquals('comment_moderation', Comment_Moderation::get_id());
    }

    public function test_get_name() {
        $this->assertNotEmpty(Comment_Moderation::get_name());
    }

    public function test_get_description() {
        $this->assertNotEmpty(Comment_Moderation::get_description());
    }
}
