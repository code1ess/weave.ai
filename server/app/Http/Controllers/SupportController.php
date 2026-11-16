<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['task' => 'nullable|uuid']);
        $task = $request->filled('task') ? DB::table('generation_tasks')->where('id', $request->input('task'))->where('user_id', $request->user()->id)->first(['id']) : null;
        abort_if($request->filled('task') && ! $task, 404);

        return Inertia::render('Support', ['tickets' => SupportTicket::where('user_id', $request->user()->id)->select('id', 'subject', 'status', 'updated_at')->latest('updated_at')->paginate(15), 'taskId' => $task?->id]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['subject' => 'required|string|max:160', 'body' => 'required|string|max:5000', 'task_id' => ['nullable', 'uuid', Rule::exists('generation_tasks', 'id')->where('user_id', $request->user()->id)], 'idempotency_key' => 'required|uuid']);
        $ticket = SupportTicket::firstOrCreate(['user_id' => $request->user()->id, 'idempotency_key' => $data['idempotency_key']], ['subject' => $data['subject'], 'body' => $data['body'], 'task_id' => $data['task_id'] ?? null, 'status' => 'open']);
        if ($ticket->subject !== $data['subject'] || $ticket->body !== $data['body'] || $ticket->task_id !== ($data['task_id'] ?? null)) {
            throw ValidationException::withMessages(['idempotency_key' => __('提交标识已用于其他工单。')]);
        }

        return redirect('/support/'.$ticket->id);
    }

    public function show(Request $request, string $ticket)
    {
        return $this->detail(SupportTicket::where('user_id', $request->user()->id)->findOrFail($ticket), false);
    }

    public function adminIndex(Request $request)
    {
        $data = $request->validate(['status' => 'nullable|in:open,in_progress,resolved']);

        return Inertia::render('Admin/Support', ['status' => $data['status'] ?? '', 'tickets' => SupportTicket::select('id', 'user_id', 'subject', 'status', 'updated_at')->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->latest('updated_at')->paginate(20)->withQueryString()]);
    }

    public function adminShow(string $ticket)
    {
        return $this->detail(SupportTicket::findOrFail($ticket), true);
    }

    private function detail(SupportTicket $ticket, bool $admin)
    {
        return Inertia::render($admin ? 'Admin/SupportDetail' : 'SupportDetail', ['ticket' => $ticket->only('id', 'user_id', 'task_id', 'subject', 'body', 'status', 'created_at'), 'messages' => DB::table('support_messages')->where('ticket_id', $ticket->id)->select('id', 'body', 'admin_user_id', 'created_at')->orderByDesc('id')->paginate(30)]);
    }

    public function reply(Request $request, string $ticket)
    {
        return $this->appendMessage($request, $ticket, false);
    }

    public function adminReply(Request $request, string $ticket)
    {
        return $this->appendMessage($request, $ticket, true);
    }

    private function appendMessage(Request $request, string $ticket, bool $admin)
    {
        $data = $request->validate(['body' => 'required|string|max:5000', 'idempotency_key' => 'required|uuid', 'status' => $admin ? 'required|in:in_progress,resolved' : 'prohibited']);
        DB::transaction(function () use ($request, $ticket, $admin, $data) {
            $record = SupportTicket::when(! $admin, fn ($q) => $q->where('user_id', $request->user()->id))->lockForUpdate()->findOrFail($ticket);
            $status = $admin ? $data['status'] : 'open';
            $author = $admin ? ['user_id' => null, 'admin_user_id' => $request->user('admin')->id] : ['user_id' => $request->user()->id, 'admin_user_id' => null];
            $existing = DB::table('support_messages')->where('ticket_id', $ticket)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if ($existing->body !== $data['body'] || $existing->requested_status !== $status || $existing->user_id !== $author['user_id'] || $existing->admin_user_id !== $author['admin_user_id']) {
                    throw ValidationException::withMessages(['idempotency_key' => __('提交标识已用于其他回复。')]);
                }

                return;
            }
            DB::table('support_messages')->insert([...$author, 'ticket_id' => $ticket, 'idempotency_key' => $data['idempotency_key'], 'body' => $data['body'], 'requested_status' => $status, 'created_at' => now()]);
            $record->update(['status' => $status, 'updated_at' => now()]);
            if ($admin) {
                DB::table('admin_audit_logs')->insert(['admin_user_id' => $request->user('admin')->id, 'action' => 'support.replied', 'subject' => $ticket, 'reason' => 'status: '.$status, 'created_at' => now()]);
            }
        });

        return redirect(($admin ? '/admin' : '').'/support/'.$ticket);
    }
}
