<?php

namespace Tests\Feature;

use Tests\TestCase;

class MainPageTest extends TestCase
{
    public function test_guest_is_redirected_to_login_from_main_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
