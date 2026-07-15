<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Правка контента по ТЗ (п.5): «Музыка» вместо «Музыку» в H1 и Title статьи
return new class extends Migration
{
    public function up(): void
    {
        $article = DB::table('articles')->where('slug', 'muzyku-pod-nastroenie-ot-neyroseti')->first();

        if (! $article) {
            return;
        }

        DB::table('articles')
            ->where('id', $article->id)
            ->update([
                'title' => str_replace('Музыку под настроение', 'Музыка под настроение', $article->title),
                'seo_title' => $article->seo_title
                    ? str_replace('Музыку под настроение', 'Музыка под настроение', $article->seo_title)
                    : null,
            ]);
    }

    public function down(): void
    {
        $article = DB::table('articles')->where('slug', 'muzyku-pod-nastroenie-ot-neyroseti')->first();

        if (! $article) {
            return;
        }

        DB::table('articles')
            ->where('id', $article->id)
            ->update([
                'title' => str_replace('Музыка под настроение', 'Музыку под настроение', $article->title),
                'seo_title' => $article->seo_title
                    ? str_replace('Музыка под настроение', 'Музыку под настроение', $article->seo_title)
                    : null,
            ]);
    }
};
