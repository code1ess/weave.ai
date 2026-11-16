<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $wallet = DB::table('wallets')->where('user_id', $userId)->first();

        return Inertia::render('Wallet', ['wallet' => ['available' => (int) ($wallet->available ?? 0), 'reserved' => (int) ($wallet->reserved ?? 0)], 'ledger' => DB::table('wallet_ledger')->where('user_id', $userId)->select('id', 'amount', 'available_after', 'type', 'created_at')->orderByDesc('sequence')->paginate(20)]);
    }

    public function adminIndex(Request $request)
    {
        $filters = $request->validate(['user_id' => 'nullable|integer|min:1']);
        $userId = $filters['user_id'] ?? null;

        return Inertia::render('Admin/Wallet', ['userId' => $userId ? (int) $userId : null, 'wallet' => $userId ? DB::table('wallets')->where('user_id', $userId)->select('available', 'reserved')->first() : null, 'ledger' => DB::table('wallet_ledger')->when($userId, fn ($q) => $q->where('user_id', $userId))->orderByDesc('sequence')->paginate(20)->withQueryString()]);
    }

    public function adjust(Request $request, WalletService $wallets)
    {
        $data = $request->validate(['user_id' => 'required|integer|exists:users,id', 'amount' => 'required|integer|between:-1000000,1000000|not_in:0', 'reason' => 'required|string|max:1000', 'idempotency_key' => 'required|uuid']);
        $wallets->adjust((int) $data['user_id'], (int) $data['amount'], $data['reason'], $data['idempotency_key'], $request->user('admin')->id);

        return redirect('/admin/wallet?user_id='.$data['user_id']);
    }
}
