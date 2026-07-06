@extends('layouts.public')

@section('title', 'Создать песню через нейросеть онлайн — сгенерировать музыку | На Репите')

@section('meta')
    <meta name="description" content="Создайте свою песню за 2 минуты: введите текст — нейросеть сгенерирует музыку онлайн в высоком качестве, вокал и аранжировку. Полностью на русском языке, высокое качество, большой выбор жанров, голосов и исполнителей!">
    <meta property="og:title" content="Создай песню за 2 минуты">
    <meta property="og:description" content="ИИ создаст уникальную песню под твой повод. Любой стиль. ">
@endsection
@section('jsonld')
    @include('partials.seo.json-ld', ['include' => ['organization', 'webapp', 'best-songs']])
@endsection

@push('styles')
<style>
    /* ============ HERO ============ */
    .pg-hero {
        position: relative;
        padding: 60px 20px 40px;
        text-align: center;
        overflow: hidden;
        background: linear-gradient(135deg, #0f0a24 0%, #1a0f3d 40%, #2d1b5e 100%);
        color: white;
        border-radius: 0 0 32px 32px;
    }
    .pg-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: 
            radial-gradient(circle at 20% 30%, rgba(168,85,247,0.4), transparent 40%),
            radial-gradient(circle at 80% 70%, rgba(59,130,246,0.3), transparent 40%),
            radial-gradient(circle at 50% 100%, rgba(236,72,153,0.3), transparent 50%);
        pointer-events: none;
    }
    .pg-hero-inner { position: relative; z-index: 1; max-width: 720px; margin: 0 auto; }
    .pg-hero-badge {
        display: inline-block;
        padding: 6px 16px;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
        letter-spacing: 0.5px;
    }
    .pg-hero h1 {
        font-size: clamp(32px, 6vw, 56px);
        font-weight: 800;
        line-height: 1.05;
        letter-spacing: -0.02em;
        margin-bottom: 18px;
    }
    .pg-hero-gradient-text {
        background: linear-gradient(90deg, #c084fc, #f472b6, #fbbf24);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .pg-hero p {
        font-size: 18px;
        opacity: 0.85;
        line-height: 1.5;
        margin-bottom: 28px;
    }
    .pg-hero-stats {
        display: flex; justify-content: center; gap: 32px;
        margin-top: 32px; flex-wrap: wrap;
    }
    .pg-hero-stat { text-align: center; }
    .pg-hero-stat-value {
        font-size: 28px; font-weight: 800;
        background: linear-gradient(90deg, #c084fc, #f472b6);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .pg-hero-stat-label { font-size: 13px; opacity: 0.7; margin-top: 2px; }

    /* ============ WIZARD ============ */
    .pg-wizard {
        max-width: 720px;
        margin: -40px auto 60px;
        background: white;
        border-radius: 24px;
        padding: 32px;
        box-shadow: 0 20px 60px rgba(15,10,36,0.15), 0 4px 16px rgba(0,0,0,0.04);
        position: relative;
        z-index: 10;
    }
    @media (max-width: 640px) {
        .pg-wizard { margin: -24px 16px 40px; padding: 24px 20px; border-radius: 20px; }
    }

    .pg-progress { display: flex; gap: 4px; margin-bottom: 28px; }
    .pg-progress-step {
        flex: 1; height: 4px;
        background: #e5e7eb; border-radius: 2px;
        transition: background 0.3s ease;
    }
    .pg-progress-step.done, .pg-progress-step.active {
        background: linear-gradient(90deg, #a855f7, #ec4899);
    }

    .pg-step { display: none; animation: pgFadeIn 0.3s ease; }
    .pg-step.active { display: block; }
    @keyframes pgFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .pg-step-label {
        font-size: 12px; font-weight: 700;
        color: #a855f7; text-transform: uppercase;
        letter-spacing: 1.2px; margin-bottom: 6px;
    }
    .pg-step-title {
        font-size: 26px; font-weight: 800;
        color: #0f0a24; line-height: 1.2;
        margin-bottom: 8px; letter-spacing: -0.02em;
    }
    .pg-step-subtitle {
        font-size: 15px; color: #6b7280;
        margin-bottom: 24px; line-height: 1.5;
    }

    .pg-lang-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .pg-lang-chip {
        padding: 10px 18px;
        border: 2px solid #e5e7eb;
        border-radius: 100px;
        background: white; cursor: pointer;
        font-size: 14px; font-weight: 600;
        color: #374151;
        transition: all 0.2s ease;
    }
    .pg-lang-chip:hover { border-color: #a855f7; transform: translateY(-1px); }
    .pg-lang-chip.selected {
        background: linear-gradient(135deg, #a855f7, #ec4899);
        color: white; border-color: transparent;
        box-shadow: 0 4px 16px rgba(168,85,247,0.3);
    }

    .pg-options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
    }
    .pg-option {
        padding: 16px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 14px;
        background: white; cursor: pointer;
        font-size: 14px; font-weight: 600;
        text-align: center; transition: all 0.2s ease;
        color: #374151;
    }
    .pg-option:hover {
        border-color: #a855f7; transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(168,85,247,0.1);
    }
    .pg-option.selected {
        border-color: #a855f7;
        background: linear-gradient(135deg, #faf5ff, #fdf2f8);
        color: #7c3aed;
        box-shadow: 0 4px 16px rgba(168,85,247,0.15);
    }

    .pg-artists-grid {
        display: flex; flex-wrap: wrap; gap: 8px;
        max-height: 240px; overflow-y: auto; padding: 4px;
    }
    .pg-artist-chip {
        padding: 8px 14px;
        border: 1.5px solid #e5e7eb;
        border-radius: 100px;
        background: white; cursor: pointer;
        font-size: 13px; font-weight: 500;
        color: #4b5563; white-space: nowrap;
        transition: all 0.15s ease;
    }
    .pg-artist-chip:hover { border-color: #a855f7; background: #faf5ff; }
    .pg-artist-chip.selected {
        background: #a855f7; color: white; border-color: #a855f7;
    }

    .pg-gender-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
    }
    .pg-gender-card {
        padding: 20px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 16px;
        background: white; cursor: pointer;
        text-align: center; transition: all 0.2s ease;
    }
    .pg-gender-card:hover { border-color: #a855f7; transform: translateY(-2px); }
    .pg-gender-card.selected {
        border-color: #a855f7;
        background: linear-gradient(135deg, #faf5ff, #fdf2f8);
        box-shadow: 0 4px 16px rgba(168,85,247,0.15);
    }
    .pg-gender-icon { font-size: 36px; display: block; margin-bottom: 6px; }
    .pg-gender-title { font-size: 14px; font-weight: 700; color: #0f0a24; }
    .pg-gender-sub { font-size: 11px; color: #9ca3af; margin-top: 2px; }

    .pg-textarea, .pg-input {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 15px;
        font-family: inherit;
        transition: border-color 0.2s ease;
        background: #fafafa;
    }
    .pg-textarea { padding: 16px; border-radius: 14px; resize: vertical; }
    .pg-textarea:focus, .pg-input:focus {
        outline: none; border-color: #a855f7; background: white;
        box-shadow: 0 0 0 4px rgba(168,85,247,0.08);
    }

    .pg-btn-row { display: flex; gap: 10px; margin-top: 24px; }
    .pg-btn {
        padding: 14px 24px;
        border: none;
        border-radius: 12px;
        font-size: 15px; font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: inherit;
    }
    .pg-btn-primary {
        background: linear-gradient(135deg, #a855f7, #ec4899);
        color: white; flex: 1;
        box-shadow: 0 4px 16px rgba(168,85,247,0.3);
    }
    .pg-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(168,85,247,0.4);
    }
    .pg-btn-primary:disabled {
        background: #d1d5db; cursor: not-allowed;
        box-shadow: none; transform: none;
    }
    .pg-btn-secondary {
        background: #f3f4f6; color: #4b5563;
    }
    .pg-btn-secondary:hover { background: #e5e7eb; }

    .pg-back-btn {
        background: none; border: none;
        color: #9ca3af; font-size: 14px;
        cursor: pointer; padding: 0;
        margin-bottom: 16px; font-weight: 500;
    }
    .pg-back-btn:hover { color: #4b5563; }

    .pg-duet-warn {
        margin-top: 12px; padding: 10px 14px;
        background: #fef3c7; color: #92400e;
        border-radius: 10px; font-size: 13px; display: none;
    }

    /* ============ LOADING SCREEN ============ */
    .pg-loading {
        text-align: center; padding: 40px 20px;
    }
    .pg-loading-orb {
        width: 80px; height: 80px;
        margin: 0 auto 24px;
        border-radius: 50%;
        background: conic-gradient(from 0deg, #a855f7, #ec4899, #fbbf24, #a855f7);
        animation: pgOrb 2s linear infinite;
        position: relative;
    }
    .pg-loading-orb::after {
        content: '';
        position: absolute; inset: 8px;
        background: white;
        border-radius: 50%;
    }
    .pg-loading-orb-inner {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 32px;
        z-index: 2;
    }
    @keyframes pgOrb { to { transform: rotate(360deg); } }
    .pg-loading-title {
        font-size: 20px; font-weight: 800;
        color: #0f0a24; margin-bottom: 8px;
    }
    .pg-loading-text {
        color: #6b7280; font-size: 14px; line-height: 1.5;
    }
    .pg-loading-status {
        margin-top: 16px;
        font-size: 13px; color: #a855f7; font-weight: 600;
    }

    /* ============ LYRICS PREVIEW ============ */
    .pg-lyrics-title-input {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 700;
        font-family: inherit;
        text-align: center;
        background: #fafafa;
        margin-bottom: 12px;
        transition: all 0.2s ease;
    }
    .pg-lyrics-title-input:focus {
        outline: none; border-color: #a855f7; background: white;
    }

    .pg-lyrics-box {
        background: linear-gradient(135deg, #faf5ff 0%, #fdf2f8 100%);
        border: 2px solid #e9d5ff;
        border-radius: 16px;
        padding: 24px;
        font-size: 15px;
        line-height: 1.8;
        color: #1f2937;
        white-space: pre-wrap;
        min-height: 200px;
        max-height: 420px;
        overflow-y: auto;
        font-family: inherit;
        transition: border-color 0.2s ease;
    }
    .pg-lyrics-box:focus {
        outline: none;
        border-color: #a855f7;
        box-shadow: 0 0 0 4px rgba(168,85,247,0.08);
    }

    .pg-lyrics-actions {
        display: flex; gap: 8px; flex-wrap: wrap;
        margin-top: 16px;
    }
    .pg-action-btn {
        flex: 1; min-width: 140px;
        padding: 10px 14px;
        background: white;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        font-size: 13px; font-weight: 600;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: inherit;
        display: flex; align-items: center; justify-content: center;
        gap: 6px;
    }
    .pg-action-btn:hover {
        border-color: #a855f7; color: #a855f7;
        background: #faf5ff;
    }
    .pg-action-btn:disabled {
        opacity: 0.5; cursor: wait;
    }

    /* Улучшение и перевод — скрываемые панели */
    .pg-inline-panel {
        display: none;
        margin-top: 12px;
        padding: 14px;
        background: #faf5ff;
        border-radius: 12px;
        border: 1.5px solid #e9d5ff;
    }
    .pg-inline-panel.open { display: block; }
    .pg-inline-row {
        display: flex; gap: 8px;
    }
    .pg-inline-row .pg-input { flex: 1; background: white; }
    .pg-inline-row .pg-input,
    .pg-inline-row select {
        padding: 10px 12px; font-size: 14px;
    }
    .pg-inline-select {
        padding: 10px 12px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        background: white;
        font-size: 14px;
        font-family: inherit;
    }
    .pg-inline-submit {
        padding: 10px 18px;
        background: #a855f7;
        color: white; border: none;
        border-radius: 10px;
        font-size: 13px; font-weight: 700;
        cursor: pointer; white-space: nowrap;
    }
    .pg-inline-submit:hover { background: #9333ea; }
    .pg-inline-submit:disabled { background: #d1d5db; cursor: wait; }

    /* CTA под текстом */
    .pg-cta-section {
        margin-top: 24px;
        padding: 20px;
        background: linear-gradient(135deg, #0f0a24, #2d1b5e);
        border-radius: 16px;
        text-align: center;
        color: white;
    }
    .pg-cta-title {
        font-size: 18px; font-weight: 800;
        margin-bottom: 6px;
    }
    .pg-cta-sub {
        font-size: 13px; opacity: 0.8;
        margin-bottom: 16px;
    }
    .pg-cta-btn {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #0f0a24;
        border: none;
        border-radius: 12px;
        font-size: 16px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(251,191,36,0.3);
        transition: all 0.2s ease;
        font-family: inherit;
    }
    .pg-cta-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(251,191,36,0.4);
    }
    .pg-cta-price {
        font-size: 24px; font-weight: 900;
        margin-right: 8px;
    }

    /* Trust row */
    .pg-trust {
        max-width: 720px; margin: 40px auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    .pg-trust-item {
        display: flex; gap: 12px; align-items: flex-start;
        padding: 16px;
        background: white;
        border-radius: 16px;
        border: 1px solid #f0f0f5;
    }
    .pg-trust-icon { font-size: 28px; line-height: 1; flex-shrink: 0; }
    .pg-trust-title { font-size: 14px; font-weight: 700; color: #0f0a24; margin-bottom: 2px; }
    .pg-trust-text { font-size: 13px; color: #6b7280; line-height: 1.4; }

    .pg-error {
        padding: 12px 14px;
        background: #fee2e2; color: #991b1b;
        border-radius: 10px;
        font-size: 14px; margin-bottom: 16px;
        display: none;
    }
    /* ============ PAYMENT STEP ============ */
    .pg-payment-summary {
        background: linear-gradient(135deg, #faf5ff, #fdf2f8);
        border: 1.5px solid #e9d5ff;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 24px;
    }
    .pg-payment-summary-title {
        font-size: 13px; font-weight: 700;
        color: #7c3aed; text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 10px;
    }
    .pg-payment-summary-row {
        display: flex; justify-content: space-between;
        font-size: 14px; padding: 6px 0;
        border-bottom: 1px dashed rgba(124,58,237,0.15);
    }
    .pg-payment-summary-row:last-child { border: none; }
    .pg-payment-summary-row b { color: #0f0a24; }
    .pg-payment-summary-row span { color: #6b7280; }

    .pg-contact-group {
        margin-bottom: 14px;
    }
    .pg-contact-label {
        display: block;
        font-size: 13px; font-weight: 600;
        color: #374151; margin-bottom: 6px;
    }
    .pg-contact-hint {
        font-size: 12px; color: #9ca3af;
        margin-top: 6px; line-height: 1.4;
    }
    .pg-price-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px;
        background: linear-gradient(135deg, #0f0a24, #2d1b5e);
        color: white;
        border-radius: 14px;
        margin: 20px 0 16px;
    }
    .pg-price-label {
        font-size: 14px; opacity: 0.8;
    }
    .pg-price-value {
        font-size: 28px; font-weight: 900;
        background: linear-gradient(90deg, #fbbf24, #f59e0b);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .pg-pay-btn {
        width: 100%;
        padding: 18px 24px;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #0f0a24;
        border: none;
        border-radius: 14px;
        font-size: 16px; font-weight: 800;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: inherit;
        box-shadow: 0 4px 20px rgba(251,191,36,0.3);
    }
    .pg-pay-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(251,191,36,0.4);
    }
    .pg-pay-btn:disabled {
        background: #d1d5db; color: #6b7280;
        cursor: wait;
        box-shadow: none; transform: none;
    }
    .pg-legal {
        font-size: 11px; color: #9ca3af;
        text-align: center;
        line-height: 1.5;
        margin-top: 12px;
    }
    .pg-legal a { color: #7c3aed; text-decoration: underline; }

    .pg-trust-badges {
        display: flex; gap: 12px;
        justify-content: center;
        margin: 16px 0;
        flex-wrap: wrap;
    }
    .pg-trust-badge {
        display: flex; align-items: center; gap: 6px;
        padding: 6px 12px;
        background: #f9fafb;
        border-radius: 100px;
        font-size: 12px;
        color: #6b7280;
        font-weight: 600;
    }
    /* ============ SOCIAL PROOF: EXAMPLES ============ */
    .pg-examples {
        max-width: 980px;
        margin: 60px auto 0;
        padding: 0 20px;
    }
    .pg-examples-head {
        text-align: center;
        margin-bottom: 32px;
    }
    .pg-examples-head h2 {
        font-size: 28px;
        font-weight: 800;
        color: #0f0a24;
        margin-bottom: 8px;
    }
    .pg-examples-head p {
        color: #6b7280;
        font-size: 15px;
    }
    .pg-examples-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    .pg-example-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 4px 24px rgba(124, 58, 237, 0.06);
        border: 1px solid rgba(124, 58, 237, 0.08);
        transition: transform 0.2s ease;
    }
    .pg-example-card:hover { transform: translateY(-4px); }
    .pg-example-cover {
        width: 100%;
        aspect-ratio: 1;
        border-radius: 14px;
        background: linear-gradient(135deg, #a855f7, #ec4899);
        display: flex; align-items: center; justify-content: center;
        font-size: 48px; color: white;
        margin-bottom: 14px;
        overflow: hidden;
        position: relative;
    }
    .pg-example-cover img { width: 100%; height: 100%; object-fit: cover; }
    .pg-example-cover .pg-example-play {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.4);
        display: flex; align-items: center; justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
        font-size: 56px;
        cursor: pointer;
    }
    .pg-example-card:hover .pg-example-play { opacity: 1; }
    .pg-example-title {
        font-size: 16px; font-weight: 700;
        color: #0f0a24; margin-bottom: 4px;
    }
    .pg-example-meta {
        font-size: 13px; color: #6b7280;
        margin-bottom: 10px;
    }
    .pg-example-audio {
        width: 100%;
        height: 36px;
        border-radius: 8px;
    }

    /* ============ REVIEWS ============ */
    .pg-reviews {
        max-width: 980px;
        margin: 60px auto 0;
        padding: 0 20px;
    }
    .pg-reviews-head {
        text-align: center;
        margin-bottom: 32px;
    }
    .pg-reviews-head h2 {
        font-size: 28px; font-weight: 800;
        color: #0f0a24; margin-bottom: 8px;
    }
    .pg-reviews-stars {
        font-size: 22px;
        margin: 8px 0;
    }
    .pg-reviews-count {
        color: #6b7280; font-size: 14px;
    }
    .pg-reviews-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    .pg-review-card {
        background: white;
        border-radius: 20px;
        padding: 22px;
        border: 1px solid rgba(124, 58, 237, 0.08);
        position: relative;
    }
    .pg-review-quote {
        color: #374151;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 16px;
    }
    .pg-review-author {
        display: flex; align-items: center; gap: 12px;
    }
    .pg-review-avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700;
        font-size: 16px;
    }
    .pg-review-name { font-size: 14px; font-weight: 700; color: #0f0a24; }
    .pg-review-date { font-size: 12px; color: #9ca3af; }

    /* ============ COMPARISON TABLE ============ */
    .pg-compare {
        max-width: 980px;
        margin: 60px auto 60px;
        padding: 0 20px;
    }
    .pg-compare-head {
        text-align: center;
        margin-bottom: 32px;
    }
    .pg-compare-head h2 {
        font-size: 28px; font-weight: 800;
        color: #0f0a24; margin-bottom: 8px;
    }
    .pg-compare-table {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(124, 58, 237, 0.06);
        border: 1px solid rgba(124, 58, 237, 0.08);
        overflow-x: auto;
    }
    .pg-compare-table table {
        width: 100%;
        border-collapse: collapse;
        min-width: 520px;
    }
    .pg-compare-table th,
    .pg-compare-table td {
        padding: 14px 16px;
        text-align: center;
        font-size: 14px;
        border-bottom: 1px solid #f3f4f6;
    }
    .pg-compare-table th {
        background: #f9fafb;
        font-weight: 700;
        color: #0f0a24;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .pg-compare-table th.us {
        background: linear-gradient(135deg, #a855f7, #ec4899);
        color: white;
    }
    .pg-compare-table td:first-child {
        text-align: left;
        font-weight: 600;
        color: #374151;
    }
    .pg-compare-table td.us {
        background: linear-gradient(180deg, #faf5ff 0%, white 100%);
        font-weight: 700;
        color: #7c3aed;
    }
    .pg-compare-yes { color: #10b981; font-size: 20px; }
    .pg-compare-no { color: #ef4444; font-size: 20px; }

    .pg-final-cta {
        text-align: center;
        margin: 48px 20px 60px;
    }
    .pg-final-cta h2 {
        font-size: 32px; font-weight: 800;
        color: #0f0a24; margin-bottom: 12px;
    }
    .pg-final-cta p {
        color: #6b7280; font-size: 16px;
        margin-bottom: 24px;
    }
    .pg-final-cta a {
        display: inline-block;
        padding: 18px 40px;
        background: linear-gradient(135deg, #a855f7, #ec4899);
        color: white;
        text-decoration: none;
        border-radius: 100px;
        font-weight: 800;
        font-size: 16px;
        box-shadow: 0 4px 24px rgba(168, 85, 247, 0.3);
        transition: all 0.2s;
    }
    .pg-final-cta a:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 32px rgba(168, 85, 247, 0.4);
    }

    @media (max-width: 640px) {
        .pg-examples-head h2, .pg-reviews-head h2,
        .pg-compare-head h2 { font-size: 22px; }
        .pg-final-cta h2 { font-size: 24px; }
    }

    .pg-bots-row {
        margin-top: 32px;
        text-align: center;
    }
    .pg-bots-label {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 10px;
    }
    .pg-bots-buttons {
        display: inline-flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .pg-bot-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 100px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        font-family: inherit;
    }
    .pg-bot-tg {
        background: #0088cc;
        color: white;
    }
    .pg-bot-tg:hover { background: #0077b5; transform: translateY(-2px); }
    .pg-bot-max {
        background: linear-gradient(135deg, #ff6a00, #ffb800);
        color: white;
    }
    .pg-bot-max:hover { transform: translateY(-2px); }
    .pg-auth-banner {
        background: linear-gradient(135deg, #dcfce7, #d1fae5);
        border: 1px solid #86efac;
        color: #065f46;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 16px;
    }
    .pg-balance-banner {
        display: flex;
        align-items: center;
        gap: 14px;
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border: 2px solid #10b981;
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 14px;
    }
    .pg-balance-icon {
        font-size: 36px;
        flex-shrink: 0;
    }
    .pg-balance-body { text-align: left; }
    .pg-balance-title {
        font-size: 15px;
        color: #065f46;
        font-weight: 600;
    }
    .pg-balance-title b {
        font-size: 18px;
        font-weight: 800;
    }
    .pg-balance-subtitle {
        font-size: 13px;
        color: #047857;
        margin-top: 2px;
    }
    .pg-or-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 20px 0 14px;
        color: #9ca3af;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .pg-or-divider::before,
    .pg-or-divider::after {
        content: "";
        flex: 1;
        height: 1px;
        background: #e5e7eb;
    }
    .pg-mode-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 24px;
    }
    .pg-mode-tab {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 16px;
        padding: 14px;
        cursor: pointer;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s;
        font-family: inherit;
    }
    .pg-mode-tab:hover { border-color: #c4b5fd; }
    .pg-mode-tab-active {
        border-color: #a855f7;
        background: linear-gradient(135deg, #faf5ff, #fdf2f8);
        box-shadow: 0 4px 12px rgba(168, 85, 247, 0.15);
    }
    .pg-mode-icon {
        font-size: 28px;
        flex-shrink: 0;
    }
    .pg-mode-title {
        font-size: 14px;
        font-weight: 700;
        color: #0f0a24;
        margin-bottom: 2px;
    }
    .pg-mode-sub {
        font-size: 12px;
        color: #6b7280;
    }
    .pg-textarea-hint {
        text-align: right;
        font-size: 12px;
        color: #9ca3af;
        margin-top: 6px;
    }
    .pg-own-tip {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 13px;
        color: #78350f;
        margin-top: 14px;
        line-height: 1.5;
    }

    @media (max-width: 480px) {
        .pg-mode-tabs { grid-template-columns: 1fr; }
    }

    /* === «СВОЙ ГОЛОС» === */
    .pgv-banner {
        max-width: 760px;
        margin: 0 auto 8px;
        background: linear-gradient(135deg, #ec4899, #8b5cf6);
        color: #fff;
        border-radius: 20px;
        padding: 22px 24px;
        display: flex;
        align-items: center;
        gap: 18px;
        box-shadow: 0 12px 30px rgba(236, 72, 153, 0.28);
        flex-wrap: wrap;
        justify-content: center;
        text-align: center;
    }
    .pgv-banner-emoji { font-size: 42px; line-height: 1; }
    .pgv-banner-text { flex: 1; min-width: 220px; text-align: left; }
    .pgv-banner-title { font-size: 21px; font-weight: 900; letter-spacing: -0.3px; }
    .pgv-banner-sub { font-size: 14px; opacity: 0.95; margin-top: 4px; line-height: 1.5; }
    .pgv-banner .pg-btn { white-space: nowrap; background: #fff; color: #be185d; font-weight: 800; }
    @media (max-width: 560px) {
        .pgv-banner-text { text-align: center; }
        .pgv-banner { padding: 18px; }
    }

    /* CTA внутри шага «Кто поёт?» */
    .pgv-cta {
        margin-top: 18px;
        padding: 16px;
        border: 2px dashed var(--pg-accent-2, #f472b6);
        border-radius: 16px;
        background: var(--pg-bg-soft, #fdf2f8);
        text-align: center;
    }
    .pgv-cta-text { font-size: 14px; color: #6b7280; margin-bottom: 12px; line-height: 1.5; }
    .pgv-cta-text strong { color: #be185d; font-size: 15px; }
    .pgv-connected {
        margin-top: 12px; font-size: 14px; font-weight: 700; color: #059669;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .pgv-connected button {
        background: none; border: none; color: #9ca3af; cursor: pointer;
        font-size: 12px; text-decoration: underline;
    }

    /* Модалка */
    .pgv-modal {
        display: none; position: fixed; inset: 0; z-index: 9999;
        background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(2px);
        align-items: center; justify-content: center; padding: 16px;
    }
    .pgv-modal.active { display: flex; }
    .pgv-modal-card {
        background: #fff; border-radius: 20px; width: 100%; max-width: 480px;
        max-height: 92vh; overflow-y: auto; padding: 24px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .pgv-modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .pgv-modal-head h3 { font-size: 18px; font-weight: 800; }
    .pgv-close { background: none; border: none; font-size: 22px; cursor: pointer; color: #9ca3af; line-height: 1; }

    .pgv-info {
        padding: 14px 16px; background: var(--pg-bg-soft, #fdf2f8);
        border: 1px solid #fbcfe8; border-radius: 14px;
        font-size: 13px; color: #6b7280; line-height: 1.6; margin-bottom: 18px;
    }
    .pgv-info strong { color: #be185d; }

    .pgv-step { display: none; }
    .pgv-step.active { display: block; }

    .pgv-progress { display: flex; gap: 8px; margin-bottom: 18px; }
    .pgv-progress-step { flex: 1; height: 4px; border-radius: 2px; background: #f3e8f1; }
    .pgv-progress-step.done { background: var(--pg-accent, #ec4899); }
    .pgv-progress-step.current { background: var(--pg-accent, #ec4899); opacity: 0.5; }

    .pgv-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #374151; }
    .pgv-text-input {
        width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb;
        border-radius: 12px; font-size: 14px; box-sizing: border-box;
    }

    .pgv-record-controls { display: flex; gap: 12px; justify-content: center; margin: 16px 0; }
    .pgv-record-btn {
        width: 56px; height: 56px; border-radius: 50%; border: none; cursor: pointer;
        font-size: 24px; display: flex; align-items: center; justify-content: center;
        transition: transform 0.15s;
    }
    .pgv-record-btn:hover { transform: scale(1.1); }
    .pgv-record-btn.rec { background: #ef4444; color: #fff; }
    .pgv-record-btn.rec.recording { animation: pgv-pulse 1s ease-in-out infinite; }
    .pgv-record-btn.stop { background: #f3f4f6; color: #111; border: 2px solid #e5e7eb; }
    @keyframes pgv-pulse {
        0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
        50% { box-shadow: 0 0 0 12px rgba(239,68,68,0); }
    }

    .pgv-status { text-align: center; font-size: 13px; color: #9ca3af; margin-bottom: 12px; }

    .pgv-or { display: flex; align-items: center; margin: 16px 0; color: #9ca3af; font-size: 13px; }
    .pgv-or::before, .pgv-or::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
    .pgv-or::before { margin-right: 12px; }
    .pgv-or::after { margin-left: 12px; }

    .pgv-upload {
        border: 2px dashed #e5e7eb; border-radius: 12px; padding: 24px 16px;
        text-align: center; cursor: pointer; transition: border-color 0.2s, background 0.2s;
    }
    .pgv-upload:hover, .pgv-upload.dragover { border-color: var(--pg-accent, #ec4899); background: var(--pg-bg-soft, #fdf2f8); }
    .pgv-upload-text { font-size: 14px; color: #6b7280; }
    .pgv-upload-hint { font-size: 12px; color: #9ca3af; margin-top: 4px; }

    .pgv-file { display: flex; align-items: center; gap: 10px; padding: 10px; background: #f9fafb; border-radius: 12px; margin-top: 12px; }
    .pgv-file audio { flex: 1; height: 36px; }
    .pgv-file-remove { background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 16px; }

    .pgv-time { display: flex; align-items: center; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
    .pgv-time label { font-size: 13px; color: #6b7280; }
    .pgv-time input { width: 76px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; text-align: center; }

    .pgv-phrase {
        background: var(--pg-bg-soft, #fdf2f8); border: 1px solid #fbcfe8; border-radius: 12px;
        padding: 18px; text-align: center; font-size: 17px; line-height: 1.6;
        font-weight: 700; color: #111; margin: 14px 0;
    }

    .pgv-spinner {
        display: inline-block; width: 16px; height: 16px;
        border: 2px solid #f3e8f1; border-top-color: var(--pg-accent, #ec4899);
        border-radius: 50%; animation: pgv-spin 0.8s linear infinite;
        vertical-align: middle; margin-right: 6px;
    }
    @keyframes pgv-spin { to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="pg-hero">
    <div class="pg-hero-inner">
        <!-- <div class="pg-hero-badge">🎵 ИИ-композитор · 2 минуты</div> -->
        <h1>Создайте песню с помощью нейросети для особенного момента</h1>
        <p>Поздравь, признайся, поддержи - <b style="color:white;">собственной песней</b></p>
        <div class="pg-hero-stats">
            <div class="pg-hero-stat">
                <div class="pg-hero-stat-value">2 мин</div>
                <div class="pg-hero-stat-label">на создание</div>
            </div>
            <!-- <div class="pg-hero-stat">
                <div class="pg-hero-stat-value">{{ $price }}₽</div>
                <div class="pg-hero-stat-label">первый трек</div>
            </div> -->
            <div class="pg-hero-stat">
                <div class="pg-hero-stat-value">100%</div>
                <div class="pg-hero-stat-label">твой трек</div>
            </div>
        </div>
        <br>
    </div>
    <div class="pg-bots-row">
        <div class="pg-bots-label">Используй также ботов для генерации песен👇</div>
        <div class="pg-bots-buttons">
            <button type="button" class="pg-bot-btn pg-bot-tg" onclick="pgGoTelegram()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295l.213-3.053 5.56-5.023c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/></svg>
                Telegram
            </button>
            <button type="button" class="pg-bot-btn pg-bot-max" onclick="pgGoMax()">
                MAX
            </button>
        </div>
    </div>
    <br>
</div>

<div class="pg-wizard" id="pg-wizard">
    <div class="pg-progress" id="pg-progress">
        <div class="pg-progress-step active"></div>
        <div class="pg-progress-step"></div>
        <div class="pg-progress-step"></div>
        <div class="pg-progress-step"></div>
        <div class="pg-progress-step"></div>
        <div class="pg-progress-step"></div>
        <div class="pg-progress-step"></div>
    </div>

    <div class="pg-error" id="pg-error"></div>

    {{-- ШАГ 1: Язык --}}
    <div class="pg-step active" id="step-language">
        <div class="pg-step-label">Шаг 1 из 7</div>
        <h2 class="pg-step-title">На каком языке песня?</h2>
        <p class="pg-step-subtitle">Мы умеем писать на 6 языках — ИИ-композитор понимает каждый.</p>
        <div class="pg-lang-grid" id="lang-grid">
            @foreach($languages as $code => $name)
                <button type="button" class="pg-lang-chip {{ $code === 'ru' ? 'selected' : '' }}" data-value="{{ $code }}">
                    @if($code === 'ru') 🇷🇺 Русский
                    @elseif($code === 'en') 🇬🇧 English
                    @elseif($code === 'de') 🇩🇪 Deutsch
                    @elseif($code === 'es') 🇪🇸 Español
                    @elseif($code === 'fr') 🇫🇷 Français
                    @elseif($code === 'it') 🇮🇹 Italiano
                    @endif
                </button>
            @endforeach
        </div>
        <div class="pg-btn-row">
            <button type="button" class="pg-btn pg-btn-primary" onclick="pgNext('occasion')">Далее →</button>
        </div>
    </div>

    {{-- ШАГ 2: Повод --}}
    <div class="pg-step" id="step-occasion">
        <button type="button" class="pg-back-btn" onclick="pgPrev('language')">← Назад</button>
        <div class="pg-step-label">Шаг 2 из 7</div>
        <h2 class="pg-step-title">Для какого повода?</h2>
        <p class="pg-step-subtitle">Выбери — мы учтём это при создании текста и настроения песни.</p>
        <div class="pg-options-grid" id="occasion-grid">
            @foreach($occasions as $key => $label)
                <button type="button" class="pg-option" data-value="{{ $key }}" data-label="{{ $label }}">{{ $label }}</button>
            @endforeach
        </div>
        <div id="custom-occasion-wrap" style="display:none; margin-top:16px;">
            <input type="text" class="pg-input" id="custom-occasion" placeholder="Опиши свой повод...">
            <button type="button" class="pg-btn pg-btn-primary" style="margin-top:12px;" onclick="pgSubmitCustomOccasion()">Продолжить</button>
        </div>
    </div>

    {{-- ШАГ 3: Жанр --}}
    <div class="pg-step" id="step-genre">
        <button type="button" class="pg-back-btn" onclick="pgPrev('occasion')">← Назад</button>
        <div class="pg-step-label">Шаг 3 из 7</div>
        <h2 class="pg-step-title">Какой жанр?</h2>
        <p class="pg-step-subtitle">Определи звучание — от нежной баллады до энергичного рэпа.</p>
        <div class="pg-options-grid" id="genre-grid">
            @foreach($genres as $key => $label)
                <button type="button" class="pg-option" data-value="{{ $key }}" data-label="{{ $label }}">{{ $label }}</button>
            @endforeach
        </div>
        <div id="custom-genre-wrap" style="display:none; margin-top:16px;">
            <input type="text" class="pg-input" id="custom-genre" placeholder="Напиши свой жанр...">
            <button type="button" class="pg-btn pg-btn-primary" style="margin-top:12px;" onclick="pgSubmitCustomGenre()">Продолжить</button>
        </div>
    </div>

    {{-- ШАГ 4: Артист --}}
    <div class="pg-step" id="step-artist">
        <button type="button" class="pg-back-btn" onclick="pgPrev('genre')">← Назад</button>
        <div class="pg-step-label">Шаг 4 из 7 · опционально</div>
        <h2 class="pg-step-title">На кого равняемся?</h2>
        <p class="pg-step-subtitle">Необязательно. ИИ постарается передать стиль и вайб артиста — без копирования.</p>
        <div class="pg-artists-grid" id="artists-grid"></div>
        <div style="margin-top:14px;">
            <input type="text" class="pg-input" id="custom-artist" placeholder="Или впиши своего: Imagine Dragons, Макс Корж...">
        </div>
        <div class="pg-btn-row">
            <button type="button" class="pg-btn pg-btn-secondary" onclick="pgSkipArtist()">Пропустить</button>
            <button type="button" class="pg-btn pg-btn-primary" onclick="pgConfirmArtist()">Далее →</button>
        </div>
    </div>

    {{-- ШАГ 5: Голос --}}
    <div class="pg-step" id="step-voice">
        <button type="button" class="pg-back-btn" onclick="pgPrev('artist')">← Назад</button>
        <div class="pg-step-label">Шаг 5 из 7</div>
        <h2 class="pg-step-title">Кто будет петь?</h2>
        <p class="pg-step-subtitle">Мужской, женский, дуэт или пусть ИИ сам решит.</p>
        <div class="pg-gender-grid">
            <button type="button" class="pg-gender-card" data-value="m">
                <span class="pg-gender-icon">👨</span>
                <div class="pg-gender-title">Мужской</div>
                <div class="pg-gender-sub">Сольный вокал</div>
            </button>
            <button type="button" class="pg-gender-card" data-value="f">
                <span class="pg-gender-icon">👩</span>
                <div class="pg-gender-title">Женский</div>
                <div class="pg-gender-sub">Сольный вокал</div>
            </button>
            <button type="button" class="pg-gender-card" data-value="duet">
                <span class="pg-gender-icon">👫</span>
                <div class="pg-gender-title">Дуэт</div>
                <div class="pg-gender-sub">Мужской + женский</div>
            </button>
            <button type="button" class="pg-gender-card" data-value="random">
                <span class="pg-gender-icon">🎲</span>
                <div class="pg-gender-title">Случайно</div>
                <div class="pg-gender-sub">На выбор ИИ</div>
            </button>
        </div>
        <div class="pg-duet-warn" id="duet-warn">⚠️ В дуэтах голоса иногда могут смешиваться — модель не всегда идеально распределяет партии.</div>

        <div class="pgv-cta">
            <div class="pgv-cta-text">
                <strong>🎤 Хочешь, чтобы пела песню твоим голосом?</strong><br>
                Запиши или загрузи короткий фрагмент — и ИИ споёт твоим тембром.
            </div>
            <button type="button" class="pg-btn pg-btn-primary" onclick="pgvOpen()">🎙 Записать свой голос</button>
            <div class="pgv-connected" id="pgv-connected" style="display:none;">
                ✅ Голос подключён
                <button type="button" onclick="pgvReset()">убрать</button>
            </div>
        </div>

        <div class="pg-btn-row">
            <button type="button" class="pg-btn pg-btn-primary" onclick="pgGoTo('description')">Далее →</button>
        </div>
    </div>

    {{-- ШАГ 6: Описание --}}
    <div class="pg-step" id="step-description">
        <button type="button" class="pg-back-btn" onclick="pgPrev('voice')">← Назад</button>
        <div class="pg-step-label">Шаг 6 · контент</div>
        <h2 class="pg-step-title">Что в песне? 📝</h2>

        {{-- Переключатель режима --}}
        <div class="pg-mode-tabs">
            <button type="button" class="pg-mode-tab pg-mode-tab-active" id="mode-tab-idea" onclick="pgSetMode('idea')">
                <span class="pg-mode-icon">🎨</span>
                <div>
                    <div class="pg-mode-title">Описать идею</div>
                    <div class="pg-mode-sub">ИИ напишет текст за тебя</div>
                </div>
            </button>
            <button type="button" class="pg-mode-tab" id="mode-tab-own" onclick="pgSetMode('own')">
                <span class="pg-mode-icon">📝</span>
                <div>
                    <div class="pg-mode-title">Свой текст</div>
                    <div class="pg-mode-sub">У меня готовый текст</div>
                </div>
            </button>
        </div>

        {{-- Режим "Идея" --}}
        <div id="mode-content-idea" class="pg-mode-content">
            <p class="pg-step-subtitle">Опиши о чём песня — детали, имена, повод, эмоции. Чем больше расскажешь — тем точнее ИИ напишет текст.</p>
            <textarea class="pg-textarea" id="description-input"
                      placeholder="Например: Песня жене Анне на 5 лет свадьбы. Мы познакомились на работе, у нас двое детей — Соня и Миша. Хочу передать благодарность и нежность."
                      maxlength="2000"></textarea>
            <div class="pg-textarea-hint">
                <span id="desc-counter">0</span> / 2000 символов
            </div>
            <button type="button" class="pg-btn pg-btn-primary" style="margin-top: 20px;" onclick="pgGenerateLyrics()">
                ✨ Сгенерировать текст
            </button>
        </div>

        {{-- Режим "Свой текст" --}}
        <div id="mode-content-own" class="pg-mode-content" style="display:none;">
            <p class="pg-step-subtitle">Вставь готовый текст. Можно с тегами в квадратных скобках вроде <code>[Куплет]</code>, <code>[Припев]</code> — они помогут Suno правильно структурировать песню.</p>
            <textarea class="pg-textarea" id="own-lyrics-input"
                      placeholder="[Куплет 1]
        Текст первого куплета...

        [Припев]
        Текст припева..."
                      style="min-height: 240px;"
                      maxlength="20000"></textarea>
            <div class="pg-textarea-hint">
                <span id="own-counter">0</span> / 20 000 символов
            </div>


            <input type="text" class="pg-input" id="own-title-input"
                   placeholder="Название песни (необязательно — сгенерируем сами)"
                   maxlength="200" style="margin-top: 14px;">

            <button type="button" class="pg-btn pg-btn-primary" style="margin-top: 20px;" onclick="pgUseOwnLyrics()">
                Дальше →
            </button>
        </div>
    </div>

    {{-- ШАГ 6.5: Лоадер генерации текста --}}
    <div class="pg-step" id="step-lyrics-loading">
        <div class="pg-loading">
            <div class="pg-loading-orb">
                <div class="pg-loading-orb-inner">✨</div>
            </div>
            <div class="pg-loading-title" id="loading-title">Пишем текст песни...</div>
            <div class="pg-loading-text">ИИ анализирует повод, жанр и твою историю. Обычно это занимает 10–30 секунд.</div>
            <div class="pg-loading-status" id="loading-status">Запускаем ИИ-композитора</div>
        </div>
    </div>

    {{-- ШАГ 7: Предпросмотр текста --}}
    <div class="pg-step" id="step-lyrics">
        <button type="button" class="pg-back-btn" onclick="pgPrev('description')">← Назад (придумать заново)</button>
        <div class="pg-step-label">Шаг 7 из 7 · текст готов</div>
        <h2 class="pg-step-title">Вот твоя песня 🎵</h2>
        <p class="pg-step-subtitle">Отредактируй текст или попроси ИИ переделать. Название тоже можно менять.</p>

        <input type="text" class="pg-lyrics-title-input" id="song-title" placeholder="Название песни">

        <div class="pg-lyrics-box" id="lyrics-box" contenteditable="true"></div>

        <div class="pg-lyrics-actions">
            <button type="button" class="pg-action-btn" onclick="pgToggleImprove()">
                ✏️ Улучшить
            </button>
            <button type="button" class="pg-action-btn" onclick="pgToggleTranslate()">
                🌐 Перевести
            </button>
        </div>

        {{-- Панель улучшения --}}
        <div id="ai-tools">
            <div class="pg-inline-panel" id="improve-panel">
                <div class="pg-inline-row">
                    <input type="text" class="pg-input" id="improve-feedback" placeholder="Например: сделай веселее, добавь про рыбалку...">
                    <button type="button" class="pg-inline-submit" id="improve-btn" onclick="pgImproveLyrics()">Переделать</button>
                </div>
            </div>
        </div>
        {{-- Панель перевода --}}
        <div class="pg-inline-panel" id="translate-panel">
            <div class="pg-inline-row">
                <select class="pg-inline-select" id="translate-lang" style="flex:1;">
                    @foreach($languages as $code => $name)
                        <option value="{{ $code }}">
                            @if($code === 'ru') 🇷🇺 Русский
                            @elseif($code === 'en') 🇬🇧 English
                            @elseif($code === 'de') 🇩🇪 Deutsch
                            @elseif($code === 'es') 🇪🇸 Español
                            @elseif($code === 'fr') 🇫🇷 Français
                            @elseif($code === 'it') 🇮🇹 Italiano
                            @endif
                        </option>
                    @endforeach
                </select>
                <button type="button" class="pg-inline-submit" id="translate-btn" onclick="pgTranslateLyrics()">Перевести</button>
            </div>
        </div>

   
        <div class="pg-cta-section">
            <button type="button" class="pg-btn pg-btn-primary" style="margin-top: 24px;" onclick="pgGoToPayment()">
                Продолжить →
            </button>

        </div>
    </div>

    {{-- ШАГ 8: Оплата --}}
    <div class="pg-step" id="step-payment">
        <button type="button" class="pg-back-btn" onclick="pgPrev('lyrics')">← Назад к тексту</button>
        <div class="pg-step-label">Последний шаг</div>
        @if($authUser && $authUser->balance >= 1)
            <div class="pg-balance-banner">
                <div class="pg-balance-icon">🎁</div>
                <div class="pg-balance-body">
                    <div class="pg-balance-title">У тебя <b>{{ $authUser->balance }}</b> {{ trans_choice('песня|песни|песен', $authUser->balance) }} на балансе</div>
                </div>
            </div>
            <button type="button" class="pg-pay-btn" id="pg-free-btn" onclick="pgSubmitFree()" style="background: linear-gradient(135deg, #10b981, #06b6d4); color: white;">
                🎵 Создать песню
            </button>
        @elseif($authUser)
            <div class="pg-auth-banner">
                ✓ Ты вошёл как <b>{{ $authUser->email ?? $authUser->contact ?? 'пользователь' }}</b> — песня сохранится в твоём личном кабинете
            </div>
        @endif
        @if($authUser && $authUser->balance >= 1)
        <div style="display: none;">
        @endif
            <h2 class="pg-step-title">Почти готово 🎵</h2>
            <p class="pg-step-subtitle">Укажи контакт. Через него же сможешь войти в личный кабинет.</p>

            {{-- Резюме заказа --}}
            <div class="pg-payment-summary">
                <div class="pg-payment-summary-title">Твой заказ</div>
                <div class="pg-payment-summary-row">
                    <span>Название:</span><b id="summary-title">—</b>
                </div>
                <div class="pg-payment-summary-row">
                    <span>Жанр:</span><b id="summary-genre">—</b>
                </div>
                <div class="pg-payment-summary-row">
                    <span>Голос:</span><b id="summary-voice">—</b>
                </div>
                <div class="pg-payment-summary-row">
                    <span>Язык:</span><b id="summary-language">—</b>
                </div>
            </div>

            {{-- Форма --}}
            <div class="pg-contact-group">
                <label class="pg-contact-label" for="buyer-name">Как тебя зовут?</label>
                <input type="text" class="pg-input" id="buyer-name" placeholder="Имя" maxlength="100"  value="{{ $authUser->first_name ?? '' }}">
            </div>

            <div class="pg-contact-group">
                <label class="pg-contact-label" for="buyer-contact">Email *</label>
                <input type="email" class="pg-input" id="buyer-contact" placeholder="you@example.com" required value="{{ $authUser->email ?? '' }}">
                <div class="pg-contact-hint">🔒 На эту почту придёт готовая песня и доступ в личный кабинет. Чек по 54-ФЗ — на email.</div>
            </div>

            {{-- Trust-бейджи --}}
            <div class="pg-trust-badges">
                <div class="pg-trust-badge">🔐 ЮKassa</div>
                <div class="pg-trust-badge">✅ Все карты</div>
                <div class="pg-trust-badge">📜 Чек 54-ФЗ</div>
                <!-- <div class="pg-trust-badge">♾ Безлимит правок</div> -->
            </div>

            {{-- Цена + кнопка --}}
            <div class="pg-price-row">
                <div>
                    <div class="pg-price-label">К оплате</div>
                    <div style="font-size:11px; opacity:0.6;">Единоразово · без подписок</div>
                </div>
                <div class="pg-price-value">{{ $price }} ₽</div>
            </div>

            <button type="button" class="pg-pay-btn" id="pg-pay-btn" onclick="pgSubmitOrder()">
                💳 Оплатить {{ $price }}₽ и получить песню
            </button>

            <div class="pg-legal">
                Нажимая кнопку, ты соглашаешься с <a href="/oferta" target="_blank">офертой</a> и <a href="/privacy" target="_blank">политикой конфиденциальности</a>.<br>
                <!-- Оплата через ЮKassa. Возврат — <a href="/refund" target="_blank">по запросу</a>, если песня не сгенерировалась. -->
            </div>
        @if($authUser && $authUser->balance >= 1)
        </div>
        @endif
    </div>
</div>

@php
    $trackOpsAllowedIds = config('services.track_ops.allowed_user_ids', []);
    $trackOpsTeaser = empty($trackOpsAllowedIds)
        || in_array('*', $trackOpsAllowedIds, true)
        || in_array((string) ($authUser->user_id ?? ''), $trackOpsAllowedIds, true);
@endphp
@if($trackOpsTeaser)
<div style="max-width:720px;margin:-30px auto 60px;padding:0 16px;">
    <a href="{{ $authUser ? route('studio') : url('/auth') }}" style="display:block;background:white;border:1.5px solid rgba(124,58,237,0.2);border-radius:24px;padding:24px 28px;text-decoration:none;color:#1a1035;box-shadow:0 4px 16px rgba(0,0,0,0.06);">
        <span style="display:inline-block;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#7c3aed;background:rgba(124,58,237,0.08);border-radius:999px;padding:4px 12px;margin-bottom:10px;">Новое</span>
        <span style="display:block;font-size:17px;font-weight:800;">Студия обработки треков</span>
        <span style="display:block;font-size:14px;opacity:0.65;margin-top:6px;line-height:1.5;">Кавер в новом стиле, продление, минусовка, новый вокал и мэшап из ваших треков</span>
        <span style="display:inline-block;margin-top:12px;font-size:14px;font-weight:700;color:#7c3aed;">Попробовать →</span>
    </a>
</div>
@endif
<!-- <section class="pg-examples">
    <div class="pg-examples-head">
        <h2>🎧 Послушай, какие песни получаются</h2>
        <p>Реальные треки наших клиентов — сгенерированы за 2–3 минуты</p>
    </div>
    <div class="pg-examples-grid">
        <div class="pg-example-card">
            <div class="pg-example-cover" style="background: linear-gradient(135deg, #f59e0b, #ec4899);">💝</div>
            <div class="pg-example-title">Песня жене на годовщину</div>
            <div class="pg-example-meta">Поп-рок · Мужской вокал</div>
            <audio class="pg-example-audio" controls preload="none">
                <source src="/examples/anniversary.mp3" type="audio/mpeg">
            </audio>
        </div>
        <div class="pg-example-card">
            <div class="pg-example-cover" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">🎂</div>
            <div class="pg-example-title">Поздравление папе на 50 лет</div>
            <div class="pg-example-meta">Шансон · Мужской вокал</div>
            <audio class="pg-example-audio" controls preload="none">
                <source src="/examples/dad-birthday.mp3" type="audio/mpeg">
            </audio>
        </div>
        <div class="pg-example-card">
            <div class="pg-example-cover" style="background: linear-gradient(135deg, #10b981, #06b6d4);">🎓</div>
            <div class="pg-example-title">Подарок подруге на выпускной</div>
            <div class="pg-example-meta">Поп · Женский вокал</div>
            <audio class="pg-example-audio" controls preload="none">
                <source src="/examples/graduation.mp3" type="audio/mpeg">
            </audio>
        </div>
    </div>
</section> -->

<!-- <section class="pg-reviews">
    <div class="pg-reviews-head">
        <h2>Что говорят те, кто уже сделал песню</h2>
        <div class="pg-reviews-stars">⭐⭐⭐⭐⭐</div>
        <div class="pg-reviews-count">Более <b>5 400+</b> песен сгенерировано</div>
    </div>
    <div class="pg-reviews-grid">
        <div class="pg-review-card">
            <div class="pg-review-quote">«Заказала мужу на юбилей — он расплакался. Слова как будто про нас всю жизнь. 2 минуты, и такая эмоция!»</div>
            <div class="pg-review-author">
                <div class="pg-review-avatar" style="background: linear-gradient(135deg, #a855f7, #ec4899);">М</div>
                <div>
                    <div class="pg-review-name">Марина К.</div>
                    <div class="pg-review-date">2 недели назад</div>
                </div>
            </div>
        </div>
        <div class="pg-review-card">
            <div class="pg-review-quote">«Делал песню для жены на 8 марта. Офигенно получилось, она до сих пор слушает. За 199₽ — вообще бомба.»</div>
            <div class="pg-review-author">
                <div class="pg-review-avatar" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">А</div>
                <div>
                    <div class="pg-review-name">Андрей В.</div>
                    <div class="pg-review-date">1 месяц назад</div>
                </div>
            </div>
        </div>
        <div class="pg-review-card">
            <div class="pg-review-quote">«Думала, получится дёшево и тупо. А вышел реальный трек, не стыдно даже на свадьбе поставить!»</div>
            <div class="pg-review-author">
                <div class="pg-review-avatar" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">О</div>
                <div>
                    <div class="pg-review-name">Ольга П.</div>
                    <div class="pg-review-date">3 недели назад</div>
                </div>
            </div>
        </div>
    </div>
</section> -->

<!-- <section class="pg-compare">
    <div class="pg-compare-head">
        <h2>Почему мы, а не заказать у музыканта?</h2>
    </div>
    <div class="pg-compare-table">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th class="us">🎵 НА РЕПИТЕ</th>
                    <th>Заказать у автора</th>
                    <th>Suno напрямую</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Цена первой песни</td>
                    <td class="us">199 ₽</td>
                    <td>5 000–50 000 ₽</td>
                    <td>$10/мес + VPN</td>
                </tr>
                <tr>
                    <td>Время</td>
                    <td class="us">2 минуты</td>
                    <td>3–14 дней</td>
                    <td>~5 минут</td>
                </tr>
                <tr>
                    <td>ИИ-текст на русском</td>
                    <td class="us"><span class="pg-compare-yes">✓</span></td>
                    <td>—</td>
                    <td><span class="pg-compare-no">✗</span></td>
                </tr>
                <tr>
                    <td>Поддержка на русском</td>
                    <td class="us"><span class="pg-compare-yes">✓</span></td>
                    <td>±</td>
                    <td><span class="pg-compare-no">✗</span></td>
                </tr>
                <tr>
                    <td>Оплата картой РФ</td>
                    <td class="us"><span class="pg-compare-yes">✓</span></td>
                    <td><span class="pg-compare-yes">✓</span></td>
                    <td><span class="pg-compare-no">✗</span></td>
                </tr>
                <tr>
                    <td>Безлимит правок текста</td>
                    <td class="us"><span class="pg-compare-yes">✓</span></td>
                    <td>1–2 раза</td>
                    <td><span class="pg-compare-no">✗</span></td>
                </tr>
                <tr>
                    <td>Готовая аранжировка</td>
                    <td class="us"><span class="pg-compare-yes">✓</span></td>
                    <td><span class="pg-compare-yes">✓</span></td>
                    <td><span class="pg-compare-yes">✓</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section> -->


<!-- <div class="pg-final-cta">
    <h2>Готов сделать свою песню?</h2>
    <p>2 минуты — и готовый трек в твоём телефоне</p>
    <a href="#pg-wizard-top" onclick="document.querySelector('.pg-wizard').scrollIntoView({behavior:'smooth'}); return false;">🎵 Создать песню за 199₽</a>
</div> -->
{{-- Trust signals --}}
<div class="pg-trust">
    <div class="pg-trust-item">
        <div class="pg-trust-icon">⚡</div>
        <div>
            <div class="pg-trust-title">Готово за 2 минуты</div>
            <div class="pg-trust-text">От идеи до MP3-файла — быстрее, чем приготовить кофе.</div>
        </div>
    </div>
    <div class="pg-trust-item">
        <div class="pg-trust-icon">🎁</div>
        <div>
            <div class="pg-trust-title">Идеальный подарок</div>
            <div class="pg-trust-text">Такого точно ни у кого нет. Песня именно для этого человека.</div>
        </div>
    </div>
    <div class="pg-trust-item">
        <div class="pg-trust-icon">🔒</div>
        <div>
            <div class="pg-trust-title">Безопасная оплата</div>
            <div class="pg-trust-text">ЮKassa. Приём карт всех банков, чек по 54-ФЗ.</div>
        </div>
    </div>
</div>


<section class="max-w-7xl mx-auto py-8" style="max-width: 720px">
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

{{-- ============ МОДАЛКА «СВОЙ ГОЛОС» ============ --}}
<div class="pgv-modal" id="pgv-modal">
    <div class="pgv-modal-card">
        <div class="pgv-modal-head">
            <h3>🎤 Твой голос</h3>
            <button type="button" class="pgv-close" onclick="pgvClose()">×</button>
        </div>

        <div class="pgv-progress">
            <div class="pgv-progress-step" id="pgv-prog-1"></div>
            <div class="pgv-progress-step" id="pgv-prog-2"></div>
            <div class="pgv-progress-step" id="pgv-prog-3"></div>
        </div>

        <div class="pgv-info">
            Запиши <strong>20–60 секунд</strong> своего пения или речи. Дальше система попросит зачитать
            контрольную фразу — это нужно, чтобы подтвердить, что голос твой.
        </div>

        {{-- ШАГ 1: исходное аудио (запись или загрузка) --}}
        <div class="pgv-step active" id="pgv-step-source">
            <label class="pgv-label">Запиши голос</label>
            <div class="pgv-record-controls">
                <button type="button" class="pgv-record-btn rec" id="pgv-rec-btn" onclick="pgvToggleRecord('source')">🎙</button>
            </div>
            <div class="pgv-status" id="pgv-rec-status">Нажми, чтобы начать запись</div>

            <div class="pgv-or">или</div>

            <div class="pgv-upload" id="pgv-upload-source" onclick="document.getElementById('pgv-file-source').click()">
                <div class="pgv-upload-text">📁 Загрузить аудиофайл</div>
                <div class="pgv-upload-hint">mp3, wav, m4a, ogg · до 20 МБ</div>
            </div>
            <input type="file" id="pgv-file-source" accept="audio/*" style="display:none;">

            <div class="pgv-file" id="pgv-file-preview-source" style="display:none;">
                <audio id="pgv-audio-source" controls></audio>
                <button type="button" class="pgv-file-remove" onclick="pgvClearSource()">✕</button>
            </div>

            <div class="pgv-time">
                <label>Вокал с (сек):</label>
                <input type="number" id="pgv-vocal-start" value="0" min="0">
                <label>по (сек):</label>
                <input type="number" id="pgv-vocal-end" value="20" min="1">
            </div>

            <button type="button" class="pg-btn pg-btn-primary" style="margin-top:16px; width:100%;"
                    id="pgv-source-next" onclick="pgvSubmitSource()" disabled>Далее →</button>
        </div>

        {{-- ШАГ 2: чтение контрольной фразы --}}
        <div class="pgv-step" id="pgv-step-phrase">
            <label class="pgv-label">Зачитай эту фразу вслух и запиши:</label>
            <div class="pgv-phrase" id="pgv-phrase-text">
                <span class="pgv-spinner"></span> Готовим фразу...
            </div>

            <div class="pgv-record-controls">
                <button type="button" class="pgv-record-btn rec" id="pgv-rec-btn-verify" onclick="pgvToggleRecord('verify')" disabled>🎙</button>
            </div>
            <div class="pgv-status" id="pgv-rec-status-verify">Дождись фразы</div>

            <div class="pgv-or">или</div>

            <div class="pgv-upload" id="pgv-upload-verify" onclick="document.getElementById('pgv-file-verify').click()">
                <div class="pgv-upload-text">📁 Загрузить запись фразы</div>
                <div class="pgv-upload-hint">mp3, wav, m4a, ogg · до 20 МБ</div>
            </div>
            <input type="file" id="pgv-file-verify" accept="audio/*" style="display:none;">

            <div class="pgv-file" id="pgv-file-preview-verify" style="display:none;">
                <audio id="pgv-audio-verify" controls></audio>
                <button type="button" class="pgv-file-remove" onclick="pgvClearVerify()">✕</button>
            </div>

            <button type="button" class="pg-btn pg-btn-primary" style="margin-top:16px; width:100%;"
                    id="pgv-verify-next" onclick="pgvSubmitVerify()" disabled>Создать голос →</button>
        </div>

        {{-- ШАГ 3: генерация голоса --}}
        <div class="pgv-step" id="pgv-step-generating">
            <div style="text-align:center; padding:24px 0;">
                <div class="pgv-spinner" style="width:32px; height:32px; border-width:3px;"></div>
                <div class="pgv-status" id="pgv-gen-status" style="margin-top:16px; font-size:15px;">Создаём твой голос... Это может занять 1–3 минуты.</div>
            </div>
        </div>

        <div class="pg-error" id="pgv-error" style="margin-top:12px;"></div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
   (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
   m[i].l=1*new Date();
   for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
   k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
   (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

   ym(105879987, "init", {
        webvisor:true,
        clickmap:true,
        ecommerce:"dataLayer",
        accurateTrackBounce:true,
        trackLinks:true
   });
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/105879987" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<script>
    // ============ UTM-ТРЕКИНГ ============
    const COUNTER_ID = 105879987;
    const BOT_USERNAME_TG = 'na_repitebot';
    const MAX_BOT_URL = 'https://max.ru/id501216944367_bot';

    function pgCleanParam(str) {
        if (!str) return '0';
        return str.replace(/_/g, '-').replace(/[^a-zA-Z0-9\-]/g, '').substring(0, 15);
    }
    function pgGetUtm(name) {
        const p = new URLSearchParams(window.location.search);
        return pgCleanParam(p.get(name));
    }
    function pgGetClientId(ms = 500) {
        return new Promise((resolve) => {
            let done = false;
            const t = setTimeout(() => { if (!done) resolve(null); }, ms);
            try {
                if (typeof ym === 'undefined') { resolve(null); return; }
                ym(COUNTER_ID, 'getClientID', function(id) {
                    if (!done) { done = true; clearTimeout(t); resolve(id || null); }
                });
            } catch(e) { resolve(null); }
        });
    }
    function pgReachGoal(goal) {
        try { if (typeof ym !== 'undefined') ym(COUNTER_ID, 'reachGoal', goal); } catch(e) {}
    }
    async function pgBuildStartParam() {
        const clientId = await pgGetClientId(500) || '0';
        const p1 = pgGetUtm('utm_source') || 'site';
        const p2 = pgGetUtm('utm_medium');
        const p3 = pgGetUtm('utm_campaign');
        const p4 = pgGetUtm('utm_content');
        let sp = `${p1}_${p2}_${p3}_${p4}_${clientId}`;
        if (sp.length > 64) sp = `${p1}_${p2}_${p3.substring(0,5)}_${clientId}`;
        return sp;
    }

    // Переход в Telegram-бот
    async function pgGoTelegram() {
        pgReachGoal('klik-po-knopke');
        const sp = await pgBuildStartParam();
        const tgUrl = `tg://resolve?domain=${BOT_USERNAME_TG}&start=${sp}`;
        const webUrl = `https://t.me/${BOT_USERNAME_TG}?start=${sp}`;
        window.location.href = tgUrl;
        setTimeout(() => { window.location.href = webUrl; }, 800);
    }

    // Переход в MAX-бот
    async function pgGoMax() {
        pgReachGoal('klik-max');
        const sp = await pgBuildStartParam();
        window.open(`${MAX_BOT_URL}?start=${sp}`, '_blank');
    }
    const pgGenreArtists = @json($genreArtists ?? []);
    const pgStepIndex = {
        language: 0, occasion: 1, genre: 2, artist: 3,
        voice: 4, description: 5, 'lyrics-loading': 5, lyrics: 6, payment: 6
    };

    const pgData = {
        language: null,
        occasion: null,
        genre: null,
        artist: null,
        vocalGender: 'random',
        description: '',
        title: '',
        lyrics: '',
        mode: 'idea', // 'idea' | 'own'
        voiceId: null, // итоговый Kie voice_id «своего голоса» (опционально)
    };

    function pgSetMode(mode) {
        pgData.mode = mode;
        pgReachGoal(mode === 'idea' ? 'mode-idea' : 'mode-own');

        document.getElementById('mode-tab-idea').classList.toggle('pg-mode-tab-active', mode === 'idea');
        document.getElementById('mode-tab-own').classList.toggle('pg-mode-tab-active', mode === 'own');
        document.getElementById('mode-content-idea').style.display = mode === 'idea' ? 'block' : 'none';
        document.getElementById('mode-content-own').style.display = mode === 'own' ? 'block' : 'none';
    }

    // Счётчики символов
    document.addEventListener('DOMContentLoaded', () => {
        const desc = document.getElementById('description-input');
        const descCnt = document.getElementById('desc-counter');
        if (desc && descCnt) {
            desc.addEventListener('input', () => { descCnt.textContent = desc.value.length; });
        }
        const own = document.getElementById('own-lyrics-input');
        const ownCnt = document.getElementById('own-counter');
        if (own && ownCnt) {
            own.addEventListener('input', () => { ownCnt.textContent = own.value.length; });
        }
    });

    // Использовать свой текст без AI-генерации
    async function pgUseOwnLyrics() {
        const lyricsEl = document.getElementById('own-lyrics-input');
        const titleEl = document.getElementById('own-title-input');
        if (!lyricsEl) {
            pgShowError('Поле текста не найдено');
            return;
        }

        const lyrics = lyricsEl.value.trim();
        const title = titleEl ? titleEl.value.trim() : '';

        pgReachGoal('step-own-submit');

        if (!lyrics || lyrics.length < 30) {
            pgShowError('Текст слишком короткий. Минимум 30 символов.');
            return;
        }

        pgGoTo('lyrics-loading');

        try {
            const r = await fetch('/api/public-generate/prepare-lyrics', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': pgCsrf,
                },
                body: JSON.stringify({ lyrics: lyrics, title: title || null }),
            });

            const text = await r.text();
            let d;
            try { d = JSON.parse(text); }
            catch (e) {
                console.error('Non-JSON:', text.slice(0, 300));
                throw new Error(`Сервер вернул ошибку (HTTP ${r.status})`);
            }

            if (!r.ok) {
                if (r.status === 422 && d.errors) {
                    const f = Object.keys(d.errors)[0];
                    throw new Error(d.errors[f][0]);
                }
                throw new Error(d.error || d.message || 'Ошибка сервера');
            }

            // Заполняем preview
            const lyricsBox = document.getElementById('lyrics-box');
            const songTitle = document.getElementById('song-title');
            if (lyricsBox) lyricsBox.textContent = d.lyrics;
            if (songTitle) songTitle.value = d.title || '';

            pgData.lyrics = d.lyrics;
            pgData.title = d.title;

            // Скрываем AI-инструменты, т.к. это пользовательский текст
            const aiTools = document.getElementById('ai-tools');
            if (aiTools) aiTools.style.display = 'none';

            pgReachGoal('lyrics-generated');

            pgGoTo('lyrics');

        } catch (e) {
            pgShowError(e.message);
            pgGoTo('description');
        }
    }

    let pgSelectedArtistChip = null;
    const pgCsrf = document.querySelector('meta[name="csrf-token"]').content;

    // ============ NAVIGATION ============
    function pgNext(step) { pgGoTo(step); }
    function pgPrev(step) { pgGoTo(step); }
    function pgGoTo(step) {
        document.querySelectorAll('.pg-step').forEach(el => el.classList.remove('active'));
        document.getElementById('step-' + step).classList.add('active');
        pgUpdateProgress(step);
        pgHideError();
        document.getElementById('pg-wizard').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function pgUpdateProgress(step) {
        const idx = pgStepIndex[step];
        if (idx === undefined) return; // защита: для неизвестного шага ничего не делаем
        document.querySelectorAll('.pg-progress-step').forEach((el, i) => {
            el.classList.remove('active', 'done');
            if (i < idx) el.classList.add('done');
            else if (i === idx) el.classList.add('active');
        });
    }
    function pgShowError(msg) {
        const el = document.getElementById('pg-error');
        el.textContent = msg;
        el.style.display = 'block';
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function pgHideError() {
        document.getElementById('pg-error').style.display = 'none';
    }

    // ============ LANGUAGE ============
    document.querySelectorAll('#lang-grid .pg-lang-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            document.querySelectorAll('#lang-grid .pg-lang-chip').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            pgData.language = this.dataset.value;
            pgReachGoal('step-language');
        });
    });

    // ============ OCCASION ============
    document.querySelectorAll('#occasion-grid .pg-option').forEach(btn => {
        btn.addEventListener('click', function() {
            const val = this.dataset.value;
            document.querySelectorAll('#occasion-grid .pg-option').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            if (val === 'custom') {
                document.getElementById('custom-occasion-wrap').style.display = 'block';
            } else {
                document.getElementById('custom-occasion-wrap').style.display = 'none';
                pgData.occasion = this.dataset.label.trim();
                pgReachGoal('step-occasion');
                setTimeout(() => pgGoTo('genre'), 200);
            }
        });
    });
    function pgSubmitCustomOccasion() {
        const v = document.getElementById('custom-occasion').value.trim();
        if (!v) { pgShowError('Опиши свой повод'); return; }
        pgData.occasion = v;
        pgReachGoal('step-occasion');
        pgGoTo('genre');
    }

    // ============ GENRE ============
    document.querySelectorAll('#genre-grid .pg-option').forEach(btn => {
        btn.addEventListener('click', function() {
            const val = this.dataset.value;
            document.querySelectorAll('#genre-grid .pg-option').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            if (val === 'custom') {
                document.getElementById('custom-genre-wrap').style.display = 'block';
            } else {
                document.getElementById('custom-genre-wrap').style.display = 'none';
                pgData.genre = this.dataset.label.trim();
                pgData.genreKey = val;
                pgShowArtistsForGenre(val);
                pgReachGoal('step-genre');
                setTimeout(() => pgGoTo('artist'), 200);
            }
        });
    });
    function pgSubmitCustomGenre() {
        const v = document.getElementById('custom-genre').value.trim();
        if (!v) { pgShowError('Напиши свой жанр'); return; }
        pgData.genre = v;
        pgData.genreKey = 'custom';
        pgShowArtistsForGenre('custom');
        pgReachGoal('step-genre');
        pgGoTo('artist');
    }

    // ============ ARTIST ============
    function pgShowArtistsForGenre(key) {
        const box = document.getElementById('artists-grid');
        box.innerHTML = '';
        pgSelectedArtistChip = null;
        const list = pgGenreArtists[key] || [];
        if (!list.length) {
            box.innerHTML = '<p style="color:#9ca3af;font-size:13px;padding:8px;">Для этого жанра предложений нет — впиши своего любимого артиста ниже.</p>';
            return;
        }
        list.forEach(name => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'pg-artist-chip';
            chip.textContent = name;
            chip.addEventListener('click', function() {
                if (pgSelectedArtistChip) pgSelectedArtistChip.classList.remove('selected');
                this.classList.add('selected');
                pgSelectedArtistChip = this;
                pgData.artist = name;
                document.getElementById('custom-artist').value = '';
            });
            box.appendChild(chip);
        });
    }
    function pgSkipArtist() {
        pgData.artist = '';
        pgReachGoal('step-artist');
        pgGoTo('voice');
    }
    function pgConfirmArtist() {
        const custom = document.getElementById('custom-artist').value.trim();
        if (custom) pgData.artist = custom;
        pgReachGoal('step-artist');
        pgGoTo('voice');
    }

    // ============ VOICE ============
    document.querySelectorAll('.pg-gender-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.pg-gender-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            pgData.vocalGender = this.dataset.value;
            document.getElementById('duet-warn').style.display = this.dataset.value === 'duet' ? 'block' : 'none';
            pgReachGoal('step-voice');
            // Авто-переход убран: ниже есть CTA «свой голос» + кнопка «Далее»
        });
    });

    // ============ GENERATE LYRICS ============
    async function pgGenerateLyrics() {
        try { pgReachGoal('step-description-submit'); } catch (e) {}

        // Подстраховка: если language не выбран — возьмём из активной чипсы или дефолт
        if (!pgData.language) {
            const sel = document.querySelector('#lang-grid .pg-lang-chip.selected');
            pgData.language = sel ? sel.dataset.value : 'ru';
        }
        // Если жанр или повод пустые — не сможем сгенерить
        if (!pgData.occasion || !pgData.genre) {
            pgShowError('Не хватает данных. Вернись и заполни повод и жанр.');
            return;
        }

        // Считываем описание из НОВОГО id (mode=idea)
        const descEl = document.getElementById('description-input');
        const description = descEl ? descEl.value.trim() : '';
        pgData.description = description;

        if (!description || description.length < 10) {
            pgShowError('Опиши о чём песня — минимум 10 символов');
            return;
        }

        pgGoTo('lyrics-loading');
        if (typeof pgStartLoadingMessages === 'function') {
            try { pgStartLoadingMessages(); } catch (e) {}
        }

        try {
            const r = await fetch('/api/public-generate/lyrics', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': pgCsrf,
                },
                body: JSON.stringify({
                    language: pgData.language,
                    occasion: pgData.occasion,
                    genre: pgData.genre,
                    artist: pgData.artist,
                    vocal_gender: pgData.vocalGender,
                    description: pgData.description,
                }),
            });

            const text = await r.text();
            let d;
            try { d = JSON.parse(text); }
            catch (e) {
                console.error('Non-JSON:', text.slice(0, 300));
                throw new Error(`Сервер вернул ошибку (HTTP ${r.status}). Попробуй сократить текст и повторить.`);
            }

            if (!r.ok) {
                if (r.status === 422 && d.errors) {
                    const f = Object.keys(d.errors)[0];
                    throw new Error(d.errors[f][0] || d.message || 'Ошибка валидации');
                }
                throw new Error(d.error || d.message || 'Ошибка сервера');
            }

            // Заполняем preview
            const lyricsBox = document.getElementById('lyrics-box');
            const songTitle = document.getElementById('song-title');
            if (lyricsBox) lyricsBox.textContent = d.lyrics;
            if (songTitle) songTitle.value = d.title || '';

            pgData.lyrics = d.lyrics;
            pgData.title = d.title;

            // Показываем AI-инструменты (улучшить/перевести) — это режим "Идея"
            const aiTools = document.getElementById('ai-tools');
            if (aiTools) aiTools.style.display = '';

            pgReachGoal('lyrics-generated');
            pgGoTo('lyrics');

        } catch (e) {
            console.error('pgGenerateLyrics error:', e);
            pgGoTo('description');
            setTimeout(() => pgShowError(e.message), 100);
        }
    }

    // Loading messages rotation
    let pgLoadingInterval = null;
    function pgStartLoadingMessages(type) {
        const messages = type === 'lyrics' ? [
            'Запускаем ИИ-композитора',
            'Придумываем образы и метафоры',
            'Подбираем рифмы',
            'Выстраиваем структуру куплетов',
            'Финальные штрихи',
        ] : [
            'Обрабатываем запрос',
            'Применяем изменения',
            'Финальные штрихи',
        ];
        let i = 0;
        const el = document.getElementById('loading-status');
        if (el) el.textContent = messages[0];
        pgLoadingInterval = setInterval(() => {
            i = (i + 1) % messages.length;
            if (el) el.textContent = messages[i];
        }, 3500);
    }
    function pgStopLoadingMessages() {
        if (pgLoadingInterval) {
            clearInterval(pgLoadingInterval);
            pgLoadingInterval = null;
        }
    }

    // ============ IMPROVE / TRANSLATE ============
    function pgToggleImprove() {
        const panel = document.getElementById('improve-panel');
        const other = document.getElementById('translate-panel');
        other.classList.remove('open');
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) {
            document.getElementById('improve-feedback').focus();
        }
    }
    function pgToggleTranslate() {
        const panel = document.getElementById('translate-panel');
        const other = document.getElementById('improve-panel');
        other.classList.remove('open');
        panel.classList.toggle('open');
    }

    async function pgImproveLyrics() {
        const feedback = document.getElementById('improve-feedback').value.trim();
        if (!feedback) { pgShowError('Напиши, что изменить'); return; }

        const btn = document.getElementById('improve-btn');
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Переделываем...';

        try {
            // Берём актуальный текст из contenteditable
            const currentLyrics = document.getElementById('lyrics-box').textContent.trim();

            const r = await fetch('/api/public-generate/improve', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pgCsrf },
                body: JSON.stringify({
                    lyrics: currentLyrics,
                    feedback: feedback,
                    genre: pgData.genre,
                    artist: pgData.artist,
                    vocal_gender: pgData.vocalGender,
                }),
            });
            const d = await r.json();
            if (!r.ok) throw new Error(d.error || 'Ошибка');

            pgData.title = d.title;
            pgData.lyrics = d.lyrics;
            document.getElementById('song-title').value = d.title;
            document.getElementById('lyrics-box').textContent = d.display_lyrics || d.lyrics;
            document.getElementById('improve-feedback').value = '';
            document.getElementById('improve-panel').classList.remove('open');
        } catch (e) {
            pgShowError(e.message);
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    async function pgTranslateLyrics() {
        const lang = document.getElementById('translate-lang').value;
        const btn = document.getElementById('translate-btn');
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Переводим...';

        try {
            const currentLyrics = document.getElementById('lyrics-box').textContent.trim();

            const r = await fetch('/api/public-generate/translate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pgCsrf },
                body: JSON.stringify({
                    lyrics: currentLyrics,
                    target_language: lang,
                }),
            });
            const d = await r.json();
            if (!r.ok) throw new Error(d.error || 'Ошибка');

            pgData.lyrics = d.lyrics;
            pgData.language = lang;
            document.getElementById('lyrics-box').textContent = d.lyrics;
            document.getElementById('translate-panel').classList.remove('open');
        } catch (e) {
            pgShowError(e.message);
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    // ============ ПЕРЕХОД НА ЭКРАН ОПЛАТЫ ============
    function pgGoToPayment() {
        // Финальное считывание данных
        pgData.title = document.getElementById('song-title').value.trim();
        pgData.lyrics = document.getElementById('lyrics-box').textContent.trim();

        if (!pgData.lyrics) {
            pgShowError('Текст песни пустой — вернись и сгенерируй заново');
            return;
        }

        // Заполняем summary
        document.getElementById('summary-title').textContent = pgData.title || 'Будет сгенерировано';
        document.getElementById('summary-genre').textContent = pgData.genre || '—';

        const voiceLabels = { m: '👨 Мужской', f: '👩 Женский', duet: '👫 Дуэт', random: '🎲 Случайный' };
        const voiceText = pgData.voiceId ? '🎤 Твой голос' : (voiceLabels[pgData.vocalGender] || '—');
        document.getElementById('summary-voice').textContent = voiceText;

        const langLabels = { ru: '🇷🇺 Русский', en: '🇬🇧 English', de: '🇩🇪 Deutsch', es: '🇪🇸 Español', fr: '🇫🇷 Français', it: '🇮🇹 Italiano' };
        document.getElementById('summary-language').textContent = langLabels[pgData.language] || pgData.language;

        pgReachGoal('step-lyrics-continue');

        pgGoTo('payment');
    }

    // ============ ОТПРАВКА ЗАКАЗА ============
    async function pgSubmitOrder() {
        const name = document.getElementById('buyer-name').value.trim();
        const contact = document.getElementById('buyer-contact').value.trim();

        const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!contact || !emailRe.test(contact)) {
            pgShowError('Укажи корректный email');
            document.getElementById('buyer-contact').focus();
            return;
        }

        const btn = document.getElementById('pg-pay-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '⏳ Создаём заказ...';

        try {
            pgReachGoal('klik-oplatit');
            const r = await fetch('/api/public-generate/order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pgCsrf },
                body: JSON.stringify({
                    title: pgData.title,
                    lyrics: pgData.lyrics,
                    genre: pgData.genre,
                    artist: pgData.artist,
                    vocal_gender: pgData.vocalGender,
                    voice_id: pgData.voiceId,
                    language: pgData.language,
                    occasion: pgData.occasion,
                    description: pgData.description,
                    first_name: name || null,
                    contact: contact,
                    utm_source: pgGetUtm('utm_source'),
                    utm_medium: pgGetUtm('utm_medium'),
                    utm_campaign: pgGetUtm('utm_campaign'),
                    utm_content: pgGetUtm('utm_content'),
                    utm_term: pgGetUtm('utm_term'),
                    ym_client_id: await pgGetClientId(500),
                }),
            });
            const d = await r.json();

            if (!r.ok) {
                const validationMsg = d.errors ? Object.values(d.errors)[0][0] : null;
                throw new Error(d.error || validationMsg || d.message || 'Ошибка создания заказа');
            }

            // Редирект на страницу оплаты ЮKassa
            window.location.href = d.payment_url;

        } catch (e) {
            pgShowError(e.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
    async function pgSubmitFree() {
        const btn = document.getElementById('pg-free-btn');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '⏳ Запускаем генерацию...';

        try {
            const r = await fetch('/api/public-generate/create-free', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pgCsrf },
                body: JSON.stringify({
                    title: pgData.title,
                    lyrics: pgData.lyrics,
                    genre: pgData.genre,
                    artist: pgData.artist,
                    vocal_gender: pgData.vocalGender,
                    voice_id: pgData.voiceId,
                    language: pgData.language,
                    occasion: pgData.occasion,
                    description: pgData.description,
                }),
            });
            const d = await r.json();
            if (!r.ok || !d.success) throw new Error(d.error || 'Ошибка');

            pgReachGoal('free-generation-start');
            window.location.href = d.redirect_url;

        } catch (e) {
            pgShowError(e.message);
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }

    // ============ «СВОЙ ГОЛОС» (рекордер) ============
    const pgv = {
        sourceUrl: null,       // URL исходного аудио на сервере
        verifyUrl: null,       // URL записи контрольной фразы
        taskId: null,          // Kie validate taskId
        genTaskId: null,       // Kie generate taskId
        recorder: null,        // MediaRecorder
        chunks: [],
        stream: null,
        recordingTarget: null, // 'source' | 'verify'
        phrasePoll: null,
        statusPoll: null,
    };

    function pgvShowError(msg) {
        const el = document.getElementById('pgv-error');
        el.textContent = msg;
        el.style.display = 'block';
    }
    function pgvHideError() {
        document.getElementById('pgv-error').style.display = 'none';
    }

    function pgvSetStep(step) {
        document.querySelectorAll('#pgv-modal .pgv-step').forEach(el => el.classList.remove('active'));
        document.getElementById('pgv-step-' + step).classList.add('active');
        const map = { source: 1, phrase: 2, generating: 3 };
        const cur = map[step];
        [1, 2, 3].forEach(i => {
            const el = document.getElementById('pgv-prog-' + i);
            el.classList.remove('done', 'current');
            if (i < cur) el.classList.add('done');
            else if (i === cur) el.classList.add('current');
        });
    }

    function pgvOpen() {
        pgReachGoal('voice-open');
        pgvHideError();
        pgvSetStep('source');
        document.getElementById('pgv-modal').classList.add('active');
    }
    function pgvClose() {
        pgvStopStream();
        if (pgv.phrasePoll) { clearInterval(pgv.phrasePoll); pgv.phrasePoll = null; }
        if (pgv.statusPoll) { clearInterval(pgv.statusPoll); pgv.statusPoll = null; }
        document.getElementById('pgv-modal').classList.remove('active');
    }
    function pgvReset() {
        pgData.voiceId = null;
        pgv.sourceUrl = pgv.verifyUrl = pgv.taskId = pgv.genTaskId = null;
        document.getElementById('pgv-connected').style.display = 'none';
    }

    function pgvStopStream() {
        if (pgv.recorder && pgv.recorder.state === 'recording') {
            try { pgv.recorder.stop(); } catch (e) {}
        }
        if (pgv.stream) {
            pgv.stream.getTracks().forEach(t => t.stop());
            pgv.stream = null;
        }
    }

    // --- Запись с микрофона ---
    async function pgvToggleRecord(target) {
        const btn = target === 'source'
            ? document.getElementById('pgv-rec-btn')
            : document.getElementById('pgv-rec-btn-verify');
        const statusEl = target === 'source'
            ? document.getElementById('pgv-rec-status')
            : document.getElementById('pgv-rec-status-verify');

        // Остановка
        if (pgv.recorder && pgv.recorder.state === 'recording') {
            pgv.recorder.stop();
            btn.classList.remove('recording');
            statusEl.textContent = 'Обрабатываем запись...';
            return;
        }

        // Старт
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
            pgvShowError('Браузер не поддерживает запись с микрофона — загрузи файл.');
            return;
        }

        try {
            pgv.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (e) {
            pgvShowError('Нет доступа к микрофону. Разреши доступ или загрузи файл.');
            return;
        }

        // Выбираем mime, который реально поддерживает браузер (Chrome→webm, Safari→mp4)
        const mimeCandidates = ['audio/webm', 'audio/mp4', 'audio/ogg', 'audio/mpeg'];
        let chosenMime = '';
        if (typeof MediaRecorder.isTypeSupported === 'function') {
            chosenMime = mimeCandidates.find(m => MediaRecorder.isTypeSupported(m)) || '';
        }

        pgv.chunks = [];
        pgv.recordingTarget = target;
        try {
            pgv.recorder = chosenMime
                ? new MediaRecorder(pgv.stream, { mimeType: chosenMime })
                : new MediaRecorder(pgv.stream);
        } catch (e) {
            pgv.recorder = new MediaRecorder(pgv.stream);
        }
        pgv.recorder.ondataavailable = e => { if (e.data.size > 0) pgv.chunks.push(e.data); };
        pgv.recorder.onstop = () => {
            try {
                // Реальный mime берём у рекордера (без параметров после ';')
                const recMime = (pgv.recorder.mimeType || chosenMime || 'audio/webm').split(';')[0];
                const blob = new Blob(pgv.chunks, { type: recMime });
                if (pgv.stream) {
                    pgv.stream.getTracks().forEach(t => t.stop());
                    pgv.stream = null;
                }
                const extMap = { 'audio/webm': 'webm', 'audio/mp4': 'm4a', 'audio/ogg': 'ogg', 'audio/mpeg': 'mp3' };
                const ext = extMap[recMime] || 'webm';
                pgvHandleAudio(blob, target, 'voice.' + ext);
            } catch (err) {
                pgvShowError('Не удалось обработать запись: ' + err.message);
            }
        };
        pgv.recorder.start();
        btn.classList.add('recording');
        statusEl.textContent = '● Идёт запись... нажми, чтобы остановить';
    }

    // --- Загрузка файла ---
    document.getElementById('pgv-file-source').addEventListener('change', function () {
        if (this.files[0]) pgvHandleAudio(this.files[0], 'source', this.files[0].name);
    });
    document.getElementById('pgv-file-verify').addEventListener('change', function () {
        if (this.files[0]) pgvHandleAudio(this.files[0], 'verify', this.files[0].name);
    });

    // Drag-drop
    ['source', 'verify'].forEach(target => {
        const zone = document.getElementById('pgv-upload-' + target);
        if (!zone) return;
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files[0]) pgvHandleAudio(e.dataTransfer.files[0], target, e.dataTransfer.files[0].name);
        });
    });

    // --- Загрузка аудио на сервер ---
    async function pgvHandleAudio(blobOrFile, target, filename) {
        pgvHideError();
        const previewEl = document.getElementById('pgv-file-preview-' + target);
        const audioEl = document.getElementById('pgv-audio-' + target);
        const statusEl = target === 'source'
            ? document.getElementById('pgv-rec-status')
            : document.getElementById('pgv-rec-status-verify');

        audioEl.src = URL.createObjectURL(blobOrFile);
        previewEl.style.display = 'flex';
        statusEl.textContent = 'Загружаем...';

        const fd = new FormData();
        const allowed = ['mp3', 'wav', 'm4a', 'mp4', 'ogg', 'webm'];
        let ext = (filename.split('.').pop() || '').toLowerCase();
        if (!allowed.includes(ext)) {
            // запасной вариант — из mime-типа blob/файла
            const mimeExt = { 'audio/webm': 'webm', 'audio/mp4': 'm4a', 'audio/ogg': 'ogg', 'audio/mpeg': 'mp3', 'audio/wav': 'wav', 'audio/x-wav': 'wav' };
            ext = mimeExt[(blobOrFile.type || '').split(';')[0]] || 'webm';
        }
        fd.append('audio', blobOrFile, 'voice.' + ext);

        try {
            const r = await fetch('/api/public-generate/voice/upload', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': pgCsrf },
                body: fd,
            });
            const d = await r.json();
            if (!r.ok || !d.success) throw new Error(d.error || 'Ошибка загрузки');

            if (target === 'source') {
                pgv.sourceUrl = d.url;
                document.getElementById('pgv-source-next').disabled = false;
                statusEl.textContent = '✅ Аудио загружено';
            } else {
                pgv.verifyUrl = d.url;
                document.getElementById('pgv-verify-next').disabled = false;
                statusEl.textContent = '✅ Запись загружена';
            }
        } catch (e) {
            pgvShowError(e.message);
            statusEl.textContent = 'Не удалось загрузить — попробуй ещё раз';
            previewEl.style.display = 'none';
        }
    }

    function pgvClearSource() {
        pgv.sourceUrl = null;
        document.getElementById('pgv-file-preview-source').style.display = 'none';
        document.getElementById('pgv-source-next').disabled = true;
        document.getElementById('pgv-file-source').value = '';
        document.getElementById('pgv-rec-status').textContent = 'Нажми, чтобы начать запись';
    }
    function pgvClearVerify() {
        pgv.verifyUrl = null;
        document.getElementById('pgv-file-preview-verify').style.display = 'none';
        document.getElementById('pgv-verify-next').disabled = true;
        document.getElementById('pgv-file-verify').value = '';
        document.getElementById('pgv-rec-status-verify').textContent = 'Зачитай фразу выше';
    }

    // --- Запрос НОВОЙ фразы по исходному аудио ---
    // Каждый вызов создаёт свежий validate-таск (старый taskId после провала
    // верификации больше не валиден — Kie вернёт "Validate record is not in valid status")
    async function pgvRequestPhrase() {
        if (!pgv.sourceUrl) { pgvShowError('Сначала запиши или загрузи аудио'); pgvSetStep('source'); return false; }
        pgvHideError();

        const start = parseInt(document.getElementById('pgv-vocal-start').value, 10) || 0;
        const end = parseInt(document.getElementById('pgv-vocal-end').value, 10) || 20;
        if (end <= start) { pgvShowError('Конец фрагмента должен быть позже начала'); pgvSetStep('source'); return false; }

        // Сбрасываем прошлый taskId, запись чтения и UI фразы
        pgv.taskId = null;
        pgvClearVerify();
        const phraseEl = document.getElementById('pgv-phrase-text');
        document.getElementById('pgv-rec-btn-verify').disabled = true;
        phraseEl.innerHTML = '<span class="pgv-spinner"></span> Готовим фразу...';
        document.getElementById('pgv-rec-status-verify').textContent = 'Дождись фразы';
        pgvSetStep('phrase');

        try {
            const r = await fetch('/api/public-generate/voice/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pgCsrf },
                body: JSON.stringify({
                    source_audio_url: pgv.sourceUrl,
                    vocal_start: start,
                    vocal_end: end,
                    language: pgData.language || 'ru',
                }),
            });
            const d = await r.json();
            if (!r.ok || !d.success) throw new Error(d.error || 'Ошибка');

            pgv.taskId = d.task_id;
            pgvPollPhrase();
            return true;
        } catch (e) {
            pgvShowError(e.message);
            pgvSetStep('source');
            return false;
        }
    }

    // --- Шаг 1 → запрос фразы ---
    async function pgvSubmitSource() {
        const btn = document.getElementById('pgv-source-next');
        btn.disabled = true; btn.textContent = 'Обрабатываем...';
        await pgvRequestPhrase();
        btn.disabled = false; btn.textContent = 'Далее →';
    }

    // --- Поллинг контрольной фразы ---
    function pgvPollPhrase() {
        const phraseEl = document.getElementById('pgv-phrase-text');
        const recBtn = document.getElementById('pgv-rec-btn-verify');
        const statusEl = document.getElementById('pgv-rec-status-verify');
        let attempts = 0;

        pgv.phrasePoll = setInterval(async () => {
            attempts++;
            if (attempts > 40) { // ~2 мин
                clearInterval(pgv.phrasePoll);
                pgvShowError('Не удалось получить фразу. Попробуй другое аудио.');
                pgvSetStep('source');
                return;
            }
            try {
                const r = await fetch('/api/public-generate/voice/phrase?task_id=' + encodeURIComponent(pgv.taskId));
                const d = await r.json();
                if (d.status === 'ready') {
                    clearInterval(pgv.phrasePoll);
                    phraseEl.innerHTML = '«' + d.verify_phrase + '»';
                    recBtn.disabled = false;
                    statusEl.textContent = 'Нажми, чтобы записать фразу';
                } else if (d.status === 'failed') {
                    clearInterval(pgv.phrasePoll);
                    pgvShowError(d.error || 'Не удалось обработать голос');
                    pgvSetStep('source');
                }
            } catch (e) { /* продолжаем поллинг */ }
        }, 3000);
    }

    // --- Шаг 2 → генерация голоса ---
    async function pgvSubmitVerify() {
        if (!pgv.verifyUrl) { pgvShowError('Запиши или загрузи чтение фразы'); return; }
        pgvHideError();

        const btn = document.getElementById('pgv-verify-next');
        btn.disabled = true; btn.textContent = 'Создаём...';

        try {
            const r = await fetch('/api/public-generate/voice/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pgCsrf },
                body: JSON.stringify({
                    task_id: pgv.taskId,
                    verify_audio_url: pgv.verifyUrl,
                }),
            });
            const d = await r.json();
            if (!r.ok || !d.success) throw new Error(d.error || 'Ошибка');

            pgv.genTaskId = d.task_id;
            pgvSetStep('generating');
            pgvPollStatus();
        } catch (e) {
            btn.disabled = false; btn.textContent = 'Создать голос →';
            // taskId после неудачной верификации невалиден — берём свежую фразу
            pgvShowError('Не получилось. Запрашиваем новую фразу — прочитай её заново, чётко.');
            pgvRequestPhrase();
        }
    }

    // --- Поллинг готовности голоса ---
    function pgvPollStatus() {
        let attempts = 0;
        pgv.statusPoll = setInterval(async () => {
            attempts++;
            if (attempts > 60) { // ~3 мин
                clearInterval(pgv.statusPoll);
                pgvShowError('Голос создаётся дольше обычного. Запрашиваем новую фразу — попробуй ещё раз.');
                pgvRequestPhrase();
                return;
            }
            try {
                const r = await fetch('/api/public-generate/voice/status?task_id=' + encodeURIComponent(pgv.genTaskId));
                const d = await r.json();
                if (d.status === 'ready' && d.voice_id) {
                    clearInterval(pgv.statusPoll);
                    pgData.voiceId = d.voice_id;
                    pgReachGoal('voice-ready');
                    document.getElementById('pgv-connected').style.display = 'flex';
                    pgvClose();
                } else if (d.status === 'failed') {
                    clearInterval(pgv.statusPoll);
                    // Чаще всего фраза прочитана неразборчиво. Старый taskId уже невалиден —
                    // запрашиваем НОВУЮ фразу, иначе Kie вернёт "Validate record is not in valid status".
                    pgvShowError('Не удалось распознать голос по фразе. Запрашиваем новую фразу — перечитай её чётче.');
                    pgvRequestPhrase();
                }
            } catch (e) { /* продолжаем поллинг */ }
        }, 3000);
    }
</script>
@endpush