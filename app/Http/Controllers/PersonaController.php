<?php

namespace App\Http\Controllers;

use App\Models\UserPersona;
use App\Models\Song;
use App\Services\SunoService;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    private function ensureAllowed(Request $request)
    {
        $user = $request->get('auth_user');
        if (!$user) {
            abort(403);
        }
        return $user;
    }

    public function list(Request $request)
    {
        $user = $this->ensureAllowed($request);
        $personas = UserPersona::forUser($user->user_id)->orderByDesc('id')->get();
        return response()->json(['personas' => $personas]);
    }

    public function create(Request $request, SunoService $sunoService)
    {
        $user = $this->ensureAllowed($request);

        $request->validate([
            'song_id' => 'required|integer',
            'audio_id' => 'required|string|max:255',
            'name' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'style' => 'nullable|string|max:100',
        ]);

        $song = Song::where('id', $request->input('song_id'))
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        if (!$song->suno_task_id) {
            return response()->json(['error' => 'У песни нет task_id'], 400);
        }

        // Проверяем нет ли уже персоны с этим audio_id
        $existing = UserPersona::forUser($user->user_id)
            ->where('audio_id', $request->input('audio_id'))
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Персона для этого варианта уже создана'], 400);
        }

        $result = $sunoService->generatePersona([
            'task_id' => $song->suno_task_id,
            'audio_id' => $request->input('audio_id'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'style' => $request->input('style'),
        ]);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 400);
        }

        $persona = UserPersona::create([
            'user_id' => $user->user_id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'style' => $request->input('style'),
            'persona_id' => $result['persona_id'],
            'task_id' => $song->suno_task_id,
            'audio_id' => $request->input('audio_id'),
            'song_id' => $song->id,
            'status' => 'ready',
        ]);

        return response()->json([
            'success' => true,
            'persona' => $persona,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $this->ensureAllowed($request);
        UserPersona::forUser($user->user_id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}