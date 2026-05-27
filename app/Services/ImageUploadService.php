<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageUploadService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Загружает картинку + создаёт thumb-версию
     *
     * @param UploadedFile $file
     * @param string $subdir      // 'articles', 'static-pages' и т.п.
     * @param array $sizes        // ['main' => 1300, 'thumb' => 400]
     * @param int $quality
     * @return array              // ['main' => '/uploads/.../name.jpg', 'thumb' => '...']
     */
    public function upload(UploadedFile $file, string $subdir, array $sizes = ['main' => 1300, 'thumb' => 400], int $quality = 82): array
    {
        $dir = public_path('uploads/' . $subdir);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $baseName = time() . '-' . \Illuminate\Support\Str::random(8);
        $result = [];

        foreach ($sizes as $label => $width) {
            $img = $this->manager->read($file->getRealPath());

            // Уменьшаем только если оригинал шире
            if ($img->width() > $width) {
                $img->scale(width: $width);
            }

            $fileName = $baseName . '-' . $label . '.jpg';
            $path = $dir . '/' . $fileName;
            $img->toJpeg($quality)->save($path);

            $result[$label] = '/uploads/' . $subdir . '/' . $fileName;
        }

        return $result;
    }
}