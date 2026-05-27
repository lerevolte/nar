<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

class GenerateArticleThumbnails extends Command
{
    protected $signature = 'articles:generate-thumbs {--force : Перегенерировать даже если thumb уже есть}';
    protected $description = 'Генерирует миниатюры (thumb) для обложек статей';

    public function handle()
    {
        $manager = new ImageManager(new Driver());

        $query = Article::whereNotNull('cover_url');
        if (!$this->option('force')) {
            $query->where(function($q) {
                $q->whereNull('cover_thumb_url')->orWhere('cover_thumb_url', '');
            });
        }

        $articles = $query->get();
        $total = $articles->count();

        if ($total === 0) {
            $this->info('Нечего обрабатывать — все статьи уже имеют thumb');
            return 0;
        }

        $this->info("Обрабатываем {$total} статей...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $dir = public_path('uploads/articles');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ok = 0;
        $skipped = 0;

        foreach ($articles as $article) {
            $coverUrl = $article->cover_url;

            // Путь к файлу на диске
            $relPath = ltrim(parse_url($coverUrl, PHP_URL_PATH) ?? $coverUrl, '/');
            $absPath = public_path($relPath);

            if (!file_exists($absPath)) {
                // Попробуем скачать если это внешний URL
                if (Str::startsWith($coverUrl, ['http://', 'https://'])) {
                    try {
                        $data = @file_get_contents($coverUrl);
                        if ($data === false) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }
                        $tmpPath = sys_get_temp_dir() . '/' . uniqid() . '.img';
                        file_put_contents($tmpPath, $data);
                        $absPath = $tmpPath;
                    } catch (\Exception $e) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }
                } else {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
            }

            try {
                $img = $manager->read($absPath);
                if ($img->width() > 400) {
                    $img->scale(width: 400);
                }

                $thumbName = 'thumb-' . time() . '-' . Str::random(8) . '.jpg';
                $thumbPath = $dir . '/' . $thumbName;
                $img->toJpeg(82)->save($thumbPath);

                $article->cover_thumb_url = '/uploads/articles/' . $thumbName;
                $article->save();

                $ok++;

                // удалим временный файл если был
                if (isset($tmpPath) && file_exists($tmpPath)) {
                    @unlink($tmpPath);
                    unset($tmpPath);
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Статья {$article->id}: " . $e->getMessage());
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Готово! Обработано: {$ok}, пропущено: {$skipped}");

        return 0;
    }
}