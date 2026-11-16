<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminAccountController extends Controller
{
    public function users(Request $request)
    {
        $data = $request->validate(['search' => 'nullable|string|max:100']);

        return Inertia::render('Admin/Users', ['search' => $data['search'] ?? '', 'users' => User::select('id', 'name', 'email', 'active', 'created_at')->when($data['search'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('email', 'like', '%'.$term.'%')->orWhere('name', 'like', '%'.$term.'%')))->latest()->paginate(20)->withQueryString()]);
    }

    public function userStatus(Request $request, int $user)
    {
        $data = $request->validate(['active' => 'required|boolean', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $user, $data) {
            $record = User::lockForUpdate()->findOrFail($user);
            if ($record->active === (bool) $data['active']) {
                return;
            }
            $record->forceFill(['active' => (bool) $data['active']])->save();
            DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => $data['active'] ? 'user.enabled' : 'user.disabled', 'subject' => (string) $user, 'reason' => $data['reason'], 'created_at' => now()]);
        });

        return redirect('/admin/users');
    }

    public function accounts()
    {
        return Inertia::render('Admin/Accounts', ['accounts' => AdminUser::select('id', 'name', 'email', 'role', 'active', 'created_at')->orderBy('id')->paginate(20), 'roles' => array_keys(config('admin.roles'))]);
    }

    public function update(Request $request, int $account)
    {
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(config('admin.roles')))], 'active' => 'required|boolean', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $account, $data) {
            // Revalidate actor under the same lock order for simultaneous admin changes.
            $records = AdminUser::whereIn('id', [$request->user('admin')->id, $account])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $actor = $records->get($request->user('admin')->id);
            abort_unless($actor?->canAccess('accounts.manage'), 403);
            $record = $records->get($account);
            abort_unless($record, 404);
            if ($account === $actor->id) {
                throw ValidationException::withMessages(['account' => __('不能通过此入口更改自己的角色或状态。')]);
            }
            if ($record->role === $data['role'] && $record->active === (bool) $data['active']) {
                return;
            }
            $before = $record->role.':'.($record->active ? 'active' : 'disabled');
            $record->update(['role' => $data['role'], 'active' => (bool) $data['active']]);
            DB::table('admin_audit_logs')->insert(['admin_user_id' => $actor->id, 'action' => 'admin.updated', 'subject' => (string) $account, 'reason' => $before.' -> '.$data['role'].':'.($data['active'] ? 'active' : 'disabled').'; '.$data['reason'], 'created_at' => now()]);
        });

        return redirect('/admin/accounts');
    }
}
