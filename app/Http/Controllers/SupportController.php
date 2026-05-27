<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    private function getAuthUser(Request $request)
    {
        $token = $request->cookie('tg_session');
        if (!$token) return null;
        return app(TelegramAuthService::class)->getUserBySessionToken($token);
    }

    private function ensureAdmin(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || !in_array($user->user_id, [288559694, 154483653])) {
            abort(403);
        }
        return $user;
    }

    // ===== PUBLIC =====

    public function index(Request $request)
    {
        $authUser = $this->getAuthUser($request);
        return view('public.support.index', compact('authUser'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimes:jpeg,jpg,png,webp,pdf,doc,docx',
        ]);

        $file = $request->file('file');
        $dir = public_path('uploads/support');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $name = time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);

        return response()->json([
            'success' => true,
            'filename' => $name,
            'url' => '/uploads/support/' . $name,
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:5000',
            'attached_files' => 'nullable|string|max:2000',
            'user_id' => 'nullable|integer',
        ]);

        $authUser = $this->getAuthUser($request);

        SupportTicket::create([
            'email' => $request->input('email'),
            'message' => $request->input('message'),
            'attached_files' => $request->input('attached_files'),
            'user_id' => $request->input('user_id') ?: ($authUser->user_id ?? null),
            'ip' => $request->ip(),
        ]);

        return redirect()->route('support.index')->with('success', 'Ваше сообщение отправлено! Мы ответим вам через бота или email.');
    }

    // ===== ADMIN =====

    public function adminIndex(Request $request)
    {
        $this->ensureAdmin($request);

        $status = $request->input('status', 'all');
        $query = SupportTicket::orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $tickets = $query->paginate(30);

        return view('admin.support.index', compact('tickets', 'status'));
    }

    public function adminShow(Request $request, $id)
    {
        $this->ensureAdmin($request);
        $ticket = SupportTicket::findOrFail($id);
        return view('admin.support.show', compact('ticket'));
    }

    public function adminUpdate(Request $request, $id)
    {
        $this->ensureAdmin($request);
        $ticket = SupportTicket::findOrFail($id);

        $request->validate([
            'status' => 'required|in:new,in_progress,closed',
            'admin_reply' => 'nullable|string|max:5000',
        ]);

        $ticket->update([
            'status' => $request->input('status'),
            'admin_reply' => $request->input('admin_reply'),
        ]);

        return redirect()->route('admin.support.show', $id)->with('success', 'Заявка обновлена');
    }

    public function adminDestroy(Request $request, $id)
    {
        $this->ensureAdmin($request);
        SupportTicket::findOrFail($id)->delete();
        return redirect()->route('admin.support.index')->with('success', 'Заявка удалена');
    }
}