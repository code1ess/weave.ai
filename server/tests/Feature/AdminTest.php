<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login_does_not_grant_admin_access(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_login_is_separate_and_audited(): void
    {
        $admin = AdminUser::create(['name' => 'Operator', 'email' => 'ops@example.test', 'password' => 'long-secure-password', 'role' => 'operations', 'active' => true]);
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'long-secure-password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
        $this->assertDatabaseHas('admin_audit_logs', ['admin_user_id' => $admin->id, 'action' => 'admin.login']);
        $this->get('/admin')->assertOk();
    }

    public function test_inertia_login_navigates_to_dashboard_and_invalid_login_stays_on_form(): void
    {
        $admin = AdminUser::create(['name' => 'Operator', 'email' => 'ops@example.test', 'password' => 'long-secure-password', 'role' => 'operations', 'active' => true]);

        $this->from('/admin/login')->withHeader('X-Inertia', 'true')
            ->post('/admin/login', ['email' => $admin->email, 'password' => 'incorrect-password'])
            ->assertRedirect('/admin/login')->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->withHeader('X-Inertia', 'true')
            ->post('/admin/login', ['email' => $admin->email, 'password' => 'long-secure-password'])
            ->assertConflict()->assertHeader('X-Inertia-Location', url('/admin'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_disabled_admin_and_invalid_role_cannot_access_console(): void
    {
        $admin = AdminUser::create(['name' => 'Disabled', 'email' => 'disabled@example.test', 'password' => 'long-secure-password', 'role' => 'operations', 'active' => false]);
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'long-secure-password'])->assertSessionHasErrors('email');
        $this->actingAs($admin, 'admin')->get('/admin')->assertForbidden();
        $admin->update(['active' => true, 'role' => 'unknown']);
        $this->get('/admin')->assertForbidden();
    }

    public function test_support_cannot_access_audit_but_superadmin_can(): void
    {
        $admin = AdminUser::create(['name' => 'Support', 'email' => 'support@example.test', 'password' => 'long-secure-password', 'role' => 'support', 'active' => true]);
        $this->actingAs($admin, 'admin')->get('/admin/audit')->assertForbidden();
        $this->get('/admin/users')->assertOk();
        $admin->update(['role' => 'superadmin']);
        $this->get('/admin/audit')->assertOk();
    }
}
