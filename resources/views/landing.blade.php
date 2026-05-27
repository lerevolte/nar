@extends('layouts.public')

@section('title', 'На Репите — Нейросеть для генерации песен онлайн | ИИ для создания музыки')
@section('meta')
    <meta name="description" content="Сайт для создания песен: нейросеть запишет музыку высокого качества, ИИ создаст текст и вокал по вашим словам. Полностью на русском языке. Создайте свой трек всего за 3 шага!">
@endsection
@section('jsonld')
    @include('partials.seo.json-ld', ['include' => ['organization', 'website', 'webapp', 'best-songs']])
@endsection
@section('content')
    <main>
        <!-- Hero -->
        <section class="max-w-7xl mx-auto px-4 md:px-8 py-8">
            <div class="hero-banner bg-gradient-to-r from-[#20152e] via-[#1a142c] to-[#121420] rounded-2xl overflow-hidden shadow-2xl relative flex flex-col md:flex-row items-center p-8 md:p-12 gap-8">
                <div class="w-full md:w-1/2 flex justify-center relative">
                    <img src="/img/b1.svg">
                    <!-- <div class="absolute inset-0 bg-fuchsia-600 blur-[80px] opacity-20 rounded-full"></div>
                    <div class="relative w-72 h-48 bg-gradient-to-br from-indigo-300 to-fuchsia-300 rounded-lg shadow-xl border-4 border-white/10 flex items-center justify-center transform -rotate-6">
                        <div class="w-[80%] h-[60%] bg-gray-900 rounded-md border-2 border-gray-700 flex justify-between items-center px-4">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center border-4 border-gray-800"><div class="w-4 h-4 bg-gray-900 rounded-full"></div></div>
                            <div class="w-24 h-6 bg-red-500 rounded text-center text-xs font-bold flex items-center justify-center text-white">НА РЕПИТЕ</div>
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center border-4 border-gray-800"><div class="w-4 h-4 bg-gray-900 rounded-full"></div></div>
                        </div>
                    </div> -->
                </div>
                <div class="w-full md:w-1/2 z-10 text-center md:text-left">
                    <h1 class="hero-title">Нейросеть для создания песен: генерация музыки и текста онлайн</h1>
                    <p class="hero-text">Создавай уникальные песни с помощью ИИ на любой повод</p>
                    <div class="hero-cta">
                        <a href="/create-song" class="btn-blue px-6 py-3 shadow-lg" style="height:auto;">Создать трек</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-4 md:px-8 py-8">
            <h2 style="font-family:MyriadPro,sans-serif;font-size:31px;font-weight:bold;line-height:1.2;margin-bottom:16px;">Создайте свою песню за минуту — онлайн, быстро и без регистрации</h2>
            <p style="font-family:MyriadPro,sans-serif;font-size:18px;font-weight:normal;line-height:1.2;color:#4b5563;">
                «На Репите» — это нейросеть для генерации песен, которая превращает ваши идеи, эмоции и тексты в полноценные музыкальные треки студийного качества. Просто опишите задумку — и ИИ создаст уникальную композицию с живым вокалом, профессиональным сведением и чистым звуком.

            </p>
            <p style="font-family:MyriadPro,sans-serif;font-size:18px;font-weight:normal;line-height:1.2;color:#4b5563;">
                Хотите написать хит, сделать подарок или поэкспериментировать с музыкой? Теперь создать песню можно онлайн буквально за 1 минуту. Подойдёт даже тем, кто никогда не занимался музыкой: вам не нужно разбираться в программах, писать сложные запросы или устанавливать приложения.
            </p>
        </section>

        <!-- Лучшие песни -->
        <section class="py-8">
            <h2 class="max-w-7xl mx-auto px-4 md:px-8" style="font-size:35px;font-weight:bold;margin-bottom:24px;">Лучшие песни</h2>

            @if(empty($topTracks))
                <div class="text-center py-12 text-gray-400 max-w-7xl mx-auto">
                    <div style="font-size:48px;margin-bottom:12px;">🎵</div>
                    <p>Пока нет треков в чартах</p>
                </div>
            @else
                <div class="tracks-slider-wrap">
                    <div class="tracks-slider" id="tracks-slider">
                        @foreach($topTracks as $index => $track)
                            @php
                                $isOwn = $authUser && $authUser->user_id === $track['user_id'];
                                $isLiked = in_array($track['song_id'], $votedSongIds);
                            @endphp
                            <div class="track-card">
                                <div class="track-card-cover">
                                    @if($track['cover_url'])
                                        <img src="{{ $track['cover_url'] }}" alt="{{ $track['title'] }}" draggable="false">
                                    @else
                                        <div class="track-cover-placeholder">🎵</div>
                                    @endif

                                    @if($track['audio_url'])
                                    <div class="track-play-btn"
                                         data-play-track
                                         data-url="{{ $track['audio_url'] }}"
                                         data-title="{{ $track['title'] }}"
                                         data-author="{{ $track['author'] }}"
                                         data-cover="{{ $track['cover_url'] ?? '' }}"
                                         data-song-id="{{ $track['song_id'] }}">
                                        <svg class="icon-play" width="36" height="36" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                        <svg class="icon-pause" width="36" height="36" viewBox="0 0 24 24" fill="white" style="display:none;"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                        <svg class="icon-loading" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="display:none;"><path d="M12 2v4m0 12v4m10-10h-4M6 12H2m15.07-7.07l-2.83 2.83M9.76 14.24l-2.83 2.83m12.14 0l-2.83-2.83M9.76 9.76L6.93 6.93"/></svg>
                                    </div>
                                    @endif

                                    <div class="track-controls-bar">
                                        <button class="track-control-btn" title="Прослушиваний">
                                            <svg viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                            <span class="plays-count-{{ $track['song_id'] }}">{{ $track['plays'] }}</span>
                                        </button>
                                        <button class="track-control-btn {{ $isLiked ? 'liked' : '' }} {{ $isOwn ? 'own-song' : '' }}"
                                                onclick="toggleLike({{ $track['song_id'] }}, this)"
                                                {{ $isOwn ? 'disabled' : '' }}>
                                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                                            <span class="likes-count-{{ $track['song_id'] }}">{{ $track['votes'] }}</span>
                                        </button>
                                        <button class="track-info-btn"
                                            data-track-info
                                            data-title="{{ $track['title'] }}"
                                            data-author="{{ $track['author'] }}"
                                            data-genre="{{ $track['genre'] ?? '' }}"
                                            data-occasion="{{ $track['occasion'] ?? '' }}"
                                            data-created="{{ $track['created_at'] ?? '' }}"
                                            data-plays="{{ $track['plays'] }}"
                                            data-votes="{{ $track['votes'] }}"
                                            data-lyrics="{{ $track['lyrics'] ?? '' }}">i</button>
                                    </div>
                                </div>
                                <div class="track-card-title">{{ $track['title'] }}</div>
                                <div class="track-card-author">{{ $track['author'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <!-- Текстовый блок с подзаголовками -->
        <section class="max-w-7xl mx-auto px-4 md:px-8 py-8" style="padding:35px 25px;border-radius:10px;background-image:linear-gradient(53deg,#f3fef5 12%,#fbf5ff 87%);">
            <h2 style="font-family:MyriadPro,sans-serif;font-size:31px;font-weight:600;color:#1253a2;margin-bottom:16px;">Всего 3 шага для создания песни:</h2>
            
                <ol>
                    
                    <li><b>Выберите стиль, жанр и голос певца</b><br>
                    В библиотеке — более 1000 российских и зарубежных исполнителей. Доступны десятки жанров: Поп, рок, рэп, шансон, электроника, хип-хоп, фолк, джаз, R&B, кантри, металл, лоу-фай, классика, EDM, регги и многое другое.<br>
                    Можно выбрать мужской или женский вокал, разные тембры, настроение и подачу.</li>
                    <li><b>Опишите идею или загрузите готовый текст</b><br>
                    Введите тему, настроение или готовые слова — нейросеть создаст текст песни с рифмой, структурой и логикой.<br>
                    Подходит как для коротких треков, так и для полноценных композиций.
                    </li>
                    <li><b>Скачайте готовый трек</b><br>
                    Уже через 1 минуту вы получаете готовую песню — студийное качество, чистый звук и живой вокал.
                    </li>

                </ol>
            
            <p style="font-family:MyriadPro,sans-serif;font-size:18px;font-weight:normal;color:#333;line-height:1.6;margin-bottom:24px;">
                </p>
            <h2 style="font-family:MyriadPro,sans-serif;font-size:26px;font-weight:600;color:#1253a2;margin-bottom:8px;">Нейросеть работает с любыми деталями, которые вы считаете важными:</h2>
            <p style="font-family:MyriadPro,sans-serif;font-size:18px;font-weight:normal;color:#333;line-height:1.6;margin-bottom:24px;">
                
                <ul>
                    <li>Имена — ваше имя, друзей, членов семьи, клички домашних питомцев</li>
                    <li>Конкретные события — первая встреча, совместное путешествие, смешной случай, важная дата</li>
                    <li>Мечты и цели — то, к чему человек стремится: карьера, путешествие, семья, творчество</li>
                    <li>Личные детали — любимые места, увлечения, цитаты, привычки, воспоминания</li>
                    
                </ul>
            </p>
            <p style="font-family:MyriadPro,sans-serif;font-size:18px;font-weight:normal;color:#333;line-height:1.6;margin-bottom:24px;">
                Чем конкретнее вы описываете — тем живее получается песня. Общие слова дают общий результат. Личные истории дают настоящую магию.
            </p>
            <h2 style="font-family:MyriadPro,sans-serif;font-size:26px;font-weight:600;color:#1253a2;margin-bottom:8px;">Где можно использовать сгенерированные песни</h2>
            <p style="font-family:MyriadPro,sans-serif;font-size:18px;font-weight:normal;color:#333;line-height:1.6;margin-bottom:24px;">
                <ul>
                    <li>YouTube и соцсети — уникальные треки для видео, Shorts, Reels, TikTok</li>
                    <li>Подарки — персональные песни на день рождения, свадьбу, годовщину</li>
                    <li>Бизнес — джинглы, реклама, брендинг, аудиологотипы</li>
                    <li>Мероприятия — выпускные, корпоративы, праздники</li>
                    <li>Контент-маркетинг — вирусные ролики и музыкальные интеграции</li>
                    <li>Личное творчество — демо, идеи для треков, эксперименты со стилями</li>
                </ul>
            </p>
            <p style="font-family:MyriadPro,sans-serif;font-size:18px;font-weight:normal;color:#333;line-height:1.6;margin-bottom:24px;">
                Мы сделали упор на качество русскоязычного контента. Песни, созданные нейросетью, звучат естественно: правильное произношение, живые интонации, точное попадание в ритм и культурный контекст. Готовую песню невозможно отличить от настоящей студийной записи.
            </p>
            <p style="font-family:MyriadPro,sans-serif;font-size:18px;font-weight:normal;color:#333;line-height:1.6;margin-bottom:24px;">
                Выбирайте наш сервис и ваш трек будет у всех «на репите»!
            </p>
        </section>

        <!-- Что делает сервис -->
        <section class="max-w-7xl mx-auto px-4 md:px-8 py-8">
            <h2 style="font-size:35px;font-weight:bold;margin-bottom:24px;">Что делает сервис</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="feature-card">
                    <h3 class="feature-card-title">Песня под любое событие</h3>
                    <p class="feature-card-text">ИИ создаст песню под ваш повод — день рождения, свадьба, признание</p>
                    <div class="feature-card-icon">
                        <img src="/img/i1.svg">
                    </div>
                </div>
                <div class="feature-card">
                    <h3 class="feature-card-title">Участвуй в чартах</h3>
                    <p class="feature-card-text">Добавляй песни в чарты, голосуй за лучшие треки и получай призы</p>
                    <div class="feature-card-icon">
                        <img src="/img/i2.svg">
                    </div>
                </div>
                <div class="feature-card">
                    <h3 class="feature-card-title">Все права ваши</h3>
                    <p class="feature-card-text">Созданные треки принадлежат только вам</p>
                    <div class="feature-card-icon">
                        <img src="/img/i3.svg">
                    </div>
                </div>
            </div>
        </section>
    </main>

    

  
@endsection

@push('scripts')
<script>
    var topTracks = @json($topTracks);

    // === DRAG SCROLL ===
    (function() {
        var slider = document.getElementById('tracks-slider');
        if (!slider) return;
        var isDown = false, startX, scrollLeft, hasMoved = false;

        slider.addEventListener('mousedown', function(e) {
            isDown = true; hasMoved = false;
            slider.style.cursor = 'grabbing';
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });
        slider.addEventListener('mouseleave', function() { isDown = false; slider.style.cursor = 'grab'; });
        slider.addEventListener('mouseup', function() { isDown = false; slider.style.cursor = 'grab'; });
        slider.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            var x = e.pageX - slider.offsetLeft;
            var walk = (x - startX) * 1.5;
            if (Math.abs(walk) > 5) hasMoved = true;
            slider.scrollLeft = scrollLeft - walk;
        });
        slider.addEventListener('click', function(e) {
            if (hasMoved) { e.stopPropagation(); e.preventDefault(); }
        }, true);

        requestAnimationFrame(function() {
            var cards = slider.querySelectorAll('.track-card');
            if (cards.length > 2) {
                slider.scrollLeft = (cards[0].offsetWidth + 16) * 0.5;
            }
        });
    })();


</script>
@if(session('success') && str_contains(session('success'), 'пожаловать'))
<script>
    try { ym(105879987,'reachGoal','registration'); } catch(e) {}
</script>
@endif
@endpush