<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d']);
        $from = CarbonImmutable::parse($data['from'] ?? now('UTC')->subDays(29)->toDateString(), 'UTC')->startOfDay();
        $to = CarbonImmutable::parse($data['to'] ?? now('UTC')->toDateString(), 'UTC')->startOfDay();
        if ($to->lt($from) || $from->diffInDays($to) > 92) {
            throw ValidationException::withMessages(['to' => __('日期范围须为 1 至 93 天，结束日期不能早于开始日期。')]);
        }
        $end = $to->addDay();
        $within = fn ($query) => $query->where('created_at', '>=', $from)->where('created_at', '<', $end);
        $tasks = $within(DB::table('generation_tasks'));
        $registrations = $within(DB::table('users'));
        $group = fn ($column) => (clone $tasks)->select($column)->selectRaw('COUNT(*) as total')->groupBy($column)->orderBy($column)->get()->map(fn ($row) => ['label' => $row->$column, 'total' => (int) $row->total]);
        $taskDays = (clone $tasks)->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->pluck('total', 'day');
        $userDays = (clone $registrations)->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->pluck('total', 'day');
        $daily = [];
        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $daily[] = ['day' => $key, 'tasks' => (int) ($taskDays[$key] ?? 0), 'registrations' => (int) ($userDays[$key] ?? 0)];
        }
        $financial = null;
        if ($request->user('admin')->canAccess('reports.financial')) {
            $movements = $within(DB::table('wallet_ledger'));
            $financial = ['increased' => (string) (clone $movements)->where('amount', '>', 0)->sum('amount'), 'decreased' => ltrim((string) (clone $movements)->where('amount', '<', 0)->sum('amount'), '-'), 'net' => (string) $movements->sum('amount'), 'available' => (string) DB::table('wallets')->sum('available'), 'reserved' => (string) DB::table('wallets')->sum('reserved')];
        }

        return Inertia::render('Admin/Reports', ['filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'timezone' => 'UTC', 'summary' => ['registrations' => $registrations->count(), 'tasks' => $tasks->count()], 'byStatus' => $group('status'), 'byKind' => $group('kind'), 'byModeration' => $group('moderation_status'), 'daily' => $daily, 'financial' => $financial]);
    }
}
