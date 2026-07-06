<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SunoService
{
    private string $apiKey;

    private string $apiUrl;

    // Маппинг жанров (как в suno_service.py - GENRE_MAP)
    private array $genreMap = [
        'Рэп/Хип-хоп' => 'Hip-hop, Rap, rapping',
        'Поп' => 'Pop',
        'Рок' => 'Rock',
        'R&B/Соул' => 'R&B, Soul',
        'Электронная' => 'Electronic, EDM',
        'Джаз' => 'Jazz',
        'Классическая' => 'Classical',
        'Кантри' => 'Country',
        'Регги' => 'Reggae',
        'Латино' => 'Latin',
        'Фолк' => 'Folk',
        'Метал' => 'Metal',
        'Блюз' => 'Blues',
        'Диско' => 'Disco',
        'Инди' => 'Indie',
    ];

    // Маппинг артистов на стиль (как в suno_service.py - ARTIST_STYLE_MAP) - ПОЛНЫЙ СПИСОК
    private array $artistStyleMap = [
        // ============ РУССКИЕ АРТИСТЫ ============
        // Поп
        'zivert' => 'modern russian pop, electronic pop, catchy female vocals, dance-pop',
        'зиверт' => 'modern russian pop, electronic pop, catchy female vocals, dance-pop',
        'егор крид' => 'russian pop, romantic ballads, smooth male vocals, dance-pop, catchy hooks',
        'egor kreed' => 'russian pop, romantic ballads, smooth male vocals, dance-pop, catchy hooks',
        'ёлка' => 'russian pop, positive vibes, catchy melodies, warm female vocals',
        'elka' => 'russian pop, positive vibes, catchy melodies, warm female vocals',
        'клава кока' => 'russian pop, energetic, fun female vocals, dance, youthful',
        'klava koka' => 'russian pop, energetic, fun female vocals, dance, youthful',
        'ханна' => 'russian pop, dance music, catchy, female vocals, club',
        'hanna' => 'russian pop, dance music, catchy, female vocals, club',
        'полина гагарина' => 'russian pop ballads, powerful female vocals, emotional, dramatic',
        'дима билан' => 'russian pop, eurovision style, dramatic, romantic male vocals',
        'сергей лазарев' => 'russian pop, dance-pop, powerful male vocals, dramatic',
        'tiësto' => 'trance, edm, progressive house, energetic, legendary dj',
        'тиесто' => 'trance, edm, progressive house, energetic, legendary dj',
        'beyoncé' => 'r&b pop, powerful female vocals, danceable, empowering, diva',

        // Рэп / Хип-хоп
        'баста' => 'russian hip-hop, emotional rap, melodic flow, deep lyrics, storytelling',
        'basta' => 'russian hip-hop, emotional rap, melodic flow, deep lyrics, storytelling',
        'oxxxymiron' => 'lyrical russian rap, complex rhymes, storytelling, battle rap, intellectual',
        'оксимирон' => 'lyrical russian rap, complex rhymes, storytelling, battle rap, intellectual',
        'скриптонит' => 'dark russian rap, atmospheric hip-hop, deep bass, moody, minimalist',
        'scriptonite' => 'dark russian rap, atmospheric hip-hop, deep bass, moody, minimalist',
        'miyagi' => 'reggae rap, chill vibes, melodic hip-hop, positive energy, smooth flow',
        'мияги' => 'reggae rap, chill vibes, melodic hip-hop, positive energy, smooth flow',
        'моргенштерн' => 'russian trap, aggressive rap, autotune, hard bass, party music, provocative',
        'morgenshtern' => 'russian trap, aggressive rap, autotune, hard bass, party music, provocative',
        'хаски' => 'aggressive russian rap, raw energy, street poetry, intense, emotional',
        'husky' => 'aggressive russian rap, raw energy, street poetry, intense, emotional',
        'feduk' => 'russian pop-rap, catchy melodies, fun vibes, summer music, light',
        'федук' => 'russian pop-rap, catchy melodies, fun vibes, summer music, light',
        'элджей' => 'russian trap, catchy hooks, party vibes, club music, autotune',
        'eldzhey' => 'russian trap, catchy hooks, party vibes, club music, autotune',
        'big baby tape' => 'russian trap, energetic, party rap, hard bass, aggressive flow',
        'kizaru' => 'russian trap, aggressive flow, hard beats, street rap, dark',
        'кизару' => 'russian trap, aggressive flow, hard beats, street rap, dark',
        'pharaoh' => 'russian cloud rap, atmospheric, autotune, experimental, artistic',
        'фараон' => 'russian cloud rap, atmospheric, autotune, experimental, artistic',
        'face' => 'russian emo rap, aggressive delivery, trap beats, dark themes, raw',
        'фейс' => 'russian emo rap, aggressive delivery, trap beats, dark themes, raw',
        'noize mc' => 'russian alternative rap, rock influences, political lyrics, energetic, smart',
        'нойз мс' => 'russian alternative rap, rock influences, political lyrics, energetic, smart',
        'lsp' => 'alternative hip-hop, experimental, emotional, artistic, unique',
        'лсп' => 'alternative hip-hop, experimental, emotional, artistic, unique',
        'gone.fludd' => 'experimental russian rap, cloud rap, unique style, artistic, weird',
        'thrill pill' => 'russian emo rap, melodic, sad vibes, autotune, emotional',

        // R&B / Соул
        'jony' => 'romantic pop, emotional male vocals, love ballads, soulful, heartfelt',
        'джони' => 'romantic pop, emotional male vocals, love ballads, soulful, heartfelt',
        'rauf faik' => 'emotional pop duet, romantic, sad ballads, heartfelt, tender',
        'rauf & faik' => 'emotional pop duet, romantic, sad ballads, heartfelt, tender',
        'рауф фаик' => 'emotional pop duet, romantic, sad ballads, heartfelt, tender',
        'hammali' => 'romantic r&b, emotional duets, love songs, soulful, smooth',
        'хаммали' => 'romantic r&b, emotional duets, love songs, soulful, smooth',
        'hammali & navai' => 'romantic r&b, emotional duets, love songs, soulful, smooth',
        'navai' => 'romantic pop, smooth vocals, love ballads, emotional, tender',
        'наваи' => 'romantic pop, smooth vocals, love ballads, emotional, tender',
        'мот' => 'russian r&b pop, smooth male vocals, romantic, modern, catchy',
        'mot' => 'russian r&b pop, smooth male vocals, romantic, modern, catchy',
        'тима белорусских' => 'russian pop, romantic, catchy hooks, emotional, youthful',
        'jah khalib' => 'romantic r&b, smooth vocals, pop-rap, love songs, atmospheric',
        'джах халиб' => 'romantic r&b, smooth vocals, pop-rap, love songs, atmospheric',

        // Инди / Альтернатива
        'монеточка' => 'indie pop, quirky lyrics, lo-fi vibes, ironic, unique female vocals',
        'monetochka' => 'indie pop, quirky lyrics, lo-fi vibes, ironic, unique female vocals',
        'макс корж' => 'russian indie pop, emotional vocals, atmospheric synths, melancholic, youth',
        'max korzh' => 'russian indie pop, emotional vocals, atmospheric synths, melancholic, youth',
        'мукка' => 'sad russian pop, emotional, melancholic, youth culture, tender',
        'mukka' => 'sad russian pop, emotional, melancholic, youth culture, tender',
        'три дня дождя' => 'russian sad rap, emotional, melancholic, atmospheric, depressive',
        'matrang' => 'alternative russian rap, experimental, unique flow, atmospheric',
        'матранг' => 'alternative russian rap, experimental, unique flow, atmospheric',
        'земфира' => 'russian rock, emotional female vocals, alternative, poetic, deep',
        'zemfira' => 'russian rock, emotional female vocals, alternative, poetic, deep',
        'звонкий' => 'russian pop, catchy, romantic, summer vibes, light',
        'нервы' => 'russian pop-rock, emotional, youth anthems, energetic, catchy',
        'slava marlow' => 'russian hyperpop, catchy, viral, energetic, fun, modern',
        'слава марлоу' => 'russian hyperpop, catchy, viral, energetic, fun, modern',

        // Рок
        'кино' => 'russian post-punk, melancholic, poetic, iconic, atmospheric',
        'kino' => 'russian post-punk, melancholic, poetic, iconic, atmospheric',
        'ддт' => 'russian rock, poetic lyrics, philosophical, emotional',
        'ddt' => 'russian rock, poetic lyrics, philosophical, emotional',
        'сплин' => 'russian alternative rock, melancholic, poetic, atmospheric',
        'би-2' => 'russian rock, melodic, emotional, anthemic, catchy',
        'bi-2' => 'russian rock, melodic, emotional, anthemic, catchy',
        'мумий тролль' => 'russian rock, playful, ironic, catchy, unique style',

        // Шансон
        'михаил круг' => 'russian chanson, prison songs, emotional, storytelling, classic',
        'григорий лепс' => 'russian rock ballads, powerful male vocals, emotional, dramatic, raspy',
        'трофим' => 'russian chanson, storytelling, life wisdom, emotional, acoustic',
        'любэ' => 'russian patriotic rock, folk elements, powerful, anthemic',
        'ирина аллегрова' => 'russian pop chanson, powerful female vocals, dramatic, emotional',
        'стас михайлов' => 'russian pop chanson, romantic, smooth male vocals, sentimental',
        'ваенга' => 'russian chanson pop, emotional female vocals, dramatic, soulful',

        // Электронная / Dance
        'little big' => 'rave,rave,rave, crazy russian rave, humorous, energetic, wild, party',
        'руки вверх' => 'russian eurodance, 90s vibes, catchy, nostalgic, party',
        'imanbek' => 'house music, dance, electronic, modern, club, energetic',
        'filatov & karas' => 'russian house, dance, electronic, modern, club',

        // Метал
        'ария' => 'russian heavy metal, powerful vocals, epic, classic metal',
        'кипелов' => 'russian power metal, powerful male vocals, epic, emotional, soaring',
        'эпидемия' => 'russian power metal, symphonic, epic, fantasy themes',
        'король и шут' => 'russian punk rock, horror punk, theatrical, storytelling, dark humor',

        // Регги
        "5'nizza" => 'acoustic reggae, chill, positive, duo, laid-back, summer vibes',
        '5nizza' => 'acoustic reggae, chill, positive, duo, laid-back, summer vibes',
        'пятница' => 'acoustic reggae, chill, positive, duo, laid-back, summer vibes',

        // Детская
        'барбарики' => 'kids music, fun, cheerful, simple lyrics, playful',
        'фиксики' => 'kids music, educational, fun, animated, catchy',
        'смешарики' => 'kids music, fun, cheerful, animated, memorable',

        // Вечеринка
        'инстасамка' => 'russian female rap, provocative, party music, bold, club',
        'instasamka' => 'russian female rap, provocative, party music, bold, club',
        'тимати' => 'russian pop-rap, club music, catchy hooks, r&b influences, party',
        'timati' => 'russian pop-rap, club music, catchy hooks, r&b influences, party',
        'artik & asti' => 'russian pop duo, dance, romantic, catchy, modern',
        'artik asti' => 'russian pop duo, dance, romantic, catchy, modern',

        // ============ ЗАРУБЕЖНЫЕ АРТИСТЫ ============
        // Поп
        'taylor swift' => 'pop country crossover, storytelling, emotional, catchy, personal lyrics',
        'тейлор свифт' => 'pop country crossover, storytelling, emotional, catchy, personal lyrics',
        'dua lipa' => 'disco pop, dance, retro vibes, catchy hooks, modern',
        'дуа липа' => 'disco pop, dance, retro vibes, catchy hooks, modern',
        'ariana grande' => 'pop r&b, high vocals, danceable, catchy, emotional',
        'ариана гранде' => 'pop r&b, high vocals, danceable, catchy, emotional',
        'justin bieber' => 'pop r&b, smooth vocals, danceable, romantic, modern',
        'джастин бибер' => 'pop r&b, smooth vocals, danceable, romantic, modern',
        'ed sheeran' => 'acoustic pop, heartfelt lyrics, romantic, folk influences, storytelling',
        'эд ширан' => 'acoustic pop, heartfelt lyrics, romantic, folk influences, storytelling',
        'billie eilish' => 'dark pop, whispery vocals, moody, minimalist, atmospheric, unique',
        'билли айлиш' => 'dark pop, whispery vocals, moody, minimalist, atmospheric, unique',
        'the weeknd' => 'synth-pop r&b, 80s vibes, dark romantic, falsetto, atmospheric',
        'уикенд' => 'synth-pop r&b, 80s vibes, dark romantic, falsetto, atmospheric',
        'harry styles' => 'pop rock, 70s vibes, romantic, artistic, retro',
        'гарри стайлс' => 'pop rock, 70s vibes, romantic, artistic, retro',
        'lady gaga' => 'theatrical pop, dance, dramatic, artistic, powerful vocals, bold',
        'леди гага' => 'theatrical pop, dance, dramatic, artistic, powerful vocals, bold',
        'bruno mars' => 'funk pop, retro soul, catchy, danceable, romantic, groovy',
        'бруно марс' => 'funk pop, retro soul, catchy, danceable, romantic, groovy',
        'adele' => 'soul pop, powerful ballads, emotional, dramatic vocals, heartbreak',
        'адель' => 'soul pop, powerful ballads, emotional, dramatic vocals, heartbreak',
        'sia' => 'powerful pop, emotional vocals, dramatic, anthemic, unique',
        'сиа' => 'powerful pop, emotional vocals, dramatic, anthemic, unique',
        'lana del rey' => 'dream pop, melancholic, cinematic, vintage, atmospheric, sad',
        'лана дель рей' => 'dream pop, melancholic, cinematic, vintage, atmospheric, sad',
        'sam smith' => 'soul pop, emotional ballads, powerful vocals, heartfelt, romantic',
        'lewis capaldi' => 'emotional pop ballads, raw vocals, heartbreak, powerful, scottish',
        'john legend' => 'soul r&b, romantic piano ballads, smooth vocals, classy',

        // Рэп / Хип-хоп
        'eminem' => 'fast rap, lyrical, aggressive flow, storytelling, complex rhymes, raw',
        'эминем' => 'fast rap, lyrical, aggressive flow, storytelling, complex rhymes, raw',
        'drake' => 'melodic rap, r&b influences, smooth flow, emotional hip-hop, catchy',
        'дрейк' => 'melodic rap, r&b influences, smooth flow, emotional hip-hop, catchy',
        'kendrick lamar' => 'conscious rap, jazz influences, lyrical, storytelling, poetic',
        'кендрик ламар' => 'conscious rap, jazz influences, lyrical, storytelling, poetic',
        'travis scott' => 'psychedelic trap, atmospheric, autotune, dark vibes, experimental',
        'трэвис скотт' => 'psychedelic trap, atmospheric, autotune, dark vibes, experimental',
        'kanye west' => 'experimental hip-hop, soulful samples, innovative, artistic, bold',
        'kanye' => 'experimental hip-hop, soulful samples, innovative, artistic, bold',
        'канье' => 'experimental hip-hop, soulful samples, innovative, artistic, bold',
        'post malone' => 'melodic rap, rock influences, emotional, laid-back, catchy hooks',
        'пост малон' => 'melodic rap, rock influences, emotional, laid-back, catchy hooks',
        '50 cent' => 'gangsta rap, g-funk, aggressive, street, catchy hooks, club',
        'juice wrld' => 'emo rap, melodic, emotional, freestyle, heartfelt, sad',
        'xxxtentacion' => 'emo rap, raw emotion, aggressive, vulnerable, genre-blending',
        'jay-z' => 'east coast rap, smooth flow, lyrical, luxurious, iconic, storytelling',
        'jay z' => 'east coast rap, smooth flow, lyrical, luxurious, iconic, storytelling',
        'snoop dogg' => 'g-funk, laid-back flow, west coast rap, smooth, chill',
        'снуп дог' => 'g-funk, laid-back flow, west coast rap, smooth, chill',
        'tupac' => 'west coast rap, emotional, poetic, street poetry, powerful, thug life',
        '2pac' => 'west coast rap, emotional, poetic, street poetry, powerful, thug life',
        'nas' => 'lyrical rap, storytelling, poetic, east coast, conscious, classic',
        'future' => 'trap, autotune, melodic, dark vibes, atmospheric, mumble',

        // Рок
        'linkin park' => 'nu metal, rock rap, emotional, powerful, aggressive, hybrid',
        'линкин парк' => 'nu metal, rock rap, emotional, powerful, aggressive, hybrid',
        'queen' => 'classic rock, theatrical, operatic, anthemic, epic',
        'квин' => 'classic rock, theatrical, operatic, anthemic, epic',
        'nirvana' => 'grunge, raw, emotional, distorted guitars, angsty',
        'нирвана' => 'grunge, raw, emotional, distorted guitars, angsty',
        'imagine dragons' => 'arena rock, anthemic, powerful, energetic, modern rock',
        'coldplay' => 'alternative rock, atmospheric, emotional, anthemic, beautiful',
        'колдплей' => 'alternative rock, atmospheric, emotional, anthemic, beautiful',
        'green day' => 'punk rock, energetic, rebellious, catchy, political',
        'the beatles' => 'classic rock, melodic, innovative, timeless, british invasion',
        'битлз' => 'classic rock, melodic, innovative, timeless, british invasion',
        'ac/dc' => 'hard rock, powerful riffs, energetic, classic, headbanging',
        'arctic monkeys' => 'indie rock, british, clever lyrics, catchy, cool',
        'the 1975' => 'indie pop rock, synth, emotional, modern, atmospheric',

        // Метал
        'metallica' => 'heavy metal, thrash, powerful riffs, aggressive, iconic',
        'металлика' => 'heavy metal, thrash, powerful riffs, aggressive, iconic',
        'rammstein' => 'industrial metal, german, heavy, theatrical, aggressive, dark',
        'раммштайн' => 'industrial metal, german, heavy, theatrical, aggressive, dark',
        'slipknot' => 'nu metal, aggressive, heavy, masked, intense, angry',
        'system of a down' => 'alternative metal, political, unique, armenian, progressive',
        'iron maiden' => 'heavy metal, epic, galloping, british, classic metal',
        'black sabbath' => 'heavy metal, doom, dark, pioneering, Ozzy, heavy riffs',
        'nightwish' => 'symphonic metal, operatic female vocals, epic, orchestral',
        'stone sour' => 'alternative metal, hard rock, post-grunge, powerful male vocals',
        'стоун саур' => 'alternative metal, hard rock, post-grunge, powerful male vocals',

        // R&B / Соул
        'beyonce' => 'r&b pop, powerful female vocals, danceable, empowering, diva',
        'бейонсе' => 'r&b pop, powerful female vocals, danceable, empowering, diva',
        'rihanna' => 'r&b pop, danceable, edgy, caribbean influences, bold',
        'рианна' => 'r&b pop, danceable, edgy, caribbean influences, bold',
        'usher' => 'r&b, smooth male vocals, danceable, romantic, classic',
        'chris brown' => 'r&b pop, danceable, smooth, romantic, modern',
        'frank ocean' => 'alternative r&b, emotional, artistic, unique, introspective',
        'sza' => 'alternative r&b, emotional female vocals, modern, vulnerable',
        'alicia keys' => 'r&b soul, piano, powerful female vocals, emotional, classy',
        'amy winehouse' => 'soul jazz, retro, powerful female vocals, emotional, unique',

        // Электронная / Dance
        'david guetta' => 'edm, house, dance, club, energetic, mainstream',
        'calvin harris' => 'edm, house, pop crossover, catchy, summer, club',
        'marshmello' => 'future bass, edm, melodic, happy, masked dj',
        'tiesto' => 'trance, edm, progressive house, energetic, legendary dj',
        'martin garrix' => 'big room house, edm, festival, energetic, young',
        'kygo' => 'tropical house, chill, summer vibes, melodic, relaxing',
        'avicii' => 'progressive house, melodic edm, emotional, anthemic, legendary',
        'skrillex' => 'dubstep, bass music, aggressive, electronic, heavy drops',
        'daft punk' => 'french house, electronic, robotic, disco, iconic, innovative',

        // Джаз / Блюз
        'frank sinatra' => 'classic jazz, crooner, swing, elegant, timeless, smooth',
        'фрэнк синатра' => 'classic jazz, crooner, swing, elegant, timeless, smooth',
        'ella fitzgerald' => 'jazz vocals, scat singing, elegant, classic, smooth',
        'louis armstrong' => 'jazz, trumpet, gravelly vocals, swing, classic, joyful',
        'norah jones' => 'jazz pop, mellow, smooth female vocals, acoustic, relaxing',
        'diana krall' => 'jazz vocals, piano, sophisticated, smooth, elegant',
        'michael buble' => 'jazz pop, crooner, smooth male vocals, romantic, classic',
        'nina simone' => 'jazz soul, powerful, emotional, activist, unique voice',
        'nat king cole' => 'jazz, smooth male vocals, elegant, classic, romantic',

        // Фолк / Акустика
        'mumford & sons' => 'folk rock, banjo, energetic, british, anthemic',
        'of monsters and men' => 'indie folk, icelandic, atmospheric, male-female vocals',
        'the lumineers' => 'folk rock, americana, storytelling, acoustic, heartfelt',
        'hozier' => 'indie folk rock, soulful male vocals, poetic, irish, emotional',
        'bon iver' => 'indie folk, falsetto, atmospheric, experimental, emotional',
        'iron & wine' => 'indie folk, gentle, acoustic, intimate, whispery vocals',

        // Регги
        'bob marley' => 'reggae, roots, positive, jamaican, iconic, rastafari, peaceful',
        'боб марли' => 'reggae, roots, positive, jamaican, iconic, rastafari, peaceful',
        'ub40' => 'reggae pop, british, smooth, accessible, romantic',
        'shaggy' => 'dancehall reggae, fun, party, caribbean, catchy',
        'sean paul' => 'dancehall, reggae, party, caribbean, energetic, club',
        'damian marley' => 'reggae hip-hop, roots, conscious, jamaican royalty',

        // Латино / Вечеринка
        'pitbull' => 'latin pop, party, club, energetic, fun, Mr Worldwide',
        'jason derulo' => 'pop r&b, danceable, catchy, party, club',
        'flo rida' => 'hip-hop pop, party, club, fun, catchy hooks',
        'lmfao' => 'party rock, electronic, fun, silly, club anthems',
        'black eyed peas' => 'hip-hop pop, dance, party, energetic, fun',
        'shakira' => 'latin pop, danceable, energetic, world music, hips',
        'шакира' => 'latin pop, danceable, energetic, world music, hips',
        'bad bunny' => 'reggaeton, latin trap, danceable, caribbean, modern',

        // Классика / Кино
        'hans zimmer' => 'cinematic orchestral, epic, dramatic, film scores, powerful',
        'ханс циммер' => 'cinematic orchestral, epic, dramatic, film scores, powerful',
        'ludovico einaudi' => 'neoclassical piano, minimalist, emotional, beautiful, calm',
        'yiruma' => 'neoclassical piano, romantic, gentle, emotional, korean',
        'max richter' => 'neoclassical, ambient, emotional, cinematic, modern classical',
        'ennio morricone' => 'film scores, spaghetti western, epic, dramatic, iconic',
        'эннио морриконе' => 'film scores, spaghetti western, epic, dramatic, iconic',
    ];

    // "Безопасные" термины - не артисты (как в suno_service.py - safe_terms)
    private array $safeTerms = [
        'pop', 'rock', 'rap', 'hip', 'hop', 'r&b', 'rnb', 'jazz', 'blues', 'soul',
        'funk', 'disco', 'house', 'techno', 'edm', 'electronic', 'acoustic',
        'indie', 'alternative', 'metal', 'punk', 'reggae', 'country', 'folk',
        'classical', 'latin', 'trap', 'drill', 'phonk', 'lo-fi', 'lofi',
        'male', 'female', 'solo', 'vocalist', 'vocals', 'singing', 'voice',
        'energetic', 'emotional', 'aggressive', 'chill', 'romantic', 'sad',
        'happy', 'dark', 'light', 'fast', 'slow', 'catchy', 'melodic',
        'instrumental', 'russian', 'english', 'songs', 'song', 'music',
        'поп', 'рок', 'рэп', 'хип', 'хоп', 'джаз', 'блюз', 'соул', 'диско',
        'инди', 'метал', 'панк', 'регги', 'кантри', 'фолк', 'классика',
        'шансон', 'электро', 'транс', 'хаус', 'техно', 'инструментал',
        'мужской', 'женский', 'вокал', 'голос', 'грустный', 'веселый',
        'песня', 'трек', 'музыка', 'в', 'на', 'с', 'стиле', 'жанре', 'типа',
        'как', 'группа', 'группы', 'и', 'или',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.suno.api_key');
        $this->apiUrl = config('services.suno.api_url');
    }

    /**
     * Запуск генерации музыки (1-в-1 как в suno_service.py бота)
     */
    public function generateMusic(array $params): array
    {
        $lyrics = $params['lyrics'] ?? '';
        $title = $params['title'] ?? 'My Song';
        $genre = $params['genre'] ?? 'Pop';
        $vocalGender = $params['vocal_gender'] ?? null; // 'm', 'f', 'duet', null/random
        $isPromo = $params['is_promo'] ?? false;
        $instrumental = $params['instrumental'] ?? false;

        // === Формируем стиль (как в боте — строки 1239-1250) ===
        // Убираем эмодзи из жанра
        $style = $genre;
        foreach (['🎶', '🎤', '🕺', '🎸', '🎻', '🎹', '⚡', '🌿', '🎵', '✨'] as $emoji) {
            $style = str_replace($emoji, '', $style);
        }
        $style = trim($style);

        // Маппинг через GENRE_MAP
        $styleEn = $this->genreMap[$style] ?? $style;

        // === Gender-теги (КОРОТКО, как в боте, строки 1252-1258) ===
        $genderTags = '';
        if ($vocalGender === 'f') {
            $genderTags = ', female vocals, female singer';
        } elseif ($vocalGender === 'm') {
            $genderTags = ', male vocals, male singer';
        } elseif ($vocalGender === 'duet') {
            $genderTags = ', duet, male and female vocals, man and woman singing';
        }

        $stylePrompt = $styleEn.$genderTags;

        // === Замена артистов (как в боте, строки 1262-1272) ===
        // 1. Словарь — быстро
        $originalStyle = $stylePrompt;
        $stylePrompt = $this->replaceArtistsInStyle($stylePrompt);

        // 2. AI-фоллбэк — если словарь НЕ сработал И подозрение на имя артиста
        if ($stylePrompt === $originalStyle && $this->mightContainArtist($originalStyle)) {
            Log::info("Potential artist detected, asking AI: '{$originalStyle}'");
            $aiResult = $this->replaceArtistWithAI($originalStyle);
            // AI может вернуть оригинал если не справился — это нормально
            $stylePrompt = $aiResult;
        }

        // ВАЖНО: stripArtistNames() из бота НЕТ — не режем имена вслепую.
        // Если AI не справился — оставляем оригинал (бот так и делает).

        if ($originalStyle !== $stylePrompt) {
            Log::info("Artist replacement: '{$originalStyle}' -> '{$stylePrompt}'");
        }

        // === Формируем payload (как в боте, строки 1274-1309) ===
        $payload = [
            'customMode' => true,
            'instrumental' => $instrumental,
            'model' => 'V5_5', // ← как в боте!
            'title' => mb_substr($title, 0, 80),
            'style' => mb_substr($stylePrompt, 0, 200),
            'callBackUrl' => 'https://example.com/callback',
        ];

        // Custom voice
        $voiceId = $params['voice_id'] ?? null;
        if ($voiceId) {
            $payload['voiceId'] = $voiceId;
            Log::info("Using custom voice: {$voiceId}");
        }

        // personaId — универсальный: принимает и voice_id и persona_id
        $personaId = $params['persona_id'] ?? null;
        $personaSource = $params['persona_source'] ?? null; // 'kie' или null (sunoapi)
        if ($personaId) {
            $payload['personaId'] = $personaId;
            Log::info("Using personaId: {$personaId}, source: {$personaSource}");
        }

        // Текст (если не инструментал)
        if (! $instrumental) {
            $finalLyrics = is_array($lyrics) ? implode("\n", $lyrics) : (string) $lyrics;

            // Voice-теги КОРОТКИЕ (как в боте, строки 1293-1302)
            if ($vocalGender === 'f') {
                $voiceTag = "[Female Vocal]\n\n";
            } elseif ($vocalGender === 'm') {
                $voiceTag = "[Male Vocal]\n\n";
            } else {
                // Для duet и random — НЕ добавляем ничего,
                // т.к. теги для дуэта уже вставлены в prepareLyricsForSuno
                $voiceTag = '';
            }

            $finalLyrics = $voiceTag.$finalLyrics;

            // Watermarks для промо
            if ($isPromo) {
                $finalLyrics = $this->injectWatermarks($lyrics);
                Log::info('PROMO MODE: Lyrics modified with watermarks');
            }

            $payload['prompt'] = mb_substr($finalLyrics, 0, 3000);
        }

        Log::info("Generate request: title={$title}, style=".mb_substr($stylePrompt, 0, 50).', instrumental='.($instrumental ? 'true' : 'false'));

        // === Retry логика (как была) ===
        $maxRetries = 3;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $generateUrl = ($personaId && $personaSource === 'kie')
                    ? 'https://api.kie.ai/api/v1/generate'
                    : "{$this->apiUrl}/generate";

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.($personaId && $personaSource === 'kie' ? config('services.kie.api_key') : $this->apiKey),
                    'Content-Type' => 'application/json',
                ])->timeout(60)->post($generateUrl, $payload);

                $status = $response->status();
                Log::info("Suno API attempt {$attempt}: status={$status}");

                if (in_array($status, [502, 503, 504])) {
                    $lastError = "Сервер генерации недоступен (HTTP {$status})";
                    Log::warning("Suno API {$status}, retry {$attempt}/{$maxRetries}");
                    if ($attempt < $maxRetries) {
                        sleep(5 * $attempt);

                        continue;
                    }

                    return ['success' => false, 'error' => $lastError, 'retry_possible' => true];
                }

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['code'] ?? 0) == 200) {
                        $taskId = $data['data']['taskId'] ?? null;
                        if ($taskId) {
                            return ['success' => true, 'task_id' => $taskId];
                        }

                        return ['success' => false, 'error' => 'Не получен ID задачи'];
                    }

                    $errorMsg = $data['msg'] ?? 'Unknown error';

                    // Проверяем истёкшую персону/голос
                    if (str_contains(strtolower($errorMsg), 'expired') || str_contains(strtolower($errorMsg), 'persona') && str_contains(strtolower($errorMsg), 'invalid')) {
                        return ['success' => false, 'error' => $errorMsg, 'persona_expired' => true];
                    }

                    return ['success' => false, 'error' => $this->cleanErrorMessage($errorMsg)];
                }

                return [
                    'success' => false,
                    'error' => $this->cleanErrorMessage("HTTP {$status}: ".mb_substr($response->body(), 0, 100)),
                ];
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = 'Ошибка подключения: '.$e->getMessage();
                Log::warning("Suno API connection error: {$e->getMessage()}, attempt {$attempt}/{$maxRetries}");
                if ($attempt < $maxRetries) {
                    sleep(5 * $attempt);

                    continue;
                }
            } catch (\Exception $e) {
                Log::error('Suno API exception: '.$e->getMessage());

                return ['success' => false, 'error' => $this->cleanErrorMessage($e->getMessage())];
            }
        }

        return [
            'success' => false,
            'error' => $lastError ?? 'Не удалось подключиться к серверу генерации',
            'retry_possible' => true,
        ];
    }

    /**
     * Проверка статуса генерации (как в suno_service.py - check_music_status)
     */
    public function checkStatus(string $taskId, ?string $apiSource = null): array
    {
        try {
            $checkUrl = ($apiSource === 'kie')
                ? 'https://api.kie.ai/api/v1/generate/record-info'
                : "{$this->apiUrl}/generate/record-info";

            Log::info("checkStatus: taskId={$taskId}, apiSource={$apiSource}, url={$checkUrl}");

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.($apiSource === 'kie' ? config('services.kie.api_key') : $this->apiKey),
                'Content-Type' => 'application/json',
            ])->timeout(30)->get($checkUrl, [
                'taskId' => $taskId,
            ]);

            $data = $response->json();

            if (! $response->successful() || ($data['code'] ?? 0) != 200) {
                return [
                    'status' => 'error',
                    'error' => $data['msg'] ?? 'API error',
                ];
            }

            $taskData = $data['data'] ?? [];
            $status = $taskData['status'] ?? 'PENDING';

            Log::info("Task {$taskId} status: {$status}");

            if ($status === 'SUCCESS') {
                $responseData = $taskData['response'] ?? [];
                $sunoData = $responseData['sunoData'] ?? [];
                $songs = [];

                foreach ($sunoData as $clip) {
                    $audioUrl = $clip['audioUrl'] ?? '';
                    if ($audioUrl) {
                        $songs[] = [
                            'id' => $clip['id'] ?? '',
                            'audio_url' => $audioUrl,
                            'title' => $clip['title'] ?? '',
                            'duration' => $clip['duration'] ?? 0,
                            'image_url' => $clip['imageUrl'] ?? '',
                        ];
                    }
                }

                Log::info('Parsed songs: '.json_encode($songs));

                return [
                    'status' => 'completed',
                    'songs' => $songs,
                ];
            } elseif (in_array($status, ['FAILED', 'ERROR'])) {
                return [
                    'status' => 'failed',
                    'error' => $taskData['errorMessage'] ?? 'Generation failed',
                ];
            }

            // PENDING, PROCESSING, TEXT_SUCCESS, FIRST_SUCCESS
            return ['status' => 'processing'];
        } catch (\Exception $e) {
            Log::error('Suno check status error: '.$e->getMessage());

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Замена артистов на стиль — улучшенная (с нормализацией диакритики)
     */
    private function replaceArtistsInStyle(string $style): string
    {
        $result = $style;

        // Сортируем по длине (длинные первыми: "hammali & navai" раньше "navai")
        $artists = array_keys($this->artistStyleMap);
        usort($artists, fn ($a, $b) => mb_strlen($b) - mb_strlen($a));

        foreach ($artists as $artist) {
            $artistNorm = $this->normalizeForSearch($artist);
            $resultNorm = $this->normalizeForSearch($result);
            $artistNormLen = mb_strlen($artistNorm);

            // Ищем все вхождения
            $searchPos = 0;
            while (($pos = mb_strpos($resultNorm, $artistNorm, $searchPos)) !== false) {
                // Проверяем границу слова в НОРМАЛИЗОВАННОЙ строке
                if (! $this->isWordBoundary($resultNorm, $pos, $artistNormLen)) {
                    $searchPos = $pos + 1;

                    continue;
                }

                // Нашли на границе слова! Определяем длину в оригинале
                $bestLen = $artistNormLen;
                for ($tryLen = $artistNormLen; $tryLen <= $artistNormLen + 3; $tryLen++) {
                    if ($pos + $tryLen > mb_strlen($result)) {
                        break;
                    }
                    $chunk = mb_substr($result, $pos, $tryLen);
                    if ($this->normalizeForSearch($chunk) === $artistNorm) {
                        $bestLen = $tryLen;
                        break;
                    }
                }

                // Заменяем
                $replacement = $this->artistStyleMap[$artist];
                $result = mb_substr($result, 0, $pos).$replacement.mb_substr($result, $pos + $bestLen);

                // После замены позиции поехали — выходим из цикла для этого артиста
                break;
            }
        }

        // Чистим
        $result = preg_replace('/\bstyle\b/i', '', $result);
        $result = preg_replace('/,\s*,/', ',', $result);
        $result = preg_replace('/\s+/', ' ', $result);

        return trim($result, ' ,');
    }

    /**
     * Проверка, является ли жанр рэпом
     */
    private function isRapGenre(string $style): bool
    {
        $lower = mb_strtolower($style);

        return str_contains($lower, 'рэп') ||
               str_contains($lower, 'хип-хоп') ||
               str_contains($lower, 'rap') ||
               str_contains($lower, 'hip-hop') ||
               str_contains($lower, 'hip hop');
    }

    /**
     * Водяные знаки для промо (как в suno_service.py - inject_watermarks)
     */
    private function injectWatermarks(string $lyrics): string
    {
        $watermarkText = config('services.suno.watermark_text', '[Spoken Word] Песня создана в боте На Репите');
        $watermarkTag = "\n{$watermarkText}\n";

        // В начало
        $modified = $watermarkTag.$lyrics;

        // После каждого припева
        $pattern = '/(\[(?:Chorus|Припев|Drop|Hook).*?\])/i';
        $modified = preg_replace($pattern, '$1'.$watermarkTag, $modified);

        // В конец
        $modified .= $watermarkTag;

        return $modified;
    }

    /**
     * Проверка, может ли текст содержать имя артиста — с нормализацией
     */
    private function mightContainArtist(string $text): bool
    {
        $textLower = mb_strtolower($text);

        // 1. Словарь (быстро)
        foreach (array_keys($this->artistStyleMap) as $artist) {
            if (str_contains($textLower, mb_strtolower($artist))) {
                return true;
            }
        }

        // 2. Паттерны-триггеры
        $patterns = [
            '/в\s+стиле\s+/ui',
            '/как\s+у\s+/ui',
            '/типа\s+/ui',
            '/похоже\s+на\s+/ui',
            '/\s+style\b/i',
            '/\blike\s+/i',
            '/групп[аы]\s+/ui',
            '/\bband\s+/i',
            '/\bartist\s+/i',
            '/артист\s+/ui',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $textLower)) {
                return true;
            }
        }

        // 3. Эвристика для коротких текстов (< 7 слов)
        $words = preg_split('/\s+/', trim($text));
        $wordCount = count($words);

        if ($wordCount < 7) {
            // Чистим от пунктуации
            $cleanWords = array_map(fn ($w) => mb_strtolower(preg_replace('/[^\w]/u', '', $w)), $words);

            // Ищем слова, которых нет в safe_terms
            $unknown = array_filter($cleanWords, function ($w) {
                return $w
                    && ! in_array($w, $this->safeTerms)
                    && ! ctype_digit($w);
            });

            if (! empty($unknown)) {
                return true;
            }
        }

        // 4. Слова с Заглавной Буквы (для длинных текстов)
        if (preg_match_all('/\b[A-ZА-ЯЁ][a-zа-яё]+\b/u', $text, $matches)) {
            foreach ($matches[0] as $word) {
                if (! in_array(mb_strtolower($word), $this->safeTerms)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * AI-замена артиста на стиль (как в suno_service.py - replace_artist_with_ai)
     */
    private function replaceArtistWithAI(string $style): string
    {
        $systemPrompt = 'Ты — эксперт по музыкальным стилям. Твоя задача — заменить имена артистов на описание их музыкального стиля.

ПРАВИЛА:
1. Если в тексте есть имя артиста/группы — замени его на описание стиля (жанр, настроение, особенности звучания)
2. Если имён артистов нет — верни текст без изменений
3. Отвечай ТОЛЬКО результатом, без пояснений
4. Сохраняй остальные слова (solo male vocalist и т.д.)
5. Описание стиля пиши на английском

ПРИМЕРЫ:
Вход: "Zivert, solo female vocalist"
Выход: "modern russian pop, electronic pop, catchy vocals, dance-pop, solo female vocalist"

Вход: "Eminem style, aggressive"
Выход: "fast rap, lyrical, complex rhymes, aggressive flow, aggressive"

Вход: "Pop, energetic, solo male vocalist"
Выход: "Pop, energetic, solo male vocalist"

Вход: "как у Монеточки"
Выход: "indie pop, quirky lyrics, lo-fi vibes, ironic female vocals"

Вход: "в стиле Miyagi и Эндшпиль"
Выход: "reggae rap, chill vibes, melodic hip-hop, positive energy, duo"';

        $userPrompt = "Замени артистов на стиль:\n{$style}";

        try {
            $provider = config('services.ai_provider', 'gemini');

            if ($provider === 'openai') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ]);

                $result = $response->json('choices.0.message.content', '');
            } else {
                $apiKey = config('services.gemini.api_key');
                $model = config('services.gemini.model', 'gemini-2.0-flash');

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents' => [
                            ['parts' => [['text' => $systemPrompt."\n\n".$userPrompt]]],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.3,
                            'maxOutputTokens' => 500,
                        ],
                    ]
                );

                $result = $response->json('candidates.0.content.parts.0.text', '');
            }

            // Очищаем результат
            $result = trim(str_replace("\n", ' ', $result));

            // Проверяем адекватность
            if ($result && mb_strlen($result) < 500) {
                Log::info("AI artist replacement: '{$style}' -> '{$result}'");

                return $result;
            }

            return $style;
        } catch (\Exception $e) {
            Log::error('AI artist replacement failed: '.$e->getMessage());

            return $style;
        }
    }

    /**
     * Очистка сообщения об ошибке (как в suno_service.py - clean_error_message)
     */
    private function cleanErrorMessage(string $error): string
    {
        if (empty($error)) {
            return 'Неизвестная ошибка';
        }

        // Если HTML страница
        if (str_contains($error, '<!DOCTYPE') || str_contains(strtolower($error), '<html')) {
            if (str_contains($error, '502')) {
                return 'Сервер генерации временно недоступен (502)';
            } elseif (str_contains($error, '503')) {
                return 'Сервер генерации перегружен (503)';
            } elseif (str_contains($error, '504')) {
                return 'Таймаут сервера генерации (504)';
            }

            return 'Сервер генерации недоступен';
        }

        // Убираем HTML теги
        $error = strip_tags($error);

        // Частые отказы Suno -> понятный русский текст
        $low = strtolower($error);
        $map = [
            'matches an existing recording' => 'Аудио совпало с известной записью из каталога — Suno не обрабатывает такие файлы (защита авторских прав). Сделайте ремейк по тексту.',
            'copyright' => 'Материал похож на защищённый авторским правом — Suno отклонил запрос. Попробуйте изменить текст или стиль.',
            'insufficient credits' => 'Недостаточно кредитов на стороне генерации. Мы уже уведомлены.',
            'negativetags' => 'Технические теги запроса заполнены некорректно — попробуйте ещё раз.',
            'prompt is too long' => 'Текст слишком длинный — сократите его.',
            'too long' => 'Текст или стиль слишком длинные — сократите.',
            'sensitive' => 'Запрос отклонён модерацией (чувствительный контент). Измените текст.',
            'violat' => 'Запрос отклонён модерацией. Измените текст или стиль.',
            'rate limit' => 'Слишком много запросов подряд. Подождите немного и повторите.',
        ];
        foreach ($map as $needle => $ru) {
            if (str_contains($low, $needle)) {
                return $ru;
            }
        }

        // Сокращаем
        if (mb_strlen($error) > 200) {
            $error = mb_substr($error, 0, 200).'...';
        }

        return $error;
    }

    /**
     * Запуск разделения вокала и музыки — как в suno_service.py separate_vocals
     */
    public function separateVocals(string $originalTaskId, string $audioId): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ];

        $payload = [
            'taskId' => $originalTaskId,
            'audioId' => $audioId,
            'type' => 'separate_vocal',
            'callBackUrl' => 'https://example.com/callback',
        ];

        Log::info("Vocal separation request: task={$originalTaskId}, audio={$audioId}");

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post("{$this->apiUrl}/vocal-removal/generate", $payload);

            $responseText = $response->body();
            Log::info('Vocal separation response: '.mb_substr($responseText, 0, 500));

            if ($response->successful()) {
                $data = $response->json();

                if (($data['code'] ?? 0) == 200) {
                    $newTaskId = $data['data']['taskId'] ?? null;
                    if ($newTaskId) {
                        return ['success' => true, 'task_id' => $newTaskId];
                    }
                }

                return ['success' => false, 'error' => $data['msg'] ?? 'Unknown error'];
            }

            return ['success' => false, 'error' => "HTTP {$response->status()}: {$responseText}"];

        } catch (\Exception $e) {
            Log::error('Vocal separation error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Проверка статуса разделения — как в suno_service.py check_vocal_separation_status
     */
    public function checkVocalSeparationStatus(string $taskId): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get("{$this->apiUrl}/vocal-removal/record-info", [
                    'taskId' => $taskId,
                ]);

            if ($response->status() !== 200) {
                return ['status' => 'processing'];
            }

            $data = $response->json();

            if (! $data || ($data['code'] ?? 0) !== 200) {
                return ['status' => 'processing'];
            }

            $taskData = $data['data'] ?? null;
            if (! $taskData) {
                return ['status' => 'processing'];
            }

            $successFlag = $taskData['successFlag'] ?? null;
            $respObj = $taskData['response'] ?? [];

            $instUrl = $respObj['instrumentalUrl'] ?? null;
            $vocalUrl = $respObj['vocalUrl'] ?? null;

            // Fallback: old format
            if (! $instUrl) {
                $oldInfo = $taskData['vocal_removal_info'] ?? [];
                $instUrl = $oldInfo['instrumental_url'] ?? $oldInfo['instrumentalUrl'] ?? null;
                $vocalUrl = $oldInfo['vocal_url'] ?? $oldInfo['vocalUrl'] ?? null;
            }

            if ($successFlag === 'SUCCESS' || $instUrl) {
                return [
                    'status' => 'completed',
                    'instrumental_url' => $instUrl ?? '',
                    'vocal_url' => $vocalUrl ?? '',
                ];
            }

            if (in_array($successFlag, ['FAILED', 'ERROR'])) {
                $errorMsg = $taskData['errorMessage'] ?? $taskData['error'] ?? 'Unknown error';

                return ['status' => 'failed', 'error' => $errorMsg];
            }

            return ['status' => 'processing'];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Vocal separation status network error: '.$e->getMessage());

            return ['status' => 'processing'];
        } catch (\Exception $e) {
            Log::error('Vocal separation status error: '.$e->getMessage());

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Ожидание результата разделения — как в suno_service.py wait_for_vocal_separation
     */
    public function waitForVocalSeparation(string $taskId, int $timeout = 600, int $interval = 15): array
    {
        // Начальная задержка 30 сек (как в боте)
        sleep(30);

        $elapsed = 30;
        $errorCount = 0;
        $maxErrors = 5;

        while ($elapsed < $timeout) {
            try {
                $result = $this->checkVocalSeparationStatus($taskId);

                if ($result['status'] === 'completed') {
                    return $result;
                }
                if ($result['status'] === 'failed') {
                    return $result;
                }

                $errorCount = 0;

            } catch (\Exception $e) {
                Log::error('Vocal separation loop error: '.$e->getMessage());
                $errorCount++;
                if ($errorCount >= $maxErrors) {
                    return ['status' => 'failed', 'error' => "Too many errors: {$e->getMessage()}"];
                }
            }

            sleep($interval);
            $elapsed += $interval;
        }

        return ['status' => 'failed', 'error' => 'Timeout (время ожидания истекло)'];
    }

    /**
     * Нормализация для поиска артистов: убираем диакритику, lowercase
     */
    private function normalizeForSearch(string $text): string
    {
        $lower = mb_strtolower($text);
        $map = ['ë' => 'e', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ü' => 'u', 'ö' => 'o',
            'ä' => 'a', 'á' => 'a', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'ø' => 'o', 'å' => 'a'];

        return str_replace(array_keys($map), array_values($map), $lower);
    }

    private function isWordBoundary(string $text, int $pos, int $len): bool
    {
        // Символ перед найденной подстрокой
        if ($pos > 0) {
            $charBefore = mb_substr($text, $pos - 1, 1);
            // Если перед — буква или цифра, значит мы внутри слова
            if (preg_match('/[\w\p{L}]/u', $charBefore)) {
                return false;
            }
        }

        // Символ после
        $afterPos = $pos + $len;
        if ($afterPos < mb_strlen($text)) {
            $charAfter = mb_substr($text, $afterPos, 1);
            if (preg_match('/[\w\p{L}]/u', $charAfter)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Создать персону из песни
     */
    public function generatePersona(array $params): array
    {
        $payload = [
            'taskId' => $params['task_id'],
            'audioId' => $params['audio_id'],
            'name' => $params['name'],
            'description' => $params['description'],
        ];
        if (! empty($params['style'])) {
            $payload['style'] = $params['style'];
        }
        if (! empty($params['vocal_start'])) {
            $payload['vocalStart'] = $params['vocal_start'];
        }
        if (! empty($params['vocal_end'])) {
            $payload['vocalEnd'] = $params['vocal_end'];
        }

        Log::info('Persona generate request: '.json_encode($payload));

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->apiUrl}/generate/generate-persona", $payload);

            $data = $response->json();
            Log::info('Persona generate response: '.$response->body());

            if (($data['code'] ?? 0) == 200 && ! empty($data['data']['personaId'])) {
                return [
                    'success' => true,
                    'persona_id' => $data['data']['personaId'],
                ];
            }

            return ['success' => false, 'error' => $data['msg'] ?? 'Ошибка API'];
        } catch (\Exception $e) {
            Log::error('Persona generate error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ===================================================================
    //  Операции над треками (extend / cover / instrumental / vocals / ...)
    //  Все они возвращают { code:200, data:{ taskId } } и отдают результат
    //  через тот же checkStatus()/record-info, что и обычная генерация.
    // ===================================================================

    /** Заглушка callback — результат забираем поллингом (как в generateMusic). */
    private const TRACK_OP_CALLBACK = 'https://example.com/callback';

    /** Базовый URL провайдера по api_source песни. */
    private function baseUrlFor(?string $apiSource): string
    {
        return $apiSource === 'kie'
            ? rtrim((string) config('services.kie.api_url'), '/')
            : rtrim($this->apiUrl, '/');
    }

    /** API-ключ провайдера по api_source песни. */
    private function authKeyFor(?string $apiSource): string
    {
        return $apiSource === 'kie' ? config('services.kie.api_key') : $this->apiKey;
    }

    /**
     * Общий POST к endpoint'ам генерации с retry и парсингом taskId.
     */
    private function submitGeneration(string $path, array $payload, ?string $apiSource = null): array
    {
        $url = $this->baseUrlFor($apiSource).'/'.ltrim($path, '/');
        $maxRetries = 3;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$this->authKeyFor($apiSource),
                    'Content-Type' => 'application/json',
                ])->timeout(60)->post($url, $payload);

                $status = $response->status();
                Log::info("TrackOp POST {$path} attempt {$attempt}: status={$status}");

                if (in_array($status, [502, 503, 504])) {
                    $lastError = "Сервер генерации недоступен (HTTP {$status})";
                    if ($attempt < $maxRetries) {
                        sleep(5 * $attempt);

                        continue;
                    }

                    return ['success' => false, 'error' => $lastError, 'retry_possible' => true];
                }

                $data = $response->json();

                if ($response->successful() && ($data['code'] ?? 0) == 200) {
                    $taskId = $data['data']['taskId'] ?? null;
                    if ($taskId) {
                        return ['success' => true, 'task_id' => $taskId];
                    }

                    return ['success' => false, 'error' => 'Не получен ID задачи'];
                }

                $errorMsg = $data['msg'] ?? ("HTTP {$status}: ".mb_substr($response->body(), 0, 120));

                // 413 / catalog match — Suno не принимает известные записи
                // из каталога (защита авторских прав)
                $code = $data['code'] ?? $status;
                if ($code == 413 || str_contains(strtolower($errorMsg), 'matches an existing recording')) {
                    return [
                        'success' => false,
                        'catalog_match' => true,
                        'error' => 'Эта запись совпадает с известной песней из каталога — Suno не обрабатывает такие файлы (защита авторских прав). Но можно сделать ремейк: возьмём текст песни и создадим новую версию в вашем стиле.',
                    ];
                }

                return ['success' => false, 'error' => $this->cleanErrorMessage($errorMsg)];
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = 'Ошибка подключения: '.$e->getMessage();
                if ($attempt < $maxRetries) {
                    sleep(5 * $attempt);

                    continue;
                }
            } catch (\Exception $e) {
                Log::error("TrackOp {$path} exception: ".$e->getMessage());

                return ['success' => false, 'error' => $this->cleanErrorMessage($e->getMessage())];
            }
        }

        return ['success' => false, 'error' => $lastError ?? 'Не удалось подключиться к серверу генерации', 'retry_possible' => true];
    }

    /**
     * Добавляет необязательные общие параметры (vocalGender, negativeTags,
     * persona, веса) в payload, не перетирая уже заданные ключи.
     */
    private function applyCommonOptions(array &$payload, array $params): void
    {
        $map = [
            'vocal_gender' => 'vocalGender',
            'negative_tags' => 'negativeTags',
            'persona_id' => 'personaId',
            'persona_model' => 'personaModel',
            'style_weight' => 'styleWeight',
            'weirdness_constraint' => 'weirdnessConstraint',
            'audio_weight' => 'audioWeight',
        ];

        foreach ($map as $in => $out) {
            if (array_key_exists($out, $payload)) {
                continue;
            }
            if (isset($params[$in]) && $params[$in] !== '' && $params[$in] !== null) {
                $payload[$out] = $params[$in];
            }
        }
    }

    private function defaultModel(): string
    {
        return (string) config('services.track_ops.model', 'V5_5');
    }

    /**
     * Нормализация пользовательского стиля для операций над треками:
     * убираем эмодзи, маппим русские жанры и подменяем имена артистов
     * на описание стиля (словарь + AI-фоллбэк) — как в generateMusic().
     */
    private function normalizeUserStyle(?string $style): ?string
    {
        if ($style === null || trim($style) === '') {
            return $style;
        }

        foreach (['🎶', '🎤', '🕺', '🎸', '🎻', '🎹', '⚡', '🌿', '🎵', '✨'] as $emoji) {
            $style = str_replace($emoji, '', $style);
        }
        $style = trim($style);

        $style = $this->genreMap[$style] ?? $style;

        $original = $style;
        $style = $this->replaceArtistsInStyle($style);

        if ($style === $original && $this->mightContainArtist($original)) {
            Log::info("TrackOp: potential artist detected, asking AI: '{$original}'");
            $style = $this->replaceArtistWithAI($original);
        }

        if ($original !== $style) {
            Log::info("TrackOp artist replacement: '{$original}' -> '{$style}'");
        }

        return $style;
    }

    /**
     * Продление существующего трека (Extend Music).
     * Нужен audio_id исходного клипа; api_source должен совпадать с источником.
     */
    public function extendMusic(array $params): array
    {
        $params['style'] = $this->normalizeUserStyle($params['style'] ?? null);

        // Кастомный режим — только если указана точка продолжения:
        // Suno требует continueAt при defaultParamFlag=true, иначе «continueAt cannot be null».
        // Без точки — продлеваем с конца в исходном стиле (defaultParamFlag=false).
        $hasContinue = isset($params['continue_at']) && $params['continue_at'] !== null && $params['continue_at'] !== '';

        $payload = [
            'defaultParamFlag' => $hasContinue,
            'audioId' => $params['audio_id'],
            'model' => $params['model'] ?? $this->defaultModel(),
            'callBackUrl' => self::TRACK_OP_CALLBACK,
        ];

        if ($hasContinue) {
            $payload['continueAt'] = (float) $params['continue_at'];
            if (! empty($params['style'])) {
                $payload['style'] = mb_substr($params['style'], 0, 1000);
            }
            if (! empty($params['title'])) {
                $payload['title'] = mb_substr($params['title'], 0, 100);
            }
            if (! empty($params['prompt'])) {
                $payload['prompt'] = mb_substr($params['prompt'], 0, 5000);
            }
        }

        $this->applyCommonOptions($payload, $params);

        return $this->submitGeneration('generate/extend', $payload, $params['api_source'] ?? null);
    }

    /**
     * Кавер на загруженный аудиофайл (Upload & Cover Audio).
     */
    public function uploadCover(array $params): array
    {
        $params['style'] = $this->normalizeUserStyle($params['style'] ?? null);

        $custom = $params['custom_mode'] ?? true;
        $instrumental = (bool) ($params['instrumental'] ?? false);

        $payload = [
            'uploadUrl' => $params['upload_url'],
            'customMode' => $custom,
            'instrumental' => $instrumental,
            'model' => $params['model'] ?? $this->defaultModel(),
            'callBackUrl' => self::TRACK_OP_CALLBACK,
        ];

        if ($custom) {
            $payload['style'] = mb_substr($params['style'] ?? '', 0, 1000);
            $payload['title'] = mb_substr($params['title'] ?? '', 0, 100);
            if (! $instrumental) {
                $payload['prompt'] = mb_substr($params['prompt'] ?? '', 0, 5000);
            }
        } else {
            // Non-custom: единственное поле — prompt-описание (стиль уже
            // нормализован выше, приклеиваем его к описанию)
            $prompt = trim($params['prompt'] ?? '');
            if (! empty($params['style'])) {
                $prompt = trim($prompt.' Style: '.$params['style']);
            }
            $payload['prompt'] = mb_substr($prompt, 0, 500);
        }

        $this->applyCommonOptions($payload, $params);

        return $this->submitGeneration('generate/upload-cover', $payload, $params['api_source'] ?? null);
    }

    /**
     * Продление загруженного аудиофайла (Upload & Extend Audio).
     */
    public function uploadExtend(array $params): array
    {
        $params['style'] = $this->normalizeUserStyle($params['style'] ?? null);

        $hasContinue = isset($params['continue_at']) && $params['continue_at'] !== null && $params['continue_at'] !== '';

        $payload = [
            'uploadUrl' => $params['upload_url'],
            'defaultParamFlag' => $hasContinue,
            'model' => $params['model'] ?? $this->defaultModel(),
            'callBackUrl' => self::TRACK_OP_CALLBACK,
        ];

        if (isset($params['instrumental'])) {
            $payload['instrumental'] = (bool) $params['instrumental'];
        }

        if ($hasContinue) {
            $payload['continueAt'] = (float) $params['continue_at'];
            if (! empty($params['style'])) {
                $payload['style'] = mb_substr($params['style'], 0, 1000);
            }
            if (! empty($params['title'])) {
                $payload['title'] = mb_substr($params['title'], 0, 100);
            }
            if (empty($params['instrumental']) && ! empty($params['prompt'])) {
                $payload['prompt'] = mb_substr($params['prompt'], 0, 5000);
            }
        }

        $this->applyCommonOptions($payload, $params);

        return $this->submitGeneration('generate/upload-extend', $payload, $params['api_source'] ?? null);
    }

    /**
     * Превратить вокал/мелодию в инструментал (Add Instrumental).
     * Поддерживает только V4_5PLUS / V5 / V5_5.
     */
    public function addInstrumental(array $params): array
    {
        $params['tags'] = $this->normalizeUserStyle($params['tags'] ?? null);

        $payload = [
            'uploadUrl' => $params['upload_url'],
            'title' => mb_substr($params['title'] ?? '', 0, 100),
            'tags' => mb_substr($params['tags'] ?? '', 0, 1000),
            'negativeTags' => mb_substr($params['negative_tags'] ?? '', 0, 1000),
            'model' => $params['model'] ?? $this->defaultModel(),
            'callBackUrl' => self::TRACK_OP_CALLBACK,
        ];

        $this->applyCommonOptions($payload, $params);

        return $this->submitGeneration('generate/add-instrumental', $payload, $params['api_source'] ?? null);
    }

    /**
     * Добавить вокал к инструменталу (Add Vocals).
     * Поддерживает только V4_5PLUS / V5 / V5_5.
     */
    public function addVocals(array $params): array
    {
        $params['style'] = $this->normalizeUserStyle($params['style'] ?? null);

        $payload = [
            'uploadUrl' => $params['upload_url'],
            'prompt' => mb_substr($params['prompt'] ?? '', 0, 5000),
            'title' => mb_substr($params['title'] ?? '', 0, 100),
            'style' => mb_substr($params['style'] ?? '', 0, 1000),
            'negativeTags' => mb_substr($params['negative_tags'] ?? '', 0, 1000),
            'model' => $params['model'] ?? $this->defaultModel(),
            'callBackUrl' => self::TRACK_OP_CALLBACK,
        ];

        $this->applyCommonOptions($payload, $params);

        return $this->submitGeneration('generate/add-vocals', $payload, $params['api_source'] ?? null);
    }

    /**
     * Мэшап двух треков (Generate Mashup). Нужны ровно 2 публичных URL.
     * Модель mashup НЕ поддерживает V5_5 — по умолчанию V5.
     */
    public function mashup(array $params): array
    {
        $params['style'] = $this->normalizeUserStyle($params['style'] ?? null);

        $custom = (bool) ($params['custom_mode'] ?? false);
        // Мэшап по умолчанию — с вокалом (иначе получается голый инструментал)
        $instrumental = (bool) ($params['instrumental'] ?? false);

        $payload = [
            'uploadUrlList' => array_values($params['upload_urls']),
            'customMode' => $custom,
            'instrumental' => $instrumental,
            'model' => $params['model'] ?? 'V5',
            'callBackUrl' => self::TRACK_OP_CALLBACK,
        ];

        if ($custom) {
            $payload['style'] = mb_substr($params['style'] ?? '', 0, 1000);
            $payload['title'] = mb_substr($params['title'] ?? '', 0, 100);
            if (! $instrumental) {
                $payload['prompt'] = mb_substr($params['prompt'] ?: 'Vocal mashup blending both songs, keep singing and lyrics', 0, 5000);
            }
        } else {
            // non-custom: prompt обязателен; пустой -> Suno склонен отдавать инструментал
            $payload['prompt'] = mb_substr($params['prompt'] ?: 'Vocal mashup of the two songs, keep vocals and lyrics, energetic', 0, 3000);
        }

        $this->applyCommonOptions($payload, $params);

        return $this->submitGeneration('generate/mashup', $payload, $params['api_source'] ?? null);
    }

    /**
     * Заменить фрагмент трека (Replace Section). Стоит ~5 кредитов.
     * Нужны task_id (родительская задача) и audio_id того же провайдера.
     */
    public function replaceSection(array $params): array
    {
        $params['tags'] = $this->normalizeUserStyle($params['tags'] ?? null);

        $payload = [
            'taskId' => $params['task_id'],
            'audioId' => $params['audio_id'],
            'prompt' => mb_substr($params['prompt'] ?? '', 0, 5000),
            'tags' => mb_substr($params['tags'] ?? '', 0, 1000),
            'title' => mb_substr($params['title'] ?? '', 0, 100),
            'fullLyrics' => $params['full_lyrics'] ?? '',
            'infillStartS' => round((float) $params['infill_start_s'], 2),
            'infillEndS' => round((float) $params['infill_end_s'], 2),
            'callBackUrl' => self::TRACK_OP_CALLBACK,
        ];

        if (! empty($params['negative_tags'])) {
            $payload['negativeTags'] = mb_substr($params['negative_tags'], 0, 1000);
        }

        return $this->submitGeneration('generate/replace-section', $payload, $params['api_source'] ?? null);
    }
}
