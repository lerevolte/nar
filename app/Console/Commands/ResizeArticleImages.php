<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

class ResizeArticleImages extends Command
{
    protected $signature = 'articles:resize-images
                            {field : Поле (cover_url|banner_url)}
                            {width=1300 : Целевая ширина}
                            {--quality=82}';

    protected $description = 'Пережимает существующие картинки статей (cover/banner) до нужной ширины';

    public function handle()
    {
        $field = $this->argument('field');
        $width = (int) $this->argument('width');
        $quality = (int) $this->option('quality');

        if (!in_array($field, ['cover_url', 'banner_url'])) {
            $this->error('Поле должно быть cover_url или banner_url');
            return 1;
        }

        $manager = new ImageManager(new Driver());

        $articles = Article::whereNotNull($field)->where($field, '!=', '')->get();
        $total = $articles->count();
        if ($total === 0) { $this->info('Нечего обрабатывать'); return 0; }

        $this->info("Пережимаем {$total} картинок из {$field} до {$width}px...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $dir = public_path('uploads/articles');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ok = 0; $skipped = 0;

        foreach ($articles as $article) {
            $url = $article->{$field};
            $relPath = ltrim(parse_url($url, PHP_URL_PATH) ?? $url, '/');
            $absPath = public_path($relPath);
            $tmpPath = null;

            if (!file_exists($absPath)) {
                if (Str::startsWith($url, ['http://', 'https://'])) {
                    try {
                        $data = @file_get_contents($url);
                        if ($data === false) { $skipped++; $bar->advance(); continue; }
                        $tmpPath = sys_get_temp_dir() . '/' . uniqid() . '.img';
                        file_put_contents($tmpPath, $data);
                        $absPath = $tmpPath;
                    } catch (\Exception $e) { $skipped++; $bar->advance(); continue; }
                } else { $skipped++; $bar->advance(); continue; }
            }

            try {
                $img = $manager->read($absPath);

                if ($img->width() > $width) {
                    $img->scale(width: $width);
                }
                $name = $field . '-' . time() . '-' . Str::random(8) . '.jpg';
                $img->toJpeg($quality)->save($dir . '/' . $name);

                $article->{$field} = '/uploads/articles/' . $name;
                $article->save();

                $ok++;

                if ($tmpPath && file_exists($tmpPath)) @unlink($tmpPath);
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Статья {$article->id}: " . $e->getMessage());
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Готово. Обработано: {$ok}, пропущено: {$skipped}");
        return 0;
    }
}