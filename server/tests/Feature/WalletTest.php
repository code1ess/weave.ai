<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    private function finance(): AdminUser
    {
        return AdminUser::create(['name' => 'Finance', 'email' => 'finance@example.test', 'password' => 'secure-long-password', 'role' => 'finance', 'active' => true]);
    }

    public function test_adjustment_is_atomic_audited_and_idempotent(): void
    {
        $user = User::factory()->create();
        $admin = $this->finance();
        $payload = ['user_id' => $user->id, 'amount' => 100, 'reason' => 'Internal testing credits', 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'];
        $this->actingAs($admin, 'admin')->post('/admin/wallet/adjustments', $payload)->assertRedirect();
        $this->post('/admin/wallet/adjustments', $payload)->assertRedirect();
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'available' => 100, 'reserved' => 0]);
        $this->assertDatabaseCount('wallet_ledger', 1);
        $this->assertDatabaseHas('admin_audit_logs', ['admin_user_id' => $admin->id, 'action' => 'wallet.adjusted']);
        $this->post('/admin/wallet/adjustments', [...$payload, 'amount' => 200])->assertSessionHasErrors('idempotency_key');
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'available' => 100]);
    }

    public function test_negative_adjustments_cannot_overdraw_or_mutate_existing_entries(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->finance(), 'admin');
        $payload = ['user_id' => $user->id, 'amount' => 100, 'reason' => 'Testing', 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'];
        $this->post('/admin/wallet/adjustments', $payload)->assertRedirect();
        $this->post('/admin/wallet/adjustments', [...$payload, 'amount' => -101, 'idempotency_key' => 'c810f941-27a8-4a66-a477-4b70e1368096'])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('wallet_ledger', 1);
        $this->post('/admin/wallet/adjustments', [...$payload, 'amount' => -40, 'idempotency_key' => 'd810f941-27a8-4a66-a477-4b70e1368096'])->assertRedirect();
        $this->assertDatabaseCount('wallet_ledger', 2);
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'available' => 60]);
        $this->assertSame(60, (int) DB::table('wallet_ledger')->sum('amount'));
    }

    public function test_wallet_history_is_private_and_in_ledger_order(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($this->finance(), 'admin');
        $payload = ['user_id' => $user->id, 'amount' => 10, 'reason' => 'First', 'idempotency_key' => 'b810f941-27a8-4a66-a477-4b70e1368096'];
        $this->post('/admin/wallet/adjustments', $payload)->assertRedirect();
        $this->post('/admin/wallet/adjustments', [...$payload, 'amount' => 20, 'reason' => 'Second', 'idempotency_key' => 'c810f941-27a8-4a66-a477-4b70e1368096'])->assertRedirect();
        $this->actingAs($other, 'web')->get('/wallet')->assertInertia(fn (Assert $page) => $page->where('wallet.available', 0)->has('ledger.data', 0));
        $this->actingAs($user, 'web')->get('/wallet')->assertInertia(fn (Assert $page) => $page->where('wallet.available', 30)->has('ledger.data', 2)->where('ledger.data.0.amount', 20)->where('ledger.data.0.available_after', 30));
    }

    public function test_only_finance_can_adjust_and_users_see_their_own_ledger(): void
    {
        $user = User::factory()->create();
        $admin = $this->finance();
        $admin->update(['role' => 'operations']);
        $this->actingAs($admin, 'admin')->post('/admin/wallet/adjustments', [])->assertForbidden();
        $this->actingAs($user, 'web')->get('/wallet')->assertInertia(fn (Assert $page) => $page->component('Wallet', false)->where('wallet.available', 0)->has('ledger.data', 0));
        $this->post('/admin/wallet/adjustments', [])->assertForbidden();
    }
}
