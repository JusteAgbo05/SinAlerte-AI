<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_home_page_loads(): void
    {
        $this->withoutVite();

        $this->get('/')->assertStatus(200);
    }
}
