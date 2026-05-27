<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR-коды — {{ $batch->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f5f5f5; 
        }
        .header {
            max-width: 1200px;
            margin: 0 auto 30px;
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 { margin: 0 0 8px; }
        .meta { color: #666; font-size: 14px; }
        .actions { margin-top: 16px; }
        .btn {
            display: inline-block;
            background: #6366f1;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }
        .btn:hover { background: #4f46e5; }
        .qr-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .qr-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .qr-card img {
            width: 100%;
            max-width: 280px;
            height: auto;
        }
        .qr-code-text {
            margin-top: 12px;
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }
        .qr-download {
            display: inline-block;
            margin-top: 12px;
            color: #6366f1;
            text-decoration: none;
            font-size: 14px;
        }
        @media print {
            body { background: white; padding: 0; }
            .header, .actions { display: none; }
            .qr-grid { gap: 10px; }
            .qr-card { box-shadow: none; border: 1px solid #ddd; page-break-inside: avoid; }
            .qr-download { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $batch->name }}</h1>
        <div class="meta">
            {{ $batch->quantity }} QR-кодов • 
            +{{ $batch->bonus_songs }} {{ $batch->bonus_songs == 1 ? 'песня' : 'песен' }} за сканирование 
        </div>
        <div class="actions">
            <a href="{{ route('qr.download', $batch->token) }}" class="btn">⬇️ Скачать все (ZIP)</a>
        </div>
    </div>

    <div class="qr-grid">
        @foreach($codes as $index => $qrCode)
            <div class="qr-card">
                <img src="{{ route('qr.image', $qrCode->code) }}" alt="QR {{ $index + 1 }}">
                <div class="qr-code-text">#{{ $index + 1 }} — {{ $qrCode->code }}</div>
                <a href="{{ route('qr.image', $qrCode->code) }}" download="qr_{{ $qrCode->code }}.png" class="qr-download">
                    Скачать PNG
                </a>
            </div>
        @endforeach
    </div>
</body>
</html>