<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\GenerationTask;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): AdminUser
    {
        return AdminUser::create(['name' => $role, 'email' => $role.'@example.test', 'password' => 'secure-long-password', 'role' => $role, 'active' => true]);
    }

    public function test_reports_filter_dates_and_do_not_leak_financial_data_to_operations(): void
    {
        $user = User::factory()->create(['created_at' => '2026-01-01 12:00:00']);
        foreach (['2026-01-01 00:00:00', '2026-01-01 23:59:59', '2026-01-02 00:00:00'] as $time) {
            GenerationTask::create(['user_id' => $user->id, 'kind' => 'text_to_image', 'status' => 'pending', 'prompt' => 'Private task', 'idempotency_key' => fake()->uuid(), 'created_at' => $time])->forceFill(['created_at' => $time])->save();
        }
        $this->actingAs($this->staff('operations'), 'admin')->get('/admin/reports?from=2026-01-01&to=2026-01-01')->assertInertia(fn (Assert $page) => $page->component('Admin/Reports', false)->where('summary.tasks', 2)->where('summary.registrations', 1)->where('financial', null)->has('daily', 1)->where('daily.0.tasks', 2));
    }

    public function test_finance_reports_credit_movements_separately_from_income(): void
    {
        $user = User::factory()->create();
        $admin = $this->staff('finance');
        app(WalletService::class)->adjust($user->id, 100, 'Test credits', fake()->uuid(), $admin->id);
        app(WalletService::class)->adjust($user->id, -25, 'Correction', fake()->uuid(), $admin->id);
        $this->actingAs($admin, 'admin')->get('/admin/reports')->assertInertia(fn (Assert $page) => $page->where('financial.increased', '100')->where('financial.decreased', '25')->where('financial.net', '75')->where('financial.available', '75')->missing('financial.revenue'));
    }

    public function test_report_access_and_date_bounds_are_validated(): void
    {
        $this->actingAs($this->staff('support'), 'admin')->get('/admin/reports')->assertForbidden();
        $this->actingAs($this->staff('operations'), 'admin')->get('/admin/reports?from=2026-01-10&to=2026-01-01')->assertSessionHasErrors('to');
        $this->get('/admin/reports?from=2025-01-01&to=2026-01-01')->assertSessionHasErrors('to');
    }
}
