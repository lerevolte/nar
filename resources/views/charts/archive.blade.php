@extends('layouts.app')

@section('title', 'Архив чартов — На Репите')

@push('styles')
<style>
    .archive-header { text-align: center; margin-bottom: 24px; }
    .archive-title { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
    .archive-subtitle { color: var(--text-secondary); font-size: 14px; }

    .charts-list { display: flex; flex-direction: column; gap: 16px; }

    .chart-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 20px;
        transition: all var(--duration) var(--ease);
    }
    .chart-card:hover { transform: translateY(-2px); border-color: var(--border-strong); box-shadow: var(--shadow-md); }

    .chart-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .chart-card-title { font-size: 18px; font-weight: 700; color: var(--text-primary); }
    .chart-card-title:hover { color: var(--accent); }
    .chart-card-date { font-size: 12px; color: var(--text-tertiary); }

    .chart-card-stats { display: flex; gap: 16px; margin-bottom: 16px; font-size: 14px; color: var(--text-secondary); }

    .chart-winners { display: flex; flex-direction: column; gap: 8px; }
    .chart-winners-title { font-size: 12px; color: var(--text-tertiary); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }

    .winner-row { display: flex; align-items: center; gap: 8px; font-size: 14px; }
    .winner-place { font-size: 16px; }
    .winner-name { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .winner-reward { font-size: 12px; color: var(--gold); font-weight: 600; }
    .no-winners { font-size: 13px; color: var(--text-tertiary); font-style: italic; }

    .view-chart-btn {
        display: inline-block;
        margin-top: 12px;
        padding: 8px 16px;
        background: var(--surface-glass);
        color: var(--text-primary);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 600;
        transition: background var(--duration) var(--ease);
    }
    .view-chart-btn:hover { background: var(--surface-glass-hover); }
</style>
@endpush

@section('content')
<div class="back-link">
    <a href="{{ route('charts.index') }}">← К текущему чарту</a>
</div>

<div class="archive-header">
    <h1 class="archive-title">📚 Архив чартов</h1>
    <div class="archive-subtitle">История всех прошедших недельных чартов</div>
</div>

@if($charts->isEmpty())
    <div class="empty-chart">
        <div class="empty-chart-icon">📭</div>
        <p>Архив пока пуст</p>
        <p style="margin-top:8px; color: var(--text-tertiary);">Завершённые чарты появятся здесь</p>
    </div>
@else
    <div class="charts-list">
        @foreach($charts as $chart)
            @php
                $topEntries = $chart->entries()->with(['song', 'user'])->orderByDesc('votes_count')->take(5)->get();
                $totalEntries = $chart->entries()->count();
                $totalVotes = $chart->entries()->sum('votes_count');
                $rewards = $chart->rewards()->with(['user', 'entry.song'])->get();
            @endphp
            <div class="chart-card">
                <div class="chart-card-header">
                    <a href="{{ route('charts.show', $chart->id) }}" class="chart-card-title">
                        🏆 {{ $chart->name }}
                    </a>
                    <span class="chart-card-date">
                        {{ $chart->starts_at->format('d.m') }} — {{ $chart->ends_at->format('d.m.Y') }}
                    </span>
                </div>

                <div class="chart-card-stats">
                    <span>🎵 {{ $totalEntries }} треков</span>
                    <span>❤️ {{ $totalVotes }} голосов</span>
                </div>

                @if($topEntries->isNotEmpty())
                    <div class="chart-winners">
                        <div class="chart-winners-title">Победители:</div>
                        @foreach($topEntries as $index => $entry)
                            @php
                                $place = $index + 1;
                                $reward = $rewards->firstWhere('position', $place);
                            @endphp
                            <div class="winner-row">
                                <span class="winner-place">
                                    @if($place == 1) 🥇 @elseif($place == 2) 🥈 @elseif($place == 3) 🥉 @elseif($place == 4) 4️⃣ @elseif($place == 5) 5️⃣ @endif
                                </span>
                                <span class="winner-name">
                                    {{ $entry->song->title ?? 'Без названия' }}
                                    <span style="color: var(--text-tertiary);">
                                        — {{ $entry->user->first_name ?? $entry->user->username ?? 'Автор' }}
                                    </span>
                                </span>
                                <span style="color: var(--text-tertiary);">{{ $entry->votes_count }} ❤️</span>
                                @if($reward)
                                    <span class="winner-reward">+{{ $reward->songs_reward }} 🎁</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="no-winners">В этом чарте не было участников</div>
                @endif

                <a href="{{ route('charts.show', $chart->id) }}" class="view-chart-btn">
                    Посмотреть полный чарт →
                </a>
            </div>
        @endforeach
    </div>

    @if($charts->hasPages())
        <div class="pagination">
            {{ $charts->links() }}
        </div>
    @endif
@endif
@endsection