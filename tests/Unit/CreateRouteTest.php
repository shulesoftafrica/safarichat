<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * createRoute() picks the controller for the wildcard route. It has to read the path the same way
 * whether the app is served from a domain root or from a sub-folder.
 */
class CreateRouteTest extends TestCase
{
    private function routeFor(string $uri): ?string
    {
        $_SERVER['REQUEST_URI'] = $uri;

        return createRoute();
    }

    public function test_domain_root_paths_map_to_the_first_segment(): void
    {
        $this->assertSame('Home@settings', $this->routeFor('/home/settings'));
        $this->assertSame('Home@settings', $this->routeFor('/home/settings?tab=billing'));
        $this->assertSame('Home@index', $this->routeFor('/'));
        $this->assertSame('Home@index', $this->routeFor('/home'));
    }

    public function test_sub_folder_paths_still_work(): void
    {
        $this->assertSame('Home@settings', $this->routeFor('/safarichat/home/settings'));
    }

    public function test_api_and_asset_paths_are_left_to_other_routes(): void
    {
        $this->assertNull($this->routeFor('/api/products'));
        $this->assertNull($this->routeFor('/public/css/app.css'));
        $this->assertNull($this->routeFor('/storage/uploads/a.png'));
    }
}
