<?php

namespace App\Http\Controllers;

use App\Models\GenerationTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function loginPage()
    {
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::guard('admin')->attempt([...$data, 'active' => true])) {
            throw ValidationException::withMessages(['email' => __('账号或密码不正确，或账号已停用。')]);
        }
        $request->session()->regenerate();
        DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => 'admin.login', 'created_at' => now()]);

        return Inertia::location(url('/admin'));
    }

    public function logout(Request $request)
    {
        DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => 'admin.logout', 'created_at' => now()]);
        Auth::guard('admin')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }

    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard', ['counts' => ['users' => User::count(), 'tasks' => GenerationTask::count(), 'pending' => GenerationTask::where('status', 'pending')->count()]]);
    }

    public function tasks(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'moderation' => 'nullable|in:pending,approved,rejected']);

        return Inertia::render('Admin/Tasks', ['moderation' => $filters['moderation'] ?? '', 'search' => $filters['search'] ?? '', 'records' => GenerationTask::select('id', 'user_id', 'kind', 'status', 'moderation_status', 'created_at')->when($filters['search'] ?? null, fn ($q, $search) => $q->where('id', $search))->when($filters['moderation'] ?? null, fn ($q, $value) => $q->where('moderation_status', $value))->latest()->paginate(20)->withQueryString()]);
    }

    public function audit()
    {
        return Inertia::render('Admin/Records', ['title' => __('操作审计'), 'search' => null, 'records' => DB::table('admin_audit_logs')->orderByDesc('id')->paginate(20)]);
    }
}
