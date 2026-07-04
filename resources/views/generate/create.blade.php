@extends('layouts.app')

@section('title', 'Создать песню — На Репите')

@push('styles')
<style>
    .generate-container { max-width: 700px; margin: 0 auto; }
    .step { display: none; }
    .step.active { display: block; }

    .options-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
    .option-btn {
        padding: 12px 14px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--bg-card);
        cursor: pointer;
        font-size: 14px;
        text-align: center;
        transition: all var(--duration) var(--ease);
        box-shadow: var(--shadow-xs);
    }
    .option-btn:hover { border-color: var(--accent); }
    .option-btn.selected { border-color: var(--accent); background: var(--accent); color: white; }

    .language-options { display: flex; flex-wrap: wrap; gap: 8px; }
    .language-btn {
        padding: 10px 20px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--bg-card);
        cursor: pointer;
        font-size: 14px;
        transition: all var(--duration) var(--ease);
    }
    .language-btn:hover { border-color: var(--accent); }
    .language-btn.selected { border-color: var(--accent); background: var(--accent); color: white; }

    .lyrics-preview {
        background: var(--bg-input);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        padding: 20px;
        white-space: pre-wrap;
        font-family: inherit;
        line-height: 1.7;
        max-height: 400px;
        overflow-y: auto;
        min-height: 200px;
        font-size: 14px;
    }
    .lyrics-preview:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }

    .translate-row {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 12px;
        padding: 12px;
        background: var(--accent-soft);
        border-radius: var(--radius-md);
    }
    .translate-row select {
        flex: 1;
        padding: 10px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 14px;
        background: var(--bg-card);
        color: var(--text-primary);
    }

    .gender-options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .gender-btn {
        padding: 16px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--bg-card);
        cursor: pointer;
        text-align: center;
        transition: all var(--duration) var(--ease);
        box-shadow: var(--shadow-xs);
    }
    .gender-btn:hover { border-color: var(--accent); }
    .gender-btn.selected { border-color: var(--accent); background: var(--accent); color: white; }
    .gender-btn .icon { font-size: 32px; display: block; margin-bottom: 6px; }
    .gender-btn .sublabel { font-size: 11px; opacity: 0.6; margin-top: 4px; }

    .progress-container { text-align: center; padding: 48px 20px; }
    .progress-spinner {
        width: 64px; height: 64px;
        border: 3px solid var(--border);
        border-top-color: var(--accent);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    .progress-text { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
    .progress-subtext { color: var(--text-secondary); font-size: 14px; }

    .btn-back {
        background: none; border: none; color: var(--text-tertiary);
        cursor: pointer; font-size: 14px; margin-bottom: 16px; padding: 0; font-weight: 500;
    }
    .btn-back:hover { color: var(--text-primary); }

    .success-result { text-align: center; }
    .success-icon { font-size: 64px; margin-bottom: 16px; }

    .artists-grid { display: flex; flex-wrap: wrap; gap: 6px; max-height: 280px; overflow-y: auto; padding: 4px; }
    .artist-chip {
        padding: 7px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-full);
        background: var(--bg-card); cursor: pointer; font-size: 13px;
        transition: all var(--duration) var(--ease); white-space: nowrap;
    }
    .artist-chip:hover { border-color: var(--accent); background: var(--accent-soft); }
    .artist-chip.selected { border-color: var(--accent); background: var(--accent); color: white; }

    .artist-custom-group { padding: 12px; background: var(--bg-input); border-radius: var(--radius-md); }
    .artist-custom-group .form-label { font-size: 13px; margin-bottom: 6px; }

    .step-subtitle { color: var(--text-secondary); margin-bottom: 16px; font-size: 14px; }

    .loading-inline {
        display: inline-block; width: 14px; height: 14px;
        border: 2px solid var(--border); border-top-color: var(--accent);
        border-radius: 50%; animation: spin 0.8s linear infinite; margin-right: 6px; vertical-align: middle;
    }

    .feedback-row { display: flex; gap: 8px; margin-top: 12px; }
    .feedback-row input { flex: 1; }

    .duet-warning {
        background: var(--warning-soft); color: var(--warning);
        padding: 10px 14px; border-radius: var(--radius-sm); font-size: 13px; margin-top: 12px; display: none;
    }

    /* Payment waiting overlay */
    .payment-waiting {
        text-align: center;
        padding: 32px 20px;
    }
    .payment-waiting-icon { font-size: 48px; margin-bottom: 16px; animation: pulse 2s ease-in-out infinite; }
    @keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .payment-waiting-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
    .payment-waiting-text { color: var(--text-secondary); font-size: 14px; margin-bottom: 20px; line-height: 1.6; }
    .payment-waiting-status {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; background: var(--accent-soft); color: var(--accent);
        border-radius: var(--radius-full); font-size: 14px; font-weight: 600;
    }
    .payment-waiting-cancel {
        display: block; margin-top: 16px;
        color: var(--text-tertiary); font-size: 13px; cursor: pointer;
        background: none; border: none;
    }
    .payment-waiting-cancel:hover { color: var(--text-primary); }

    /* Balance warning banner */
    .balance-warning {
        background: var(--warning-soft);
        border: 1px solid rgba(217,119,6,0.2);
        border-radius: var(--radius-md);
        padding: 14px 16px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .balance-warning-text { font-size: 14px; color: var(--warning); font-weight: 500; }
    .balance-warning-btn {
        background: var(--warning); color: white; border: none;
        padding: 8px 16px; border-radius: var(--radius-sm);
        font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .balance-warning-btn:hover { opacity: 0.9; }
    .voice-select-grid { display: flex; flex-direction: column; gap: 8px; }
    .voice-select-btn {
        padding: 14px 16px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--bg-card);
        cursor: pointer;
        font-size: 14px;
        text-align: left;
        transition: all var(--duration) var(--ease);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .voice-select-btn:hover { border-color: var(--accent); }
    .voice-select-btn.selected { border-color: var(--accent); background: var(--accent); color: white; }
    .voice-select-btn .vs-icon { font-size: 24px; }
    .voice-select-btn .vs-name { font-weight: 600; }
    .voice-select-btn .vs-sub { font-size: 12px; opacity: 0.6; }
</style>
@endpush

@section('content')
@php
    $trackOpsAllowedIds = config('services.track_ops.allowed_user_ids', []);
    $trackOpsAllowed = empty($trackOpsAllowedIds)
        || in_array('*', $trackOpsAllowedIds, true)
        || in_array((string) ($authUser->user_id ?? ''), $trackOpsAllowedIds, true);
@endphp
<div class="generate-container">
    @if($trackOpsAllowed)
    <a href="{{ route('studio') }}" style="display:flex;align-items:center;gap:12px;background:var(--bg-card);border:1.5px solid var(--border-accent);border-radius:var(--radius-lg);padding:14px 16px;margin-bottom:16px;text-decoration:none;color:var(--text-primary);box-shadow:var(--shadow-xs);">
        <span style="flex:1;">
            <span style="display:block;font-size:14px;font-weight:700;">Студия: обработать готовое аудио</span>
            <span style="display:block;font-size:12px;color:var(--text-secondary);margin-top:2px;">Кавер, продление, минусовка, вокал, мэшап — из своего файла или трека</span>
        </span>
        <span style="color:var(--accent);font-size:18px;">→</span>
    </a>
    @endif
    <div class="card">
        <div id="error-container" style="display: none;">
            <div class="error-message" id="error-message"></div>
        </div>

        <!-- Шаг 1: Язык -->
        <div class="step active" id="step-language">
            <h2 class="card-title">🌍 На каком языке песня?</h2>
            <div class="language-options" id="language-options">
                @foreach($languages as $code => $name)
                    <button type="button" class="language-btn {{ $code === 'ru' ? 'selected' : '' }}" data-value="{{ $code }}">
                        @if($code === 'ru') 🇷🇺 Русский @elseif($code === 'en') 🇬🇧 English @elseif($code === 'de') 🇩🇪 Deutsch @elseif($code === 'es') 🇪🇸 Español @elseif($code === 'fr') 🇫🇷 Français @elseif($code === 'it') 🇮🇹 Italiano @endif
                    </button>
                @endforeach
            </div>
            <button type="button" class="btn btn-primary btn-block" style="margin-top: 20px;" onclick="confirmLanguage()">Далее →</button>
        </div>

        <!-- Шаг 2: Повод -->
        <div class="step" id="step-occasion">
            <button type="button" class="btn-back" onclick="goToStep('language')">← Назад</button>
            <h2 class="card-title">🎯 Выбери повод</h2>
            <div class="options-grid" id="occasion-options">
                @foreach($occasions as $key => $label)
                    <button type="button" class="option-btn" data-value="{{ $key }}">{{ $label }}</button>
                @endforeach
            </div>
            <div class="form-group" id="custom-occasion-group" style="display: none; margin-top: 16px;">
                <input type="text" class="form-input" id="custom-occasion" placeholder="Введи свой вариант...">
                <button type="button" class="btn btn-primary btn-block" style="margin-top: 12px;" onclick="submitCustomOccasion()">Продолжить</button>
            </div>
        </div>

        <!-- Шаг 3: Жанр -->
        <div class="step" id="step-genre">
            <button type="button" class="btn-back" onclick="goToStep('occasion')">← Назад</button>
            <h2 class="card-title">🎵 Выбери жанр</h2>
            <div class="options-grid" id="genre-options">
                @foreach($genres as $key => $label)
                    <button type="button" class="option-btn" data-value="{{ $key }}">{{ $label }}</button>
                @endforeach
            </div>
            <div class="form-group" id="custom-genre-group" style="display: none; margin-top: 16px;">
                <input type="text" class="form-input" id="custom-genre" placeholder="Введи свой жанр...">
                <button type="button" class="btn btn-primary btn-block" style="margin-top: 12px;" onclick="submitCustomGenre()">Продолжить</button>
            </div>
        </div>

        <!-- Шаг 3.5: Артист -->
        <div class="step" id="step-artist">
            <button type="button" class="btn-back" onclick="goToStep('genre')">← Назад</button>
            <h2 class="card-title">🎤 Артист для вдохновения</h2>
            <p class="step-subtitle">ИИ постарается передать стиль и вайб</p>
            <div class="artists-grid" id="artists-grid"></div>
            <div class="artist-custom-group" style="margin-top: 16px;">
                <label class="form-label">Или введи своего:</label>
                <input type="text" class="form-input" id="custom-artist" placeholder="Imagine Dragons, Макс Корж...">
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="skipArtist()">Пропустить</button>
                <button type="button" class="btn btn-primary" style="flex: 2;" onclick="confirmArtist()">Далее →</button>
            </div>
        </div>

        <!-- Шаг 3.7: Выбор голоса/персоны -->
        @if(!empty($userVoices) || !empty($userPersonas))
        <div class="step" id="step-custom-voice">
            <button type="button" class="btn-back" onclick="goToStep('artist')">← Назад</button>
            <h2 class="card-title">🎙 Выбери голос для песни</h2>
            <p class="step-subtitle">Используй свой голос или стиль из прошлых песен</p>

            <div class="voice-select-grid">
                <button type="button" class="voice-select-btn" onclick="selectPersonaId(this, '')">
                    <span class="vs-icon">🎲</span>
                    <div>
                        <div class="vs-name">Голос ИИ</div>
                        <div class="vs-sub">Стандартная генерация</div>
                    </div>
                </button>

                @foreach($userVoices as $v)
                <button type="button" class="voice-select-btn" onclick="selectPersonaId(this, '{{ $v['voice_id'] }}')">
                    <span class="vs-icon">🎙</span>
                    <div>
                        <div class="vs-name">{{ $v['name'] }}</div>
                        <div class="vs-sub">Клон голоса</div>
                    </div>
                </button>
                @endforeach

                @foreach($userPersonas as $p)
                <button type="button" class="voice-select-btn" onclick="selectPersonaId(this, '{{ $p['persona_id'] }}')">
                    <span class="vs-icon">🎙</span>
                    <div>
                        <div class="vs-name">{{ $p['name'] }}</div>
                        <div class="vs-sub">{{ Str::limit($p['description'], 40) }}</div>
                    </div>
                </button>
                @endforeach
            </div>

            <a href="{{ route('voices') }}" style="display:block;text-align:center;margin-top:12px;font-size:13px;color:var(--text-tertiary);">
                + Управление голосами
            </a>
            <div style="margin-top:16px;padding:14px 16px;background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-md);font-size:13px;color:var(--text-secondary);line-height:1.6;">
                <strong>💡 Как добиться лучшего сходства сохраненного голоса:</strong><br>
                • Записывайте в тихом помещении — без музыки, разговоров и шумов на фоне<br>
                • Используйте чистый вокал без обработки и эффектов (реверб, автотюн)<br>
                • Оптимальная длина записи — 15-30 секунд<br>
                • Выбирайте жанр, близкий к вашей манере пения — если вы записали спокойный голос, а выбрали метал, результат может отличаться<br>
                • Голос лучше передаётся в поп, балладах и акустике — в агрессивных жанрах сходство может снижаться
            </div>
        </div>
        @endif

        <!-- Шаг 4: Голос -->
        <div class="step" id="step-voice-gender">
            <button type="button" class="btn-back" onclick="goToStep(hasCustomVoices ? 'custom-voice' : 'artist')">← Назад</button>
            <h2 class="card-title">🎤 Выбери голос</h2>
            <div class="gender-options">
                <button type="button" class="gender-btn" data-value="m"><span class="icon">👨</span><span>Мужской</span><div class="sublabel">Сольный мужской вокал</div></button>
                <button type="button" class="gender-btn" data-value="f"><span class="icon">👩</span><span>Женский</span><div class="sublabel">Сольный женский вокал</div></button>
                <button type="button" class="gender-btn" data-value="duet"><span class="icon">👫</span><span>Дуэт (М+Ж)</span><div class="sublabel">Мужской и женский</div></button>
                <button type="button" class="gender-btn" data-value="random"><span class="icon">🎲</span><span>Случайный</span><div class="sublabel">На усмотрение ИИ</div></button>
            </div>
            <div class="duet-warning" id="duet-warning">⚠️ Модель не всегда идеально распределяет партии. Иногда голоса могут смешаться.</div>
        </div>

        <!-- Шаг 5: Описание -->
        <div class="step" id="step-description">
            <button type="button" class="btn-back" onclick="goToStep('voice-gender')">← Назад</button>
            <h2 class="card-title">💬 Расскажи о песне</h2>
            <div class="form-group">
                <label class="form-label">Для кого песня? Что хочешь передать?</label>
                <textarea class="form-textarea" id="description" rows="5" placeholder="Песня для друга Саши на день рождения. Ему 30 лет, любит рыбалку и пиво. Хочу весело и с юмором."></textarea>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-primary" style="flex: 2;" onclick="generateLyrics()">✨ Сгенерировать текст</button>
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="goToStep('own-lyrics')">✍️ Свой текст</button>
            </div>
        </div>

        <!-- Шаг 5.5: Свой текст -->
        <div class="step" id="step-own-lyrics">
            <button type="button" class="btn-back" onclick="goToStep('description')">← Назад</button>
            <h2 class="card-title">📝 Вставь свой текст</h2>
            <p class="step-subtitle">Желательно разметить структуру ([Куплет], [Припев]), но не обязательно.</p>
            <div class="form-group">
                <textarea class="form-textarea" id="own-lyrics-input" rows="10" placeholder="Вставьте полный текст песни..."></textarea>
            </div>
            <button type="button" class="btn btn-primary btn-block" onclick="submitOwnLyrics()">Далее →</button>
        </div>

        <!-- Шаг 6: Текст песни -->
        <div class="step" id="step-lyrics">
            <button type="button" class="btn-back" onclick="goToStep('description')">← Назад</button>
            <h2 class="card-title">📝 Текст песни</h2>

            {{-- Баннер нулевого баланса --}}
            <div class="balance-warning" id="balance-warning" style="display: none;">
                <span class="balance-warning-text">💰 Баланс: 0 песен</span>
                <button class="balance-warning-btn" onclick="openPaymentTab()">Купить песни</button>
            </div>

            <div class="form-group">
                <label class="form-label">Название</label>
                <input type="text" class="form-input" id="song-title">
            </div>
            <div class="form-group">
                <label class="form-label">Текст (можно редактировать)</label>
                <div class="lyrics-preview" id="lyrics-preview" contenteditable="true"></div>
            </div>
            <div class="translate-row">
                <span style="font-size:14px;">🌐</span>
                <select id="translate-language">
                    <option value="">Перевести на...</option>
                    @foreach($languages as $code => $name)
                        <option value="{{ $code }}">@if($code === 'ru') 🇷🇺 Русский @elseif($code === 'en') 🇬🇧 English @elseif($code === 'de') 🇩🇪 Deutsch @elseif($code === 'es') 🇪🇸 Español @elseif($code === 'fr') 🇫🇷 Français @elseif($code === 'it') 🇮🇹 Italiano @endif</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-secondary btn-sm" id="translate-btn" onclick="translateLyrics()">Перевести</button>
            </div>
            <div class="feedback-row">
                <input type="text" class="form-input" id="feedback" placeholder="Хочешь что-то изменить?">
                <button type="button" class="btn btn-secondary btn-sm" id="improve-btn" onclick="improveLyrics()">Изменить</button>
            </div>
            <button type="button" class="btn btn-primary btn-block" style="margin-top: 20px;" onclick="startGeneration()">🎵 Сгенерировать песню</button>
        </div>

        <!-- Шаг 7: Генерация -->
        <div class="step" id="step-generating">
            <div class="progress-container">
                <div class="progress-spinner"></div>
                <div class="progress-text" id="progress-text">Генерируем музыку...</div>
                <div class="progress-subtext" id="progress-subtext">Обычно это занимает 2-4 минуты</div>
            </div>
        </div>

        <!-- Шаг 7.5: Ожидание оплаты -->
        <div class="step" id="step-payment-waiting">
            <div class="payment-waiting">
                <div class="payment-waiting-icon">💳</div>
                <div class="payment-waiting-title">Ожидаем оплату...</div>
                <div class="payment-waiting-text">
                    Страница оплаты открыта в новой вкладке.<br>
                    После оплаты генерация запустится автоматически.
                </div>
                <div class="payment-waiting-status">
                    <span class="loading-inline"></span>
                    <span id="payment-status-text">Проверяем баланс...</span>
                </div>
                <button class="payment-waiting-cancel" onclick="cancelPaymentWaiting()">Отмена — вернуться к тексту</button>
            </div>
        </div>

        <!-- Шаг 8: Результат -->
        <div class="step" id="step-result">
            <div class="success-result">
                <div class="success-icon">🎉</div>
                <h2>Песня готова!</h2>
                <p style="color: var(--text-secondary); margin-bottom: 24px;">Два варианта на выбор</p>
                <a href="#" id="result-link" class="btn btn-primary btn-block btn-lg">Открыть песню</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const genreArtists = @json($genreArtists);
    const userBalance = {{ $authUser->balance ?? 0 }};
    const formData = { language:'ru', occasion:'', genre:'', genreKey:'', artist:'', vocalGender:'random', customPersonaId:'', description:'', title:'', lyrics:'' };
    const hasCustomVoices = {{ (!empty($userVoices) || !empty($userPersonas)) ? 'true' : 'false' }};
    let currentStep = 'language';
    let selectedArtistChip = null;
    let paymentCheckInterval = null;

    // ==============================
    // AUTO-RESUME: check on page load
    // ==============================
    document.addEventListener('DOMContentLoaded', function() {
        const pending = localStorage.getItem('pending_generation');
        if (pending && userBalance > 0) {
            try {
                const saved = JSON.parse(pending);
                localStorage.removeItem('pending_generation');
                // Restore form data
                Object.assign(formData, saved);
                // Auto-launch generation
                goToStep('generating');
                document.getElementById('progress-text').textContent = 'Оплата прошла! Генерируем...';
                document.getElementById('progress-subtext').textContent = 'Запускаем создание песни';
                generateMusicDirect();
            } catch(e) {
                console.error('Failed to restore pending generation', e);
                localStorage.removeItem('pending_generation');
            }
        }
    });

    function goToStep(step) {
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById('step-' + step).classList.add('active');
        currentStep = step;
        hideError();
        // Show balance warning on lyrics step
        if (step === 'lyrics') {
            document.getElementById('balance-warning').style.display = userBalance < 1 ? 'flex' : 'none';
        }
    }
    function showError(msg) { document.getElementById('error-message').textContent = msg; document.getElementById('error-container').style.display = 'block'; }
    function hideError() { document.getElementById('error-container').style.display = 'none'; }

    // Language
    document.querySelectorAll('#language-options .language-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#language-options .language-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            formData.language = this.dataset.value;
        });
    });
    function confirmLanguage() { goToStep('occasion'); }

    // Occasion
    document.querySelectorAll('#occasion-options .option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const value = this.dataset.value;
            document.querySelectorAll('#occasion-options .option-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            if (value === 'custom') { document.getElementById('custom-occasion-group').style.display = 'block'; }
            else { document.getElementById('custom-occasion-group').style.display = 'none'; formData.occasion = this.textContent.trim(); goToStep('genre'); }
        });
    });
    function submitCustomOccasion() { const v = document.getElementById('custom-occasion').value.trim(); if (v) { formData.occasion = v; goToStep('genre'); } }

    // Genre
    document.querySelectorAll('#genre-options .option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const value = this.dataset.value;
            document.querySelectorAll('#genre-options .option-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            if (value === 'custom') { document.getElementById('custom-genre-group').style.display = 'block'; }
            else { document.getElementById('custom-genre-group').style.display = 'none'; formData.genre = this.textContent.trim(); formData.genreKey = value; showArtistsForGenre(value); goToStep('artist'); }
        });
    });
    function submitCustomGenre() { const v = document.getElementById('custom-genre').value.trim(); if (v) { formData.genre = v; formData.genreKey = 'custom'; showArtistsForGenre('custom'); goToStep('artist'); } }

    // Artists
    function showArtistsForGenre(key) {
        const c = document.getElementById('artists-grid'); c.innerHTML = ''; selectedArtistChip = null;
        const artists = genreArtists[key] || [];
        if (!artists.length) { c.innerHTML = '<p style="color:var(--text-tertiary);font-size:13px;">Нет предложений для этого жанра.</p>'; return; }
        artists.forEach(a => {
            const chip = document.createElement('button');
            chip.type = 'button'; chip.className = 'artist-chip'; chip.textContent = a;
            chip.addEventListener('click', function() {
                if (selectedArtistChip) selectedArtistChip.classList.remove('selected');
                this.classList.add('selected'); selectedArtistChip = this; formData.artist = a;
                document.getElementById('custom-artist').value = '';
            });
            c.appendChild(chip);
        });
    }
    function skipArtist() {
        formData.artist = '';
        if (hasCustomVoices) { goToStep('custom-voice'); }
        else { goToStep('voice-gender'); }
    }
    function confirmArtist() {
        const custom = document.getElementById('custom-artist').value.trim();
        if (custom) formData.artist = custom;
        if (hasCustomVoices) { goToStep('custom-voice'); }
        else { goToStep('voice-gender'); }
    }

    // Voice Gender
    document.querySelectorAll('.gender-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.gender-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            formData.vocalGender = this.dataset.value;
            document.getElementById('duet-warning').style.display = this.dataset.value === 'duet' ? 'block' : 'none';
            setTimeout(() => goToStep('description'), 300);
        });
    });

    // Own lyrics
    function submitOwnLyrics() {
        const text = document.getElementById('own-lyrics-input').value.trim();
        if (!text) { showError('Вставьте текст песни'); return; }
        formData.lyrics = text; formData.description = 'Свой текст';
        const lines = text.split('\n'); const firstLine = lines[0].trim();
        formData.title = (firstLine && firstLine.length < 50 && !firstLine.startsWith('[')) ? firstLine : 'Моя песня';
        document.getElementById('song-title').value = formData.title;
        document.getElementById('lyrics-preview').textContent = text;
        goToStep('lyrics');
    }

    // Generate lyrics
    async function generateLyrics() {
        formData.description = document.getElementById('description').value.trim();
        if (!formData.description) { showError('Опиши идею песни'); return; }
        goToStep('generating');
        document.getElementById('progress-text').textContent = 'Пишем текст...';
        document.getElementById('progress-subtext').textContent = 'ИИ сочиняет песню';
        try {
            const r = await fetch('/api/generate/lyrics', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, credentials:'same-origin',
                body: JSON.stringify({ occasion:formData.occasion, genre:formData.genre, description:formData.description, language:formData.language, artist:formData.artist, vocal_gender:formData.vocalGender }) });
            const d = await r.json();
            if (!r.ok) throw new Error(d.error || 'Ошибка генерации');
            formData.title = d.title; formData.lyrics = d.lyrics;
            document.getElementById('song-title').value = d.title;
            document.getElementById('lyrics-preview').textContent = d.display_lyrics || d.lyrics;
            goToStep('lyrics');
        } catch (e) { goToStep('description'); showError(e.message); }
    }

    // Translate
    async function translateLyrics() {
        const lang = document.getElementById('translate-language').value;
        if (!lang) { showError('Выберите язык'); return; }
        const btn = document.getElementById('translate-btn'); btn.disabled = true; btn.innerHTML = '<span class="loading-inline"></span>';
        try {
            const r = await fetch('/api/generate/translate', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, credentials:'same-origin',
                body: JSON.stringify({ lyrics: document.getElementById('lyrics-preview').textContent, target_language: lang }) });
            const d = await r.json();
            if (!r.ok) throw new Error(d.error || 'Ошибка');
            document.getElementById('lyrics-preview').textContent = d.lyrics; formData.lyrics = d.lyrics; formData.language = lang;
        } catch (e) { showError(e.message); }
        finally { btn.disabled = false; btn.textContent = 'Перевести'; }
    }

    // Improve
    async function improveLyrics() {
        const fb = document.getElementById('feedback').value.trim(); if (!fb) return;
        const btn = document.getElementById('improve-btn'); btn.disabled = true; btn.innerHTML = '<span class="loading-inline"></span>';
        try {
            const r = await fetch('/api/generate/improve', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, credentials:'same-origin',
                body: JSON.stringify({ lyrics: document.getElementById('lyrics-preview').textContent, feedback: fb, artist: formData.artist, vocal_gender: formData.vocalGender }) });
            const d = await r.json();
            if (!r.ok) throw new Error(d.error || 'Ошибка');
            document.getElementById('song-title').value = d.title;
            document.getElementById('lyrics-preview').textContent = d.display_lyrics || d.lyrics;
            document.getElementById('feedback').value = '';
            formData.title = d.title; formData.lyrics = d.lyrics;
        } catch (e) { showError(e.message); }
        finally { btn.disabled = false; btn.textContent = 'Изменить'; }
    }

    function startGeneration() {
        formData.title = document.getElementById('song-title').value;
        generateMusic();
    }

    // ==============================
    // PAYMENT FLOW
    // ==============================
    function openPaymentTab() {
        window.open('/buy', '_blank');
    }

    function handleNeedPayment() {
        // Save generation data to localStorage
        formData.title = document.getElementById('song-title')?.value || formData.title;
        localStorage.setItem('pending_generation', JSON.stringify({
            title: formData.title,
            lyrics: formData.lyrics,
            genre: formData.genre,
            artist: formData.artist,
            vocalGender: formData.vocalGender,
        }));

        // Show payment waiting step
        goToStep('payment-waiting');

        // Open payment in new tab
        window.open('/buy', '_blank');

        // Start polling balance
        let pollCount = 0;
        paymentCheckInterval = setInterval(async () => {
            pollCount++;
            if (pollCount > 120) { // 10 min max
                clearInterval(paymentCheckInterval);
                document.getElementById('payment-status-text').textContent = 'Время ожидания истекло';
                return;
            }
            try {
                const r = await fetch('/api/user', { headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, credentials: 'same-origin' });
                const d = await r.json();
                if (d.balance > 0) {
                    clearInterval(paymentCheckInterval);
                    document.getElementById('payment-status-text').textContent = '✅ Оплата получена!';
                    // Auto-launch generation
                    const saved = localStorage.getItem('pending_generation');
                    if (saved) {
                        const data = JSON.parse(saved);
                        Object.assign(formData, data);
                        localStorage.removeItem('pending_generation');
                    }
                    setTimeout(() => {
                        goToStep('generating');
                        document.getElementById('progress-text').textContent = 'Оплата прошла! Генерируем...';
                        generateMusicDirect();
                    }, 1000);
                }
            } catch(e) { console.error('Balance check error', e); }
        }, 5000); // every 5 sec
    }

    function cancelPaymentWaiting() {
        if (paymentCheckInterval) clearInterval(paymentCheckInterval);
        localStorage.removeItem('pending_generation');
        goToStep('lyrics');
    }

    // ==============================
    // GENERATE MUSIC
    // ==============================
    async function generateMusic() {
        goToStep('generating');
        document.getElementById('progress-text').textContent = 'Генерируем музыку...';
        document.getElementById('progress-subtext').textContent = 'Это займёт 2-4 минуты';
        try {
            const r = await fetch('/api/generate/music', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, credentials:'same-origin',
                body: JSON.stringify({ title: formData.title, lyrics: formData.lyrics, genre: formData.genre, artist: formData.artist || '', vocal_gender: formData.vocalGender, persona_id: formData.customPersonaId || '' }) });
            const d = await r.json();
            if (!r.ok) {
                if (d.need_payment) {
                    handleNeedPayment();
                    return;
                }
                if (d.voice_expired) {
                    goToStep('custom-voice');
                    showError(d.error);
                    return;
                }
                throw new Error(d.error || 'Ошибка');
            }
            pollStatus(d.task_id, d.song_id);
        } catch (e) { goToStep('lyrics'); showError(e.message); }
    }

    // Direct generation (after payment, no need_payment check)
    async function generateMusicDirect() {
        try {
            const r = await fetch('/api/generate/music', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, credentials:'same-origin',
                body: JSON.stringify({ title: formData.title, lyrics: formData.lyrics, genre: formData.genre, artist: formData.artist || '', vocal_gender: formData.vocalGender, persona_id: formData.customPersonaId || '' }) });
            const d = await r.json();
            if (!r.ok) {
                if (d.need_payment) {
                    handleNeedPayment();
                    return;
                }
                if (d.voice_expired) {
                    goToStep('custom-voice');
                    showError(d.error);
                    return;
                }
                throw new Error(d.error || 'Ошибка');
            }
            document.getElementById('progress-text').textContent = 'Генерируем музыку...';
            document.getElementById('progress-subtext').textContent = 'Это займёт 2-4 минуты';
            pollStatus(d.task_id, d.song_id);
        } catch (e) { goToStep('lyrics'); showError(e.message); }
    }

    async function pollStatus(taskId, songId) {
        const max = 60; let attempts = 0;
        const iv = setInterval(async () => {
            attempts++;
            if (attempts > max) { clearInterval(iv); showError('Генерация заняла слишком долго.'); goToStep('lyrics'); return; }
            try {
                const r = await fetch(`/api/generate/status?task_id=${taskId}&song_id=${songId}`, { headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}, credentials:'same-origin' });
                const d = await r.json();
                if (d.status === 'completed') { clearInterval(iv); document.getElementById('result-link').href = `/songs/${songId}`; goToStep('result'); }
                else if (d.status === 'failed') { clearInterval(iv); showError(d.error || 'Ошибка генерации'); goToStep('lyrics'); }
                else { const msgs = ['Создаём мелодию...','Синтезируем вокал...','Сводим трек...','Финальные штрихи...']; document.getElementById('progress-text').textContent = msgs[Math.min(Math.floor(attempts/10), msgs.length-1)]; }
            } catch (e) { console.error(e); }
        }, 5000);
    }

    function selectPersonaId(btn, personaId) {
        document.querySelectorAll('.voice-select-btn').forEach(function(b) { b.classList.remove('selected'); });
        btn.classList.add('selected');
        formData.customPersonaId = personaId;

        if (personaId) {
            // Свой голос/персона — пропускаем gender, сразу к описанию
            setTimeout(function() { goToStep('description'); }, 300);
        } else {
            // Голос ИИ — к выбору gender
            setTimeout(function() { goToStep('voice-gender'); }, 300);
        }
    }
</script>
@endpush