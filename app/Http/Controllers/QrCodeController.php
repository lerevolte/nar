<?php

namespace App\Http\Controllers;

use App\Models\QrBatch;
use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeController extends Controller
{
    /**
     * Админка — форма генерации
     */
    public function adminForm(Request $request)
    {
        $user = $request->get('auth_user');
        if($user->user_id == 288559694 || $user->user_id == 154483653) {
            $batches = QrBatch::withCount('codes')
                ->orderByDesc('created_at')
                ->take(20)
                ->get();

            return view('admin.qr.index', compact('batches'));
        }
    }

    /**
     * Админка — генерация QR-кодов
     */
    public function generate(Request $request)
    {
        $user = $request->get('auth_user');
        if($user->user_id == 288559694 || $user->user_id == 154483653) {
            $request->validate([
                'name' => 'nullable|string|max:255',
                'quantity' => 'required|integer|min:1|max:1000',
                'bonus_songs' => 'required|integer|min:1|max:100',
                'utm_source' => 'nullable|string|max:50',
                'utm_medium' => 'nullable|string|max:50',
                'utm_campaign' => 'nullable|string|max:50',
                'utm_content' => 'nullable|string|max:50',
            ]);

            $batch = QrBatch::create([
                'token' => Str::random(32),
                'name' => $request->input('name') ?: 'Партия ' . now()->format('d.m.Y H:i'),
                'quantity' => $request->input('quantity'),
                'bonus_songs' => $request->input('bonus_songs', 1),
                'utm_source' => $request->input('utm_source', 'qrcode'),
                'utm_medium' => $request->input('utm_medium'),
                'utm_campaign' => $request->input('utm_campaign'),
                'utm_content' => $request->input('utm_content'),
            ]);

            $codes = [];
            for ($i = 0; $i < $batch->quantity; $i++) {
                $codes[] = [
                    'batch_id' => $batch->id,
                    'code' => Str::random(12),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            QrCode::insert($codes);

            return redirect()->route('admin.qr.show', $batch->token)
                ->with('success', "Создано {$batch->quantity} QR-кодов");
        }
    }

    /**
     * Публичная страница с QR-кодами
     */
    public function show(Request $request, string $token)
    {
        $batch = QrBatch::where('token', $token)->firstOrFail();
        $codes = $batch->codes()->get();

        return view('qr.show', compact('batch', 'codes'));
    }

    /**
     * Генерация одного QR-кода как PNG
     */
    public function image(string $code)
    {
        $qrCode = QrCode::where('code', $code)->firstOrFail();
        $url = $qrCode->getTelegramUrl();

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(1800)
            ->margin(50)
            ->build();

        return response($result->getString())
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', "inline; filename=\"qr_{$code}.png\"");
    }

    /**
     * Скачать все QR-коды как ZIP
     */
    public function downloadZip(string $token)
    {
        $batch = QrBatch::where('token', $token)->firstOrFail();
        $codes = $batch->codes()->get();

        $zipFileName = "qr_batch_{$batch->id}.zip";
        $zipPath = storage_path("app/temp/{$zipFileName}");

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($codes as $index => $qrCode) {
            $url = $qrCode->getTelegramUrl();

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($url)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(1800)
                ->margin(50)
                ->build();

            $fileName = sprintf('qr_%03d_%s.png', $index + 1, $qrCode->code);
            $zip->addFromString($fileName, $result->getString());
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }
    /**
     * Скачать список ссылок как CSV
     */
    public function downloadCsv(string $token)
    {
        $batch = QrBatch::where('token', $token)->firstOrFail();
        $codes = $batch->codes()->get();

        $csvFileName = "qr_batch_{$batch->id}.csv";
        
        $callback = function() use ($codes) {
            $file = fopen('php://output', 'w');
            
            
            foreach ($codes as $index => $qrCode) {
                fputcsv($file, [
                    $qrCode->getTelegramUrl()
                ]);
            }
            
            fclose($file);
        };

        return response()->streamDownload($callback, $csvFileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}