<?php

namespace Tests\Unit;

use Tests\TestCase;

class VersionTest extends TestCase
{
    public function testVersion()
    {
        $this->assertNotEmpty(app_version());

        $this->assertStringContainsString('-', app_version());

        $parts = explode('-', app_version());

        $this->assertCount(2, $parts);

        $this->assertTrue(ctype_xdigit($parts[1]));
    }
}
