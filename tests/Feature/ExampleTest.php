<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_responds_on_the_root_path(): void
    {
        $this->get('/')->assertStatus(200);
    }
}
