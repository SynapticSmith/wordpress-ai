<?php

namespace WordPress\AI\Tests\Abilities\Site_Agent;

use PHPUnit\Framework\TestCase;
use WordPress\AI\Abilities\Site_Agent\Site_Agent;

class Site_AgentTest extends TestCase {

    public function test_get_id() {
        $this->assertEquals('site_agent', Site_Agent::get_id());
    }

    public function test_get_name() {
        $this->assertNotEmpty(Site_Agent::get_name());
    }

    public function test_get_description() {
        $this->assertNotEmpty(Site_Agent::get_description());
    }
}
