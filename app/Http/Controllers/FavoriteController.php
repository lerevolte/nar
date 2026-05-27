<?php

namespace App\Http\Controllers;

use App\Models\FavoriteSong;
use App\Models\Song;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Переключить избранное (добавить/убрать)
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'song_id' => 'required|integer',
            'variant' => 'required|integer|in:1,2',
        ]);

        $user = $request->get('auth_user');
        $songId = $request->input('song_id');
        $variant = $request->input('variant');

        // Проверяем что песня принадлежит пользователю
        $song = Song::where('id', $songId)
            ->where(function ($q) {
                $q->where('is_deleted', false)->orWhereNull('is_deleted');
            })
            ->first();

        if (!$song) {
            return response()->json(['error' => 'Песня не найдена'], 404);
        }

        // Проверяем что у песни есть этот вариант
        if ($variant == 1 && !$song->file_path) {
            return response()->json(['error' => 'У песни нет варианта 1'], 400);
        }
        if ($variant == 2 && !$song->file_path_2) {
            return response()->json(['error' => 'У песни нет варианта 2'], 400);
        }

        // Проверяем есть ли уже в избранном
        $existing = FavoriteSong::where('user_id', $user->user_id)
            ->where('song_id', $songId)
            ->where('variant', $variant)
            ->first();

        if ($existing) {
            // Удаляем из избранного
            $existing->delete();
            return response()->json([
                'success' => true,
                'action' => 'removed',
                'message' => 'Убрано из избранного',
            ]);
        }

        // Добавляем в избранное
        FavoriteSong::create([
            'user_id' => $user->user_id,
            'song_id' => $songId,
            'variant' => $variant,
        ]);

        return response()->json([
            'success' => true,
            'action' => 'added',
            'message' => 'Добавлено в избранное',
        ]);
    }

    /**
     * Список избранных треков
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');

        $favorites = FavoriteSong::where('user_id', $user->user_id)
            ->with('song.user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($fav) {
                return [
                    'id' => $fav->id,
                    'song_id' => $fav->song_id,
                    'variant' => $fav->variant,
                    'title' => $fav->song->title ?? 'Без названия',
                    'author' => $fav->song->user->first_name ?? $fav->song->user->username ?? 'Автор',
                    'is_own' => $fav->user_id === $fav->song->user_id,
                    'audio_url' => $fav->variant == 1 
                        ? $fav->song->file_path 
                        : $fav->song->file_path_2,
                    'created_at' => $fav->created_at->format('d.m.Y'),
                ];
            });

        return response()->json([
            'favorites' => $favorites,
            'total' => $favorites->count(),
        ]);
    }
}