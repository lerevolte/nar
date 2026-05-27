@extends('layouts.app')

@section('title', 'Купить песни — На Репите')

@push('styles')
<style>
    .packages-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 24px; }
    .package-card {
        background: var(--bg-card); border: 2px solid var(--border); border-radius: var(--radius-lg);
        padding: 24px; text-align: center; cursor: pointer;
        transition: all var(--duration) var(--ease); box-shadow: var(--shadow-xs); position: relative;
    }
    .package-card:hover { border-color: var(--accent); transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .package-card.selected { border-color: var(--accent); background: var(--accent-soft); }
    .package-card.popular::before {
        content: '🔥 Популярный'; position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
        background: var(--accent); color: white; padding: 3px 12px; border-radius: var(--radius-full);
        font-size: 11px; font-weight: 700;
    }
    .package-songs { font-size: 48px; font-weight: 800; color: var(--accent); letter-spacing: -0.03em; }
    .package-label { font-size: 14px; color: var(--text-secondary); margin-bottom: 12px; }
    .package-price { font-size: 24px; font-weight: 800; }

    .balance-info {
        background: var(--success-soft); border: 1px solid rgba(22,163,74,0.15);
        border-radius: var(--radius-lg); padding: 16px; margin-bottom: 24px; text-align: center;
    }
    .balance-value { font-size: 24px; font-weight: 800; color: var(--success); }

    .contact-section { margin-bottom: 24px; }
    .contact-hint { font-size: 12px; color: var(--text-tertiary); margin-top: 8px; }

    /* Promo section */
    .promo-section {
        margin-bottom: 24px;
        padding: 20px;
        background: var(--bg-input);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
    }
    .promo-title {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .promo-input-row {
        display: flex;
        gap: 8px;
    }
    .promo-input-row input {
        flex: 1;
        text-transform: uppercase;
    }
    .promo-check-btn {
        background: var(--accent);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: var(--radius-md);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: all var(--duration) var(--ease);
    }
    .promo-check-btn:hover { background: var(--accent-hover); }
    .promo-check-btn:disabled { background: var(--text-tertiary); cursor: wait; }

    .promo-result {
        display: none;
        margin-top: 12px;
        padding: 14px;
        border-radius: var(--radius-md);
        animation: slideDown 0.2s var(--ease);
    }
    @keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }

    .promo-result.success {
        background: var(--success-soft);
        border: 1px solid rgba(22,163,74,0.2);
    }
    .promo-result.error {
        background: var(--danger-soft);
        border: 1px solid rgba(225,29,72,0.15);
    }
    .promo-result.info {
        background: var(--accent-soft);
        border: 1px solid rgba(108,92,231,0.2);
    }

    .promo-offer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .promo-offer-info { flex: 1; }
    .promo-offer-songs { font-size: 20px; font-weight: 800; color: var(--accent); }
    .promo-offer-price { font-size: 14px; color: var(--text-secondary); margin-top: 2px; }
    .promo-offer-free { color: var(--success); font-weight: 700; }

    .promo-apply-btn {
        background: var(--success);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: var(--radius-md);
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all var(--duration) var(--ease);
        white-space: nowrap;
    }
    .promo-apply-btn:hover { opacity: 0.9; transform: scale(1.02); }
    .promo-apply-btn:disabled { background: var(--text-tertiary); cursor: wait; }

    .promo-error-text { color: var(--danger); font-size: 14px; font-weight: 500; }
    .promo-success-text { color: var(--success); font-size: 14px; font-weight: 500; }

    .promo-divider {
        text-align: center;
        color: var(--text-tertiary);
        font-size: 13px;
        margin: 20px 0;
        position: relative;
    }
    .promo-divider::before, .promo-divider::after {
        content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: var(--border);
    }
    .promo-divider::before { left: 0; }
    .promo-divider::after { right: 0; }
</style>
@endpush

@section('content')
<div class="card">
    <h2 class="card-title">💰 Купить песни</h2>

    <div class="balance-info">
        <div style="font-size:13px; color:var(--text-secondary); margin-bottom:4px;">Текущий баланс</div>
        <div class="balance-value" id="current-balance">{{ $user->balance }} песен</div>
    </div>

    {{-- Промокод --}}
    <div class="promo-section">
        <div class="promo-title">🎟️ Есть промокод?</div>
        <div class="promo-input-row">
            <input type="text" class="form-input" id="promo-input" placeholder="Введите промокод" maxlength="100" autocomplete="off">
            <button class="promo-check-btn" id="promo-check-btn" onclick="checkPromo()">Проверить</button>
        </div>

        {{-- Результат проверки --}}
        <div class="promo-result" id="promo-result">
            {{-- Заполняется динамически --}}
        </div>
    </div>

    <div class="promo-divider">или выберите пакет</div>

    <div class="packages-grid">
        @foreach($packages as $count => $package)
            <div class="package-card {{ $count == 7 ? 'popular' : '' }}" data-count="{{ $count }}" data-price="{{ $package['price'] }}">
                <div class="package-songs">{{ $count }}</div>
                <div class="package-label">@if($count == 2) песни @else песен @endif</div>
                <div class="package-price">{{ number_format($package['price'], 0, '', ' ') }} ₽</div>
            </div>
        @endforeach
    </div>

    <div class="contact-section">
        <label class="form-label">Email или телефон (для чека)</label>
        <input type="text" class="form-input" id="contact" placeholder="email@example.com или +7 900 000 00 00" value="{{ $user->contact ?? '' }}">
        <div class="contact-hint">Нужно для отправки электронного чека согласно 54-ФЗ</div>
    </div>

    <button class="btn btn-primary btn-block btn-lg" id="pay-btn" disabled>Оплатить</button>
    <div id="error-message" style="display:none; color:var(--danger); margin-top:12px; text-align:center; font-size:14px;"></div>
</div>
@endsection

@push('scripts')
<script>
    let selectedPackage = null;
    let validatedPromo = null; // { id, code, value, songs_amount, needs_payment }

    // === Package selection ===
    document.querySelectorAll('.package-card').forEach(card => {
        card.addEventListener('click', function() {
            // Deselect promo if user picks a package
            validatedPromo = null;
            hidePromoResult();

            document.querySelectorAll('.package-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            selectedPackage = { count: parseInt(this.dataset.count), price: parseInt(this.dataset.price) };
            updatePayButton();
        });
    });

    function updatePayButton() {
        const btn = document.getElementById('pay-btn');
        if (selectedPackage) {
            btn.disabled = false;
            btn.textContent = `Оплатить ${selectedPackage.price} ₽`;
        } else {
            btn.disabled = true;
            btn.textContent = 'Оплатить';
        }
    }

    // === Promo enter key ===
    document.getElementById('promo-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); checkPromo(); }
    });

    // === Check promo ===
    async function checkPromo() {
        const code = document.getElementById('promo-input').value.trim();
        if (!code) return;

        const btn = document.getElementById('promo-check-btn');
        btn.disabled = true; btn.textContent = '...';
        hidePromoResult();
        validatedPromo = null;

        try {
            const r = await fetch('/api/promo/check', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                credentials: 'same-origin',
                body: JSON.stringify({ code }),
            });
            const d = await r.json();

            if (!r.ok) {
                showPromoError(d.error || 'Ошибка');
                return;
            }

            const promo = d.promo;
            const songs = promo.songs_amount || promo.songs_count || 0;
            const price = promo.value || 0;

            if (songs <= 0) {
                showPromoError('Промокод не содержит бонусов');
                return;
            }

            validatedPromo = {
                id: promo.id,
                code: promo.code,
                songs: songs,
                price: price,
            };

            showPromoOffer(songs, price);

            // Deselect packages
            document.querySelectorAll('.package-card').forEach(c => c.classList.remove('selected'));
            selectedPackage = null;
            document.getElementById('pay-btn').disabled = true;
            document.getElementById('pay-btn').textContent = 'Оплатить';

        } catch (e) {
            showPromoError('Ошибка сети');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Проверить';
        }
    }

    // === Show promo offer ===
    function showPromoOffer(songs, price) {
        const el = document.getElementById('promo-result');

        if (price <= 0) {
            // Free promo
            el.className = 'promo-result success';
            el.style.display = 'block';
            el.innerHTML = `
                <div class="promo-offer">
                    <div class="promo-offer-info">
                        <div class="promo-offer-songs">+${songs} песен</div>
                        <div class="promo-offer-price promo-offer-free">Бесплатно!</div>
                    </div>
                    <button class="promo-apply-btn" id="promo-apply-btn" onclick="applyPromo()">Активировать</button>
                </div>
            `;
        } else {
            // Paid promo (discounted)
            el.className = 'promo-result info';
            el.style.display = 'block';
            el.innerHTML = `
                <div class="promo-offer">
                    <div class="promo-offer-info">
                        <div class="promo-offer-songs">+${songs} песен</div>
                        <div class="promo-offer-price">за ${price} ₽</div>
                    </div>
                    <button class="promo-apply-btn" id="promo-apply-btn" onclick="payWithPromo()">Оплатить ${price} ₽</button>
                </div>
            `;
        }
    }

    function showPromoError(msg) {
        const el = document.getElementById('promo-result');
        el.className = 'promo-result error';
        el.style.display = 'block';
        el.innerHTML = `<div class="promo-error-text">❌ ${msg}</div>`;
    }

    function showPromoSuccess(msg) {
        const el = document.getElementById('promo-result');
        el.className = 'promo-result success';
        el.style.display = 'block';
        el.innerHTML = `<div class="promo-success-text">✅ ${msg}</div>`;
    }

    function hidePromoResult() {
        document.getElementById('promo-result').style.display = 'none';
    }

    // === Apply free promo ===
    async function applyPromo() {
        if (!validatedPromo) return;

        const btn = document.getElementById('promo-apply-btn');
        btn.disabled = true; btn.textContent = 'Активируем...';

        try {
            const r = await fetch('/api/promo/apply', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                credentials: 'same-origin',
                body: JSON.stringify({ code: validatedPromo.code }),
            });
            const d = await r.json();

            if (!r.ok) {
                showPromoError(d.error || 'Ошибка');
                return;
            }

            showPromoSuccess(d.message || `+${d.songs_added} песен на балансе!`);
            document.getElementById('current-balance').textContent = d.new_balance + ' песен';
            document.getElementById('promo-input').value = '';
            validatedPromo = null;

        } catch (e) {
            showPromoError('Ошибка сети');
        }
    }

    // === Pay with promo (discounted) ===
    async function payWithPromo() {
        if (!validatedPromo) return;

        const contact = document.getElementById('contact').value.trim();
        const btn = document.getElementById('promo-apply-btn');
        btn.disabled = true; btn.textContent = 'Создание платежа...';

        try {
            const r = await fetch('/api/promo/pay', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                credentials: 'same-origin',
                body: JSON.stringify({ promo_id: validatedPromo.id, contact: contact || null }),
            });
            const d = await r.json();

            if (!r.ok) {
                showPromoError(d.error || 'Ошибка');
                return;
            }

            window.location.href = d.payment_url;

        } catch (e) {
            showPromoError('Ошибка сети');
            btn.disabled = false;
            btn.textContent = `Оплатить ${validatedPromo.price} ₽`;
        }
    }

    // === Regular payment ===
    document.getElementById('pay-btn').addEventListener('click', async function() {
        if (!selectedPackage) return;

        const contact = document.getElementById('contact').value.trim();
        const errorEl = document.getElementById('error-message');
        this.disabled = true; this.textContent = 'Создание платежа...'; errorEl.style.display = 'none';

        try {
            const r = await fetch('/api/payment/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                credentials: 'same-origin',
                body: JSON.stringify({ songs_count: selectedPackage.count, contact: contact || null }),
            });
            const d = await r.json();
            if (!r.ok) throw new Error(d.error || 'Ошибка');
            window.location.href = d.payment_url;
        } catch (e) {
            errorEl.textContent = e.message; errorEl.style.display = 'block';
            this.disabled = false; updatePayButton();
        }
    });

    // Default selection
    document.querySelector('.package-card.popular')?.click();
</script>
@endpush