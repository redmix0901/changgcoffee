<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_away_from_admin_campaigns(): void
    {
        $this->get('/admin/campaigns')->assertRedirect('/admin/login');
    }

    public function test_admin_can_log_in_with_the_configured_shared_credentials(): void
    {
        config()->set('admin.username', 'admin');
        config()->set('admin.password', 'secret');

        $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'secret',
        ])->assertRedirect('/admin/campaigns');

        $this->get('/admin/campaigns')->assertOk();
    }
}
