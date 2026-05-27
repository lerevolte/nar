<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('is_guide')->default(false)->after('reading_time');
        });

        $guideSlugs = [
            'sozdat-pesnyu-s-pomoschyu-neyroseti',
            'suno-ai-polnyy-gayd',
            'generatsiya-teksta-pesni-onlayn',
            'kak-sdelat-pesnyu-iz-svoego-stiha',
            'bot-dlya-generatsii-pesen-v-telegram',
            'kak-besplatno-sozdat-pesnyu-v-neyroseti',
            'prompty-dlya-generatsii-muzyki',
            'neiroset-dlia-sozdaniia-rok-muzyki-kak-opisat-zanr-v-prompte',
            'kak-sozdat-pesnyu-s-pomoschyu-neyroseti',
            'kak-sozdat-rep-ili-hip-hop-s-pomoschyu-neyroseti',
            'kak-sgenerirovat-detskuiu-pesniu-s-pomoshhiu-neiroseti',
            'kak-sdelat-pozdravlenie-s-dnyom-rozhdeniya-cherez-neyroset',
            'generatsiya-pesni-po-tekstu-poshagovyy-gayd',
            'neyronka-dlya-generatsii-pesen',
            'kak-sgenerirovat-ii-pesnyu-na-russkom-yazyke-onlayn',
        ];

        DB::table('articles')
            ->whereIn('slug', $guideSlugs)
            ->update(['is_guide' => true]);
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('is_guide');
        });
    }
};
