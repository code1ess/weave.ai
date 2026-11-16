<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function adjust(int $userId, int $amount, string $reason, string $key, int $adminId): void
    {
        DB::transaction(function () use ($userId, $amount, $reason, $key, $adminId) {
            // Serialize wallet creation and all balance writes on the owning user.
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $existing = DB::table('wallet_ledger')->where('user_id', $userId)->where('idempotency_key', $key)->first();
            if ($existing) {
                if ((int) $existing->amount !== $amount || $existing->reason !== $reason || (int) $existing->admin_user_id !== $adminId) {
                    throw ValidationException::withMessages(['idempotency_key' => __('此操作标识已用于其他调账，请刷新重试。')]);
                }

                return;
            }
            if ($amount === 0 || abs($amount) > 1000000) {
                throw ValidationException::withMessages(['amount' => __('单次调整必须为非零整数，绝对值不超过 1000000。')]);
            }
            DB::table('wallets')->insertOrIgnore(['user_id' => $userId, 'available' => 0, 'reserved' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $wallet = DB::table('wallets')->where('user_id', $userId)->lockForUpdate()->first();
            $balance = (int) $wallet->available + $amount;
            if ($balance < 0 || $balance > 1000000000) {
                throw ValidationException::withMessages(['amount' => __('可用余额不足或超出钱包上限。')]);
            }
            DB::table('wallets')->where('id', $wallet->id)->update(['available' => $balance, 'updated_at' => now()]);
            $id = (string) Str::uuid();
            DB::table('wallet_ledger')->insert(['id' => $id, 'user_id' => $userId, 'idempotency_key' => $key, 'amount' => $amount, 'available_after' => $balance, 'type' => 'adjustment', 'reason' => $reason, 'admin_user_id' => $adminId, 'created_at' => now()]);
            DB::table('admin_audit_logs')->insert(['admin_user_id' => $adminId, 'action' => 'wallet.adjusted', 'subject' => $id, 'reason' => $reason, 'created_at' => now()]);
        }, 3);
    }
}
