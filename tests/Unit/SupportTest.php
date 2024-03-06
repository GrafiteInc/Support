<?php

namespace Tests\Unit;

use Tests\TestCase;
use Grafite\Support\Helpers\Stringy;
use Illuminate\Support\Facades\Artisan;

class SupportTest extends TestCase
{
    public function testCloudflarePurge()
    {
        $this->markTestSkipped('This test is skipped because it makes a real request to Cloudflare.');

        // $result = Artisan::call('cloudflare:purge', [
        //     'zone' => '1234567890',
        //     'email' => 'foo',
        //     'key' => 'bar'
        // ]);
    }

    public function testRouteHelper()
    {
        include('src/GlobalHelpers/RouteHelper.php');

        $result = route_link_class('home', 'active', 'nav-link');

        $this->assertEquals('nav-link', $result);
    }

    public function testSessionHelper()
    {
        include('src/GlobalHelpers/SessionHelper.php');

        $result = session_error_message();
        $this->assertEquals('', $result);

        $result = javascript_session_data();
        $this->assertStringContainsString('window.app', $result);
    }

    public function testControllerMacros()
    {
        $result = collect([1, 3, 5, 7, 9])->paginate(2);
        $this->assertStringContainsString('LengthAwarePaginator', get_class($result));

        $resultDesc = collect([
            (object) [
                'name' => 'foo',
                'created_at' => '2020-01-01 00:00:00'
            ],
            (object) [
                'name' => 'bar',
                'created_at' => '2010-01-01 00:00:00'
            ],
        ])->sortByDateDesc();

        $resultAsc = collect([
            (object) [
                'name' => 'foo',
                'created_at' => '2020-01-01 00:00:00'
            ],
            (object) [
                'name' => 'bar',
                'created_at' => '2010-01-01 00:00:00'
            ],
        ])->sortByDate();

        $this->assertStringContainsString('bar', $resultAsc->first()->name);
        $this->assertStringContainsString('foo', $resultDesc->first()->name);

        $resultChunk = collect([
            (object) [
                'name' => 'foo',
                'created_at' => '2020-01-01 00:00:00'
            ],
            (object) [
                'name' => 'bar',
                'created_at' => '2010-01-01 00:00:00'
            ],
            (object) [
                'name' => 'baz',
                'created_at' => '2010-01-01 00:00:00'
            ],
            (object) [
                'name' => 'bing',
                'created_at' => '2010-01-01 00:00:00'
            ],
        ])->chunkBy('created_at');

        $this->assertStringContainsString('foo', $resultChunk['2020-01-01 00:00:00']->first()->name);
        $this->assertEquals(3, $resultChunk['2010-01-01 00:00:00']->count());
    }
}
