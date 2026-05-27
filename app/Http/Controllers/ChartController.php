<?php

namespace App\Http\Controllers;

use App\Models\Chart;
use App\Models\ChartEntry;
use App\Models\ChartVote;
use App\Models\Song;
use App\Services\ChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChartController extends Controller
{
    /**
     * Главная страница чартов
     */
    public function index(Request $request, ChartService $chartService)
    {
        $user = $request->get('auth_user');

        // Получаем активный чарт (или создаём новый)
        $chart = $chartService->getOrCreateCurrentChart();

        // Получаем записи чарта с песнями и авторами
        $entries = ChartEntry::with(['song', 'user'])
            ->where('chart_id', $chart->id)
            ->get()
            ->shuffle(); // Случайный порядок при каждой загрузке

        // Проставляем позиции по голосам (для отображения реального места)
        $ranked = $entries->sortByDesc('votes_count')->values();
        $ranked->each(function ($entry, $index) {
            $entry->rank = $index + 1;
        });

        // Получаем ID песен, за которые пользователь уже голосовал
        $votedEntryIds = ChartVote::where('user_id', $user->user_id)
            ->whereIn('chart_entry_id', $entries->pluck('id'))
            ->pluck('chart_entry_id')
            ->toArray();

        // Проверяем, есть ли у пользователя песня в чарте
        $userEntry = ChartEntry::with('song')
            ->where('chart_id', $chart->id)
            ->where('user_id', $user->user_id)
            ->first();

        // Получаем песни пользователя для добавления
        $userSongs = collect();
        if (!$userEntry) {
            $userSongs = Song::where('user_id', $user->user_id)
                ->notDeleted()
                ->whereNotNull('file_path')
                ->whereNotIn('id', function ($query) use ($chart) {
                    $query->select('song_id')
                        ->from('chart_entries')
                        ->where('chart_id', $chart->id);
                })
                ->get();
        }

        // Избранные
        $favoriteSongIds = \App\Models\FavoriteSong::where('user_id', $user->user_id)
            ->pluck('song_id')
            ->toArray();

        // Призы
        $rewards = ChartService::getRewardsForChart($chart);

        return view('charts.index', compact(
            'chart',
            'entries',
            'votedEntryIds',
            'userSongs',
            'userEntry',
            'rewards',
            'favoriteSongIds'
        ));
    }

    /**
     * Добавить песню в чарт
     */
    public function addSong(Request $request, ChartService $chartService)
    {
        $request->validate([
            'song_id' => 'required|integer',
            'variant' => 'nullable|integer|in:1,2',
            'comment' => 'nullable|string|max:500'
        ]);

        $user = $request->get('auth_user');
        $songId = $request->input('song_id');
        $variant = $request->input('variant', 1);
        $comment = $request->input('comment');

        // Проверяем что песня принадлежит пользователю
        $song = Song::where('id', $songId)
            ->where('user_id', $user->user_id)
            ->whereNotNull('file_path')
            ->first();

        if (!$song) {
            return response()->json(['error' => 'Песня не найдена'], 404);
        }

        $chart = $chartService->getOrCreateCurrentChart();

        // Проверяем лимит (1 песня на чарт)
        $canAdd = $chartService->canUserAddSong($user->user_id, $chart->id);
        
        if (!$canAdd['can_add']) {
            return response()->json(['error' => $canAdd['reason']], 400);
        }

        // Проверяем что песня ещё не в чарте
        $existing = ChartEntry::where('chart_id', $chart->id)
            ->where('song_id', $songId)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Эта песня уже участвует в чарте'], 400);
        }

        // Добавляем
        $entry = ChartEntry::create([
            'chart_id' => $chart->id,
            'song_id' => $songId,
            'user_id' => $user->user_id,
            'votes_count' => 0,
            'variant' => $variant,
            'comment' => $comment
        ]);

        return response()->json([
            'success' => true,
            'entry_id' => $entry->id,
            'message' => 'Песня добавлена в чарт!',
        ]);
    }

    /**
     * Голосование за песню
     */
    public function vote(Request $request)
    {
        $request->validate([
            'entry_id' => 'required|integer',
        ]);

        $user = $request->get('auth_user');
        $entryId = $request->input('entry_id');

        $entry = ChartEntry::find($entryId);
        if (!$entry) {
            return response()->json(['error' => 'Запись не найдена'], 404);
        }

        // Проверяем что чарт активен
        $chart = Chart::find($entry->chart_id);
        if (!$chart || !$chart->is_active) {
            return response()->json(['error' => 'Чарт уже завершён'], 400);
        }

        // Нельзя голосовать за свои песни
        if ($entry->user_id === $user->user_id) {
            return response()->json(['error' => 'Нельзя голосовать за свои песни'], 400);
        }

        // Голосовать могут только пользователи с покупками
        $hasPurchases = \App\Models\Payment::where('user_id', $user->user_id)
            ->where('status', 'succeeded')
            ->exists();

        if (!$hasPurchases) {
            return response()->json(['error' => 'Голосовать могут только пользователи, совершившие покупку'], 403);
        }


        // Проверяем что ещё не голосовал за эту песню
        $existingVote = ChartVote::where('chart_entry_id', $entryId)
            ->where('user_id', $user->user_id)
            ->first();

        if ($existingVote) {
            return response()->json(['error' => 'Вы уже голосовали за эту песню'], 400);
        }

        // Лимит голосов в день (макс 10)
        $todayVotes = ChartVote::where('user_id', $user->user_id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todayVotes >= 10) {
            return response()->json(['error' => 'Лимит голосов на сегодня исчерпан (10 в день)'], 400);
        }

        // Создаём голос
        DB::transaction(function () use ($entry, $user, $entryId) {
            ChartVote::create([
                'chart_entry_id' => $entryId,
                'user_id' => $user->user_id,
            ]);

            $entry->increment('votes_count');
        });

        return response()->json([
            'success' => true,
            'votes_count' => $entry->fresh()->votes_count,
        ]);
    }

    /**
     * Убрать голос
     */
    public function unvote(Request $request)
    {
        $request->validate([
            'entry_id' => 'required|integer',
        ]);

        $user = $request->get('auth_user');
        $entryId = $request->input('entry_id');

        $entry = ChartEntry::find($entryId);
        if (!$entry) {
            return response()->json(['error' => 'Запись не найдена'], 404);
        }

        // Проверяем что чарт активен
        $chart = Chart::find($entry->chart_id);
        if (!$chart || !$chart->is_active) {
            return response()->json(['error' => 'Чарт уже завершён'], 400);
        }

        // Находим голос
        $vote = ChartVote::where('chart_entry_id', $entryId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$vote) {
            return response()->json(['error' => 'Вы не голосовали за эту песню'], 400);
        }

        // Удаляем голос
        DB::transaction(function () use ($entry, $vote) {
            $vote->delete();
            $entry->decrement('votes_count');
        });

        return response()->json([
            'success' => true,
            'votes_count' => $entry->fresh()->votes_count,
        ]);
    }

    /**
     * Архив чартов
     */
    public function archive(Request $request)
    {
        $charts = Chart::where('is_active', false)
            ->where('period', 'weekly')
            ->orderByDesc('ends_at')
            ->paginate(10);

        return view('charts.archive', compact('charts'));
    }


    /**
     * Просмотр конкретного чарта
     */
    public function show(Request $request, $id)
    {
        $user = $request->get('auth_user');

        $chart = Chart::findOrFail($id);

        $entries = ChartEntry::with(['song', 'user'])
            ->where('chart_id', $chart->id)
            ->orderByDesc('votes_count')
            ->orderBy('created_at')
            ->get();

        // Проставляем позиции
        $entries->each(function ($entry, $index) {
            $entry->position = $index + 1;
        });

        // Получаем голоса пользователя если чарт активен
        $votedEntryIds = [];
        if ($chart->is_active) {
            $votedEntryIds = ChartVote::where('user_id', $user->user_id)
                ->whereIn('chart_entry_id', $entries->pluck('id'))
                ->pluck('chart_entry_id')
                ->toArray();
        }

        // Избранные
        $favoriteSongIds = \App\Models\FavoriteSong::where('user_id', $user->user_id)
            ->pluck('song_id')
            ->toArray();

        // Призы
        $rewards = ChartService::getRewardsForChart($chart);

        // Награды победителям
        $chartRewards = $chart->rewards()->with(['user', 'entry.song'])->get();

        return view('charts.show', compact(
            'chart',
            'entries',
            'votedEntryIds',
            'favoriteSongIds',
            'rewards',
            'chartRewards'
        ));
    }


    /**
     * Чарт за всё время
     */
    public function allTime(Request $request)
    {
        $user = $request->get('auth_user');

        // Получаем все песни, которые когда-либо участвовали в чартах
        // Группируем по song_id и суммируем голоса
        $entries = ChartEntry::select(
                'song_id',
                'user_id',
                DB::raw('SUM(votes_count) as total_votes'),
                DB::raw('MIN(id) as id'),
                DB::raw('MIN(created_at) as first_added')
            )
            ->with(['song', 'user'])
            ->groupBy('song_id', 'user_id')
            ->orderByDesc('total_votes')
            ->orderBy('first_added')
            ->take(100)
            ->get();

        // Проставляем позиции
        $entries->each(function ($entry, $index) {
            $entry->position = $index + 1;
            $entry->votes_count = $entry->total_votes; // для совместимости с view
        });

        // Получаем все entry_id для этих песен (для голосования)
        $songIds = $entries->pluck('song_id')->toArray();
        
        $entryIds = ChartEntry::whereIn('song_id', $songIds)
            ->pluck('id')
            ->toArray();

        // Голоса пользователя
        $votedEntryIds = ChartVote::where('user_id', $user->user_id)
            ->whereIn('chart_entry_id', $entryIds)
            ->pluck('chart_entry_id')
            ->toArray();

        // Для каждой песни находим entry_id из активного чарта (если есть) или последний
        $songEntryMap = [];
        foreach ($entries as $entry) {
            $activeEntry = ChartEntry::where('song_id', $entry->song_id)
                ->whereHas('chart', function ($q) {
                    $q->where('is_active', true);
                })
                ->first();
            
            if ($activeEntry) {
                $songEntryMap[$entry->song_id] = $activeEntry->id;
            } else {
                // Берём последний entry
                $lastEntry = ChartEntry::where('song_id', $entry->song_id)
                    ->latest()
                    ->first();
                $songEntryMap[$entry->song_id] = $lastEntry?->id;
            }
        }
        $favoriteSongIds = \App\Models\FavoriteSong::where('user_id', $user->user_id)
            ->pluck('song_id')
            ->toArray();

        return view('charts.all-time', compact('entries', 'votedEntryIds', 'songEntryMap', 'favoriteSongIds'));
    }

    /**
     * Голосование в чарте за всё время
     */
    public function voteAllTime(Request $request)
    {
        $request->validate([
            'song_id' => 'required|integer',
        ]);

        $user = $request->get('auth_user');
        $songId = $request->input('song_id');

        // Находим entry для этой песни (предпочтительно из активного чарта)
        $entry = ChartEntry::where('song_id', $songId)
            ->whereHas('chart', function ($q) {
                $q->where('is_active', true);
            })
            ->first();

        if (!$entry) {
            // Берём последний entry
            $entry = ChartEntry::where('song_id', $songId)->latest()->first();
        }

        if (!$entry) {
            return response()->json(['error' => 'Песня не найдена'], 404);
        }

        // Нельзя голосовать за свои песни
        if ($entry->user_id === $user->user_id) {
            return response()->json(['error' => 'Нельзя голосовать за свои песни'], 400);
        }

        // Проверяем, голосовал ли уже за любой entry этой песни
        $existingVote = ChartVote::where('user_id', $user->user_id)
            ->whereIn('chart_entry_id', function ($query) use ($songId) {
                $query->select('id')
                    ->from('chart_entries')
                    ->where('song_id', $songId);
            })
            ->first();

        if ($existingVote) {
            return response()->json(['error' => 'Вы уже голосовали за эту песню'], 400);
        }

        // Лимит голосов в день
        $todayVotes = ChartVote::where('user_id', $user->user_id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todayVotes >= 10) {
            return response()->json(['error' => 'Лимит голосов на сегодня исчерпан (10 в день)'], 400);
        }

        // Создаём голос
        DB::transaction(function () use ($entry, $user) {
            ChartVote::create([
                'chart_entry_id' => $entry->id,
                'user_id' => $user->user_id,
            ]);

            $entry->increment('votes_count');
        });

        // Считаем общее количество голосов за эту песню
        $totalVotes = ChartEntry::where('song_id', $songId)->sum('votes_count');

        return response()->json([
            'success' => true,
            'votes_count' => $totalVotes,
        ]);
    }

    /**
     * Убрать голос в чарте за всё время
     */
    public function unvoteAllTime(Request $request)
    {
        $request->validate([
            'song_id' => 'required|integer',
        ]);

        $user = $request->get('auth_user');
        $songId = $request->input('song_id');

        // Находим голос пользователя за эту песню
        $vote = ChartVote::where('user_id', $user->user_id)
            ->whereIn('chart_entry_id', function ($query) use ($songId) {
                $query->select('id')
                    ->from('chart_entries')
                    ->where('song_id', $songId);
            })
            ->first();

        if (!$vote) {
            return response()->json(['error' => 'Вы не голосовали за эту песню'], 400);
        }

        $entry = ChartEntry::find($vote->chart_entry_id);

        // Удаляем голос
        DB::transaction(function () use ($entry, $vote) {
            $vote->delete();
            if ($entry) {
                $entry->decrement('votes_count');
            }
        });

        // Считаем общее количество голосов за эту песню
        $totalVotes = ChartEntry::where('song_id', $songId)->sum('votes_count');

        return response()->json([
            'success' => true,
            'votes_count' => $totalVotes,
        ]);
    }

    /**
     * Удалить песню из чарта
     */
    public function removeSong(Request $request)
    {
        $request->validate([
            'song_id' => 'required|integer',
        ]);

        $user = $request->get('auth_user');
        $songId = $request->input('song_id');

        // Ищем запись в активном чарте
        $entry = ChartEntry::where('song_id', $songId)
            ->where('user_id', $user->user_id)
            ->whereHas('chart', function ($q) {
                $q->where('is_active', true);
            })
            ->first();

        if (!$entry) {
            return response()->json(['error' => 'Песня не найдена в активном чарте'], 404);
        }

        DB::transaction(function () use ($entry) {
            // Удаляем голоса (если не настроен каскадное удаление в БД)
            ChartVote::where('chart_entry_id', $entry->id)->delete();
            // Удаляем запись
            $entry->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Песня удалена из чарта',
        ]);
    }

    /**
     * Публичный API: топ-5 треков для посадочной страницы
     */
    public function publicTopTracks(ChartService $chartService)
    {
        // Получаем активный чарт
        $chart = $chartService->getOrCreateCurrentChart();

        // Топ-5 треков
        $entries = ChartEntry::with(['song', 'user'])
            ->where('chart_id', $chart->id)
            ->where('votes_count', '>', 0)
            ->orderByDesc('votes_count')
            ->orderBy('created_at')
            ->take(5)
            ->get();

        $tracks = $entries->map(function ($entry, $index) {
            return [
                'position' => $index + 1,
                'title' => $entry->song->title ?? 'Без названия',
                'author' => $entry->user->first_name ?? $entry->user->username ?? 'Автор',
                'votes' => $entry->votes_count,
                'audio_url' => ($entry->variant == 2) 
                    ? $entry->song->file_path_2 
                    : $entry->song->file_path,
            ];
        });

        return response()->json([
            'chart_name' => $chart->name,
            'tracks' => $tracks,
        ]);
    }

    /**
     * Тематический чарт (14 февраля)
     */
    public function valentine(Request $request, ChartService $chartService)
    {
        $user = $request->get('auth_user');

        // Получаем или создаём чарт
        $chart = $chartService->getOrCreateThemeChart('valentine');
        
        // Если чарт не найден — создаём
        if (!$chart) {
            $chart = $chartService->createValentineChart();
        }

        // Получаем записи чарта
        $entries = ChartEntry::with(['song', 'user'])
            ->where('chart_id', $chart->id)
            ->orderByDesc('votes_count')
            ->orderBy('created_at')
            ->take(50)
            ->get();

        // Проставляем позиции
        $entries->each(function ($entry, $index) {
            $entry->position = $index + 1;
        });

        // ID песен, за которые пользователь голосовал
        $votedEntryIds = ChartVote::where('user_id', $user->user_id)
            ->whereIn('chart_entry_id', $entries->pluck('id'))
            ->pluck('chart_entry_id')
            ->toArray();

        // Проверяем, есть ли у пользователя песня в этом чарте
        $userEntry = ChartEntry::with('song')
            ->where('chart_id', $chart->id)
            ->where('user_id', $user->user_id)
            ->first();

        // Песни пользователя для добавления
        $userSongs = collect();
        if (!$userEntry) {
            $userSongs = Song::where('user_id', $user->user_id)
                ->whereNotNull('file_path')
                ->whereNotIn('id', function ($query) use ($chart) {
                    $query->select('song_id')
                        ->from('chart_entries')
                        ->where('chart_id', $chart->id);
                })
                ->get();
        }

        // Призы для Valentine - берём напрямую из константы
        $rewards = ChartService::THEME_REWARDS['valentine'] ?? ChartService::REWARDS;

        // Считаем время до окончания
        $now = now();
        $diff = $chart->ends_at->diff($now);

        if ($chart->ends_at->isPast()) {
            $timeLeft = 'Чарт завершён';
        } else {
            $parts = [];
            if ($diff->d > 0) {
                $days = $diff->d;
                $daysWord = $this->pluralize($days, 'день', 'дня', 'дней');
                $parts[] = "{$days} {$daysWord}";
            }
            if ($diff->h > 0) {
                $hours = $diff->h;
                $hoursWord = $this->pluralize($hours, 'час', 'часа', 'часов');
                $parts[] = "{$hours} {$hoursWord}";
            }
            if (empty($parts) && $diff->i > 0) {
                $minutes = $diff->i;
                $minutesWord = $this->pluralize($minutes, 'минута', 'минуты', 'минут');
                $parts[] = "{$minutes} {$minutesWord}";
            }
            $timeLeft = implode(' ', $parts) ?: 'меньше минуты';
        }

        return view('charts.valentine', compact(
            'chart',
            'entries',
            'votedEntryIds',
            'userSongs',
            'userEntry',
            'rewards',
            'timeLeft'
        ));
    }
    /**
     * Добавить песню в тематический чарт
     */
    public function addSongToTheme(Request $request, ChartService $chartService)
    {
        $request->validate([
            'song_id' => 'required|integer',
            'theme' => 'required|string|in:valentine',
            'variant' => 'nullable|integer|in:1,2',
            'comment' => 'nullable|string|max:500'
        ]);

        $user = $request->get('auth_user');
        $songId = $request->input('song_id');
        $theme = $request->input('theme');
        $variant = $request->input('variant', 1);
        $comment = $request->input('comment');

        // Получаем чарт
        $chart = $chartService->getOrCreateThemeChart($theme);
        
        if (!$chart) {
            return response()->json(['error' => 'Чарт не найден или завершён'], 404);
        }

        // Проверяем что песня принадлежит пользователю
        $song = Song::where('id', $songId)
            ->where('user_id', $user->user_id)
            ->whereNotNull('file_path')
            ->first();

        if (!$song) {
            return response()->json(['error' => 'Песня не найдена'], 404);
        }

        // Проверяем лимит (1 песня на тематический чарт)
        $existingEntry = ChartEntry::where('chart_id', $chart->id)
            ->where('user_id', $user->user_id)
            ->first();

        if ($existingEntry) {
            return response()->json(['error' => 'Вы уже добавили песню в этот чарт'], 400);
        }

        // Проверяем что песня ещё не в чарте
        $existingSong = ChartEntry::where('chart_id', $chart->id)
            ->where('song_id', $songId)
            ->first();

        if ($existingSong) {
            return response()->json(['error' => 'Эта песня уже участвует в чарте'], 400);
        }

        // Добавляем
        $entry = ChartEntry::create([
            'chart_id' => $chart->id,
            'song_id' => $songId,
            'user_id' => $user->user_id,
            'votes_count' => 0,
            'variant' => $variant,
            'comment' => $comment
        ]);

        return response()->json([
            'success' => true,
            'entry_id' => $entry->id,
            'message' => 'Песня добавлена в чарт! 💕',
        ]);
    }

    /**
     * Удалить песню из тематического чарта
     */
    public function removeSongFromTheme(Request $request)
    {
        $request->validate([
            'song_id' => 'required|integer',
            'theme' => 'required|string|in:valentine',
        ]);

        $user = $request->get('auth_user');
        $songId = $request->input('song_id');
        $theme = $request->input('theme');

        // Ищем запись в активном тематическом чарте
        $entry = ChartEntry::where('song_id', $songId)
            ->where('user_id', $user->user_id)
            ->whereHas('chart', function ($q) use ($theme) {
                $q->where('is_active', true)->where('theme', $theme);
            })
            ->first();

        if (!$entry) {
            return response()->json(['error' => 'Песня не найдена в чарте'], 404);
        }

        DB::transaction(function () use ($entry) {
            ChartVote::where('chart_entry_id', $entry->id)->delete();
            $entry->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Песня удалена из чарта',
        ]);
    }


    /**
     * Склонение слов
     */
    private function pluralize(int $n, string $one, string $few, string $many): string
    {
        $n = abs($n) % 100;
        if ($n >= 11 && $n <= 19) {
            return $many;
        }
        $n = $n % 10;
        if ($n === 1) {
            return $one;
        }
        if ($n >= 2 && $n <= 4) {
            return $few;
        }
        return $many;
    }
}