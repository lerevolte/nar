<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генератор QR-кодов</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        button { background: #6366f1; color: white; border: none; padding: 14px 24px; border-radius: 8px; font-size: 16px; cursor: pointer; width: 100%; }
        button:hover { background: #4f46e5; }
        .batches { margin-top: 30px; }
        .batch-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #eee; }
        .batch-item:last-child { border-bottom: none; }
        .batch-info { flex: 1; }
        .batch-name { font-weight: 600; }
        .batch-meta { font-size: 13px; color: #666; margin-top: 4px; }
        .batch-link { color: #6366f1; text-decoration: none; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .row { display: flex; gap: 16px; }
        .row .form-group { flex: 1; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🎯 Генератор QR-кодов</h1>
        
        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <form action="{{ route('admin.qr.generate') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label>Название партии</label>
                <input type="text" name="name" placeholder="">
            </div>

            <div class="row">
                <div class="form-group">
                    <label>Количество QR-кодов *</label>
                    <input type="number" name="quantity" value="10" min="1" max="1000" required>
                </div>

                <div class="form-group">
                    <label>Бонус песен *</label>
                    <input type="number" name="bonus_songs" value="1" min="1" max="100" required>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label>utm_source</label>
                    <input type="text" name="utm_source" value="qrcode">
                </div>

                <div class="form-group">
                    <label>utm_medium</label>
                    <input type="text" name="utm_medium" value="print">
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label>utm_campaign</label>
                    <input type="text" name="utm_campaign" placeholder="promo2024">
                </div>

                <div class="form-group">
                    <label>utm_content</label>
                    <input type="text" name="utm_content" placeholder="flyer_a5">
                </div>
            </div>

            <button type="submit">🚀 Сгенерировать QR-коды</button>
        </form>
    </div>

    @if($batches->isNotEmpty())
    <div class="card batches">
        <h2>📦 Последние партии</h2>
        
        @foreach($batches as $batch)
            <div class="batch-item">
                <div class="batch-info">
                    <div class="batch-name">{{ $batch->name }}</div>
                    <div class="batch-meta">
                        {{ $batch->quantity }} шт. • +{{ $batch->bonus_songs }} песен • 
                        Сканирований: {{ $batch->scans_count }}
                    </div>
                </div>
                <a href="{{ route('qr.show', $batch->token) }}" class="batch-link" target="_blank">Открыть →</a>
            </div>
        @endforeach
    </div>
    @endif
</body>
</html>