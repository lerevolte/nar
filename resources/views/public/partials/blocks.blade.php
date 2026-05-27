@if(!empty($blocks) && is_array($blocks))
    @foreach($blocks as $block)
        @php $type = $block['type'] ?? null; $data = $block['data'] ?? []; @endphp

        @if($type === 'gradient_text' && !empty($data['html']))
            <div class="article-block-gradient">
                {!! $data['html'] !!}
            </div>

        @elseif($type === 'image_full' && !empty($data['url']))
            <div class="article-block-image">
                <img src="{{ $data['url'] }}" alt="{{ $data['alt'] ?? '' }}" data-no-fancybox>
            </div>

        @elseif($type === 'songs_list' && !empty($data['song_ids']))
            @php
                $songIds = is_array($data['song_ids']) ? array_values($data['song_ids']) : explode(',', $data['song_ids']);
                $songs = \App\Models\Song::whereIn('id', $songIds)
                    ->whereNotNull('file_path')
                    ->with('user')
                    ->get()
                    ->sortBy(function($s) use ($songIds) {
                        return array_search($s->id, $songIds);
                    })
                    ->values();

                // Подтягиваем votes по каждой песне (сумма по всем чартам)
                $songVotes = \App\Models\ChartEntry::whereIn('song_id', $songs->pluck('id'))
                    ->selectRaw('song_id, SUM(votes_count) as total')
                    ->groupBy('song_id')
                    ->pluck('total', 'song_id');

                // ID песен, за которые голосовал текущий пользователь
                $blockVotedIds = [];
                if (!empty($authUser)) {
                    $entryIds = \App\Models\ChartEntry::whereIn('song_id', $songs->pluck('id'))->pluck('id')->toArray();
                    $votedEntryIds = \App\Models\ChartVote::where('user_id', $authUser->user_id)
                        ->whereIn('chart_entry_id', $entryIds)
                        ->pluck('chart_entry_id')
                        ->toArray();
                    $blockVotedIds = \App\Models\ChartEntry::whereIn('id', $votedEntryIds)
                        ->pluck('song_id')
                        ->unique()
                        ->values()
                        ->toArray();
                }

                $blockId = 'songs-block-' . uniqid();
            @endphp
            @if($songs->count() > 0)
                <div class="article-block-songs" id="{{ $blockId }}">
                    @if(!empty($data['title']))
                        <h2 class="article-block-heading">{{ $data['title'] }}</h2>
                    @endif
                    <div class="article-block-songs-grid">
                        @foreach($songs as $i => $song)
                            @php
                                $isOwn = !empty($authUser) && $authUser->user_id === $song->user_id;
                                $isLiked = in_array($song->id, $blockVotedIds);
                                $votesCount = (int) ($songVotes[$song->id] ?? 0);
                            @endphp
                            <div class="track-card {{ $i >= 4 ? 'hidden-item' : '' }}">
                                <div class="track-card-cover">
                                    @if($song->cover_url)
                                        <img src="{{ $song->cover_url }}" alt="{{ $song->title }}" draggable="false" data-no-fancybox>
                                    @else
                                        <div class="track-cover-placeholder">🎵</div>
                                    @endif

                                    @if($song->file_path)
                                        <div class="track-play-btn"
                                             data-play-track
                                             data-url="{{ $song->file_path }}"
                                             data-title="{{ $song->title }}"
                                             data-author="{{ $song->user->first_name ?? 'Аноним' }}"
                                             data-cover="{{ $song->cover_url ?? '' }}"
                                             data-song-id="{{ $song->id }}">
                                            <svg class="icon-play" width="36" height="36" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                            <svg class="icon-pause" width="36" height="36" viewBox="0 0 24 24" fill="white" style="display:none;"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                            <svg class="icon-loading" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="display:none;"><path d="M12 2v4m0 12v4m10-10h-4M6 12H2m15.07-7.07l-2.83 2.83M9.76 14.24l-2.83 2.83m12.14 0l-2.83-2.83M9.76 9.76L6.93 6.93"/></svg>
                                        </div>
                                    @endif

                                    <div class="track-controls-bar">
                                        <button class="track-control-btn" title="Прослушиваний">
                                            <svg viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                            <span class="plays-count-{{ $song->id }}">{{ $song->plays_count ?? 0 }}</span>
                                        </button>
                                        <button class="track-control-btn {{ $isLiked ? 'liked' : '' }} {{ $isOwn ? 'own-song' : '' }}"
                                                onclick="toggleLike({{ $song->id }}, this)"
                                                {{ $isOwn ? 'disabled' : '' }}>
                                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                                            <span class="likes-count-{{ $song->id }}">{{ $votesCount }}</span>
                                        </button>
                                        <button class="track-info-btn"
                                            data-track-info
                                            data-title="{{ $song->title }}"
                                            data-author="{{ $song->user->first_name ?? 'Аноним' }}"
                                            data-genre="{{ $song->genre ?? '' }}"
                                            data-occasion="{{ $song->occasion ?? '' }}"
                                            data-created="{{ $song->created_at ? $song->created_at->format('d.m.Y') : '' }}"
                                            data-plays="{{ $song->plays_count ?? 0 }}"
                                            data-votes="{{ $votesCount }}"
                                            data-lyrics="{{ $song->lyrics ?? '' }}">i</button>
                                    </div>
                                </div>
                                <div class="track-card-title">{{ $song->title }}</div>
                                <div class="track-card-author">{{ $song->user->first_name ?? 'Аноним' }}</div>
                            </div>
                        @endforeach
                    </div>
                    @if($songs->count() > 4)
                        <button type="button" class="article-block-toggle" onclick="toggleBlockItems('{{ $blockId }}', this)">
                            Показать все, {{ $songs->count() }} песен
                        </button>
                    @endif
                </div>
            @endif
        @elseif($type === 'articles_list' && !empty($data['article_ids']))
            @php
                $artIds = is_array($data['article_ids']) ? $data['article_ids'] : explode(',', $data['article_ids']);
                $arts = \App\Models\Article::published()
                    ->whereIn('id', $artIds)
                    ->get()
                    ->sortBy(function($a) use ($artIds) {
                        return array_search($a->id, $artIds);
                    })
                    ->values();
                $blockId = 'articles-block-' . uniqid();
            @endphp
            @if($arts->count() > 0)
                <div class="article-block-articles" id="{{ $blockId }}">
                    @if(!empty($data['title']))
                        <h2 class="article-block-heading">{{ $data['title'] }}</h2>
                    @endif
                    <div class="article-block-articles-grid">
                        @foreach($arts as $i => $art)
                            <a href="{{ route('public.articles.show', $art->slug) }}" class="article-card {{ $i >= 4 ? 'hidden-item' : '' }}">
                                <div class="article-card-cover">
                                    @if($art->cover_url)
                                        <img src="{{ $art->cover_thumb_url ?? $art->cover_url }}" alt="{{ $art->title }}" data-no-fancybox>
                                    @else
                                        <div class="article-card-cover-placeholder">📝</div>
                                    @endif
                                </div>
                                <div class="article-card-info">
                                    <div class="article-card-title">{{ $art->title }}</div>
                                    <div class="article-card-meta">
                                        <span class="article-card-meta-item">{{ ($art->published_at ?? $art->created_at)->format('d.m.Y') }}</span>
                                        <span class="article-card-meta-item">👁 {{ $art->views_count }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    @if($arts->count() > 4)
                        <button type="button" class="article-block-toggle" onclick="toggleBlockItems('{{ $blockId }}', this)">
                            Показать все статьи ({{ $arts->count() }})
                        </button>
                    @endif
                </div>
            @endif
        @endif
    @endforeach
@endif