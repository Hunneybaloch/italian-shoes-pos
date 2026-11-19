<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_the_login_page(): void
    {
        $this->get('/')
            ->assertRedirect('/login');
    }

    public function test_login_page_returns_a_successful_response(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Italian Shoes');
    }
}
