<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_requires_current_password_and_invalidates_old_session_hash(): void
    {
        $user = User::factory()->create();
        $oldHash = $user->password;
        $this->actingAs($user)->put('/settings/password', ['current_password' => 'wrong', 'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password'])->assertSessionHasErrors('current_password');
        $this->put('/settings/password', ['current_password' => 'password', 'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password'])->assertRedirect('/settings');
        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->get('/settings')->assertOk();
        $this->withSession(['password_hash_web' => $oldHash])->get('/settings')->assertRedirect('/login');
    }

    public function test_user_login_does_not_redirect_into_the_admin_guard(): void
    {
        $user = User::factory()->create();
        $this->withSession(['url.intended' => 'http://localhost/admin/tasks'])->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/studio');
        $this->post('/logout');
        $this->withSession(['url.intended' => url('/works')])->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(url('/works'));
    }

    public function test_disabled_user_cannot_login_or_use_existing_session(): void
    {
        $user = User::factory()->create(['active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->actingAs($user)->get('/studio')->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_profile_update_does_not_allow_role_or_status_mass_assignment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/settings', ['name' => 'Updated', 'active' => false, 'role' => 'superadmin'])->assertRedirect('/settings');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated', 'active' => true]);
    }

    public function test_only_superadmin_can_change_user_status_and_admin_roles_with_audit(): void
    {
        $super = AdminUser::create(['name' => 'Super', 'email' => 'super@example.test', 'password' => 'secure-long-password', 'role' => 'superadmin', 'active' => true]);
        $staff = AdminUser::create(['name' => 'Staff', 'email' => 'staff@example.test', 'password' => 'secure-long-password', 'role' => 'support', 'active' => true]);
        $user = User::factory()->create();
        $this->actingAs($staff, 'admin')->put('/admin/users/'.$user->id.'/status', ['active' => false, 'reason' => 'Policy'])->assertForbidden();
        $this->actingAs($super, 'admin')->put('/admin/users/'.$user->id.'/status', ['active' => false, 'reason' => 'Policy'])->assertRedirect();
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'user.disabled', 'subject' => (string) $user->id]);
        $this->put('/admin/accounts/'.$staff->id, ['role' => 'operations', 'active' => true, 'reason' => 'Role change'])->assertRedirect();
        $this->assertDatabaseHas('admin_users', ['id' => $staff->id, 'role' => 'operations']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'admin.updated', 'subject' => (string) $staff->id]);
        $this->put('/admin/accounts/'.$super->id, ['role' => 'support', 'active' => false, 'reason' => 'Self disable'])->assertSessionHasErrors('account');
        $this->assertDatabaseHas('admin_users', ['id' => $super->id, 'role' => 'superadmin', 'active' => true]);
    }
}
