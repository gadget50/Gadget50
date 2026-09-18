<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_is_reachable(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_logout_requires_post(): void
    {
        $this->get(route('logout'))->assertStatus(405);
    }
}
