<?php

namespace App\Http\Controllers;

use App\Models\ChartEntry;
use App\Models\ChartVote;
use App\Models\Song;
use App\Services\ChartService;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function index(Request $request, ChartService $chartService)
    {
        // Проверяем авторизацию
        $authUser = null;
        $token = $request->cookie('tg_session');
        if ($token) {
            $authService = app(TelegramAuthService::class);
            $authUser = $authService->getUserBySessionToken($token);
        }

        // Лучшие песни: случайные призёры чартов (1–5 места) с обложками, разные авторы
        $topTracks = $chartService->getShowcaseTracks(20);

        // ID песен, за которые авторизованный пользователь голосовал
        $votedSongIds = [];
        if ($authUser) {
            $songIds = collect($topTracks)->pluck('song_id')->toArray();
            $entryIds = ChartEntry::whereIn('song_id', $songIds)->pluck('id')->toArray();
            $votedEntryIds = ChartVote::where('user_id', $authUser->user_id)
                ->whereIn('chart_entry_id', $entryIds)
                ->pluck('chart_entry_id')
                ->toArray();

            // Конвертируем entry_id -> song_id
            $votedSongIds = ChartEntry::whereIn('id', $votedEntryIds)
                ->pluck('song_id')
                ->unique()
                ->values()
                ->toArray();
        }

        return view('landing', compact('topTracks', 'authUser', 'votedSongIds'));
    }

    /**
     * API: Увеличить счётчик прослушиваний
     */
    public function incrementPlay(Request $request)
    {
        $request->validate(['song_id' => 'required|integer']);

        $song = Song::find($request->input('song_id'));
        if (! $song) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $song->increment('plays_count');

        return response()->json([
            'success' => true,
            'plays_count' => $song->fresh()->plays_count,
        ]);
    }

    /**
     * API: Лайк/анлайк с лендинга (для авторизованных)
     */
    public function toggleLike(Request $request, ChartService $chartService)
    {
        $request->validate(['song_id' => 'required|integer']);

        $user = $request->get('auth_user');
        if (! $user) {
            return response()->json(['error' => 'Требуется авторизация'], 401);
        }

        $songId = $request->input('song_id');

        // Находим entry (предпочтительно из активного чарта)
        $entry = ChartEntry::where('song_id', $songId)
            ->whereHas('chart', fn ($q) => $q->where('is_active', true))
            ->first();

        if (! $entry) {
            $entry = ChartEntry::where('song_id', $songId)->latest()->first();
        }

        if (! $entry) {
            return response()->json(['error' => 'Песня не найдена в чартах'], 404);
        }

        // Нельзя за свои
        if ($entry->user_id === $user->user_id) {
            return response()->json(['error' => 'Нельзя голосовать за свои песни'], 400);
        }

        // Проверяем существующий голос за любой entry этой песни
        $existingVote = ChartVote::where('user_id', $user->user_id)
            ->whereIn('chart_entry_id', function ($query) use ($songId) {
                $query->select('id')->from('chart_entries')->where('song_id', $songId);
            })
            ->first();

        if ($existingVote) {
            // Убираем голос
            $voteEntry = ChartEntry::find($existingVote->chart_entry_id);
            DB::transaction(function () use ($existingVote, $voteEntry) {
                $existingVote->delete();
                if ($voteEntry) {
                    $voteEntry->decrement('votes_count');
                }
            });

            $totalVotes = ChartEntry::where('song_id', $songId)->sum('votes_count');

            return response()->json([
                'success' => true,
                'action' => 'unliked',
                'votes_count' => $totalVotes,
            ]);
        }

        // Защита от накрутки: возраст аккаунта, IP и устройство
        $ip = $request->ip();
        $deviceId = $request->attributes->get('device_id');
        if ($reason = $chartService->voteRejectionReason($user, $ip, $deviceId, $songId)) {
            return response()->json(['error' => $reason], 403);
        }

        // Добавляем голос
        DB::transaction(function () use ($entry, $user, $ip, $deviceId) {
            ChartVote::create([
                'chart_entry_id' => $entry->id,
                'user_id' => $user->user_id,
                'ip_address' => $ip,
                'device_id' => $deviceId,
            ]);
            $entry->increment('votes_count');
        });

        $totalVotes = ChartEntry::where('song_id', $songId)->sum('votes_count');

        return response()->json([
            'success' => true,
            'action' => 'liked',
            'votes_count' => $totalVotes,
        ]);
    }

    /**
     * Публичная страница "Лучшие песни"
     */
    public function bestSongs(Request $request)
    {
        // Проверяем авторизацию
        $authUser = null;
        $token = $request->cookie('tg_session');
        if ($token) {
            $authService = app(TelegramAuthService::class);
            $authUser = $authService->getUserBySessionToken($token);
        }

        // Все треки из чартов, сгруппированные по song_id
        $query = ChartEntry::select(
            'song_id',
            'user_id',
            DB::raw('SUM(votes_count) as total_votes'),
            DB::raw('MIN(id) as id'),
            DB::raw('MIN(created_at) as first_added')
        )
            ->whereHas('song', function ($q) {
                $q->whereNotNull('file_path')
                    ->where(function ($q2) {
                        $q2->where('is_deleted', false)->orWhereNull('is_deleted');
                    });
            })
            ->groupBy('song_id', 'user_id')
            ->orderByDesc('total_votes')
            ->orderBy('first_added');

        $entries = $query->paginate(20);

        // Подгружаем связи для текущей страницы
        $entries->getCollection()->transform(function ($entry) {
            $entry->setRelation('song', Song::find($entry->song_id));
            $entry->setRelation('user', \App\Models\User::where('user_id', $entry->user_id)->first());

            return $entry;
        });

        // Голоса авторизованного пользователя
        $votedSongIds = [];
        if ($authUser) {
            $songIds = $entries->pluck('song_id')->toArray();
            $entryIds = ChartEntry::whereIn('song_id', $songIds)->pluck('id')->toArray();
            $votedEntryIds = ChartVote::where('user_id', $authUser->user_id)
                ->whereIn('chart_entry_id', $entryIds)
                ->pluck('chart_entry_id')
                ->toArray();
            $votedSongIds = ChartEntry::whereIn('id', $votedEntryIds)
                ->pluck('song_id')->unique()->values()->toArray();
        }

        return view('public.best-songs', compact('entries', 'authUser', 'votedSongIds'));
    }
}
