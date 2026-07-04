<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LyricsGeneratorService
{
    private string $provider;

    private array $newParamModels = [
        'o1-preview', 'o1-mini', 'o1', 'o3-mini',
        'gpt-4.5-preview', 'gpt-5', 'gpt-5.5',
    ];

    public const LANGUAGES = [
        'ru' => 'Russian',
        'en' => 'English',
        'de' => 'German',
        'es' => 'Spanish',
        'fr' => 'French',
        'it' => 'Italian',
    ];

    // === GENRE_RULES — точная копия из openai_service.py ===
    private array $genreRules = [
        'default' => 'Match the style specified by the user. Use characteristic vocabulary, rhythm, and techniques for this genre. Structure: [Verse] - [Chorus] - [Verse] - [Chorus] - [Bridge] - [Chorus].',
        'rap' => 'Use complex rhyme schemes (multisyllabic, internal rhymes), slang, and street vocabulary. Focus on a heavy, bouncing flow. Avoid cheesy pop lines. Structure: [Verse 1] - [Chorus | Hook] - [Verse 2] - [Chorus] - [Outro].',
        'rock' => 'Use expressive, rebellious, or gritty imagery. The lyrics must be emotional and energetic. Structure should fit powerful guitar riffs and heavy drums.',
        'metal' => 'Themes: struggle, darkness, epic battles, power, or chaos. Use dark metaphors. Add [Growl] or [Scream] tags before aggressive sections.',
        'pop' => 'Lyrics must be catchy and hook-driven. The Chorus must be viral and easy to sing along to. Use modern conversational language about love, life, or parties.',
        'rnb' => 'Sensual, smooth style. Focus on emotions, relationships, passion. Use soft rhymes and indicate runs/ad-libs in tags.',
        'electronic' => 'Minimalist lyrics. Short, hypnotic phrases. Focus on repetition before the Drop. Structure: [Verse] - [Buildup] - [Drop] - [Break] - [Drop].',
        'shanson' => 'Soulful storytelling lyrics about life, destiny, and friendship. The tone should be sincere and confessional.',
        'jazz' => 'Sophisticated lyrics, elegant metaphors, nighttime atmosphere. Allow for light melancholy, irony, or romance. Smooth, swinging flow.',
        'blues' => 'Melancholy, hardship, longing. Use the classic AAB structure. Raw, gritty emotion.',
        'country' => 'Simple storytelling about daily life, roads, home. Sincere, narrative style.',
        'reggae' => 'Laid-back, positive vibe. Themes: peace, love, freedom, summer. Simple, bouncing rhythm (off-beat phrasing).',
        'latin' => 'Passion, dancing, fire, desire. Rhythmic lyrics that make you want to move.',
        'folk' => 'Nature imagery, legends, folklore. Poetic, melodic style. Atmospheric and storytelling.',
        'disco' => 'Celebration, glitter, dancefloor, night. Positive and energetic. Rhythmic structure fitting a 4/4 beat.',
        'indie' => 'Melancholic, dreamy, or abstract imagery. Introspective, personal lyrics. Unique and non-standard metaphors.',
        'kids' => 'Very simple words, kind themes, lots of repetition. Educational or fun. Easy for a child to understand.',
    ];

    // === VALID SUNO TAGS for prompt (from suno_tags.py) ===
    private function getValidTagsForPrompt(): string
    {
        return 'STRUCTURE TAGS: [Intro], [Verse], [Verse 1], [Verse 2], [Verse 3], [Pre-Chorus], [Chorus], [Post-Chorus], [Bridge], [Outro], [Hook], [Break], [Drop], [Buildup], [Instrumental], [Instrumental Break], [Interlude], [Solo], [Guitar Solo], [Fade Out], [Fade In]
VOCAL STYLE TAGS: [Whisper], [Spoken Word], [Rap], [Harmonies], [Stacked Harmonies], [Falsetto], [Belting], [Growl], [Scream], [Crooning], [Operatic], [Scat], [Anthemic Chorus], [Crowd-style Vocals], [Raspy Lead Vocal], [Autotuned Delivery]
VOCAL EMOTION TAGS: [Vulnerable], [Powerful], [Soft], [Aggressive], [Melancholic], [Joyful], [Sultry], [Defiant], [Emotional Build-up]
MOOD TAGS: [Uplifting], [Melancholic], [Haunting], [Dark], [Joyful], [Nostalgic], [Somber], [Romantic], [Intense], [Dreamy], [Peaceful], [Anxious], [Euphoric], [Mysterious], [Aggressive], [Playful], [Epic], [Intimate], [Bittersweet], [Triumphant], [Warm], [Dark Atmosphere], [Bright Atmosphere]
ENERGY TAGS: [High Energy], [Medium Energy], [Low Energy], [Chill], [Driving], [Explosive], [Building], [Relaxed], [Frantic], [Steady]
INSTRUMENT TAGS: [Piano], [Electric Piano], [Rhodes], [Organ], [Synth], [Analog Synth], [Synth Pad], [Lead Synth], [Synth Bass], [Acoustic Guitar], [Electric Guitar], [Distorted Guitar], [Guitar Solo], [Bass Guitar], [Slap Bass], [Ukulele], [Banjo], [Drums], [Acoustic Drums], [Electronic Drums], [808s], [808 Bass], [Drum Machine], [Breakbeat], [Percussion], [Saxophone], [Trumpet], [Brass Section], [Flute], [Harmonica], [Accordion], [Violin], [Strings], [String Quartet], [Orchestral Strings], [Cello], [Harp], [Orchestra], [Full Orchestra]
PRODUCTION TAGS: [Lo-fi], [Gritty], [Clean], [Raw], [Lush], [Sparse], [Atmospheric], [Punchy], [Warm], [Bright], [Polished], [Wide Stereo], [Heavy Distortion], [Reverb Heavy]
DYNAMIC TAGS: [Crescendo], [Diminuendo], [Forte], [Piano Dynamic], [Sforzando]
SOUND EFFECTS: [Rain], [Thunder], [Wind], [Ocean Waves], [Fire Crackling], [Birds Chirping], [City Ambience], [Applause], [Record Scratch], [Silence], [Vinyl Crackle], [Risers], [Impacts]';
    }

    // === Mood tags recommended per genre (from suno_tags.py) ===
    private function getMoodTagsForGenre(string $key): array
    {
        $map = [
            'rap' => ['Aggressive', 'Defiant', 'Dark', 'Intense', 'High Energy', 'Driving'],
            'rock' => ['Intense', 'Driving', 'Aggressive', 'Epic', 'High Energy', 'Powerful'],
            'metal' => ['Aggressive', 'Dark', 'Intense', 'Epic', 'Frantic', 'Explosive'],
            'pop' => ['Uplifting', 'Joyful', 'Romantic', 'Dreamy', 'Euphoric', 'Playful'],
            'rnb' => ['Sultry', 'Romantic', 'Intimate', 'Warm', 'Soft', 'Dreamy'],
            'electronic' => ['Euphoric', 'Driving', 'Atmospheric', 'Intense', 'Explosive', 'Dreamy'],
            'jazz' => ['Intimate', 'Warm', 'Nostalgic', 'Romantic', 'Peaceful', 'Mysterious'],
            'blues' => ['Melancholic', 'Bittersweet', 'Warm', 'Intimate', 'Somber', 'Vulnerable'],
            'country' => ['Warm', 'Nostalgic', 'Joyful', 'Bittersweet', 'Intimate', 'Uplifting'],
            'reggae' => ['Peaceful', 'Joyful', 'Warm', 'Relaxed', 'Chill', 'Uplifting'],
            'folk' => ['Nostalgic', 'Peaceful', 'Intimate', 'Warm', 'Dreamy', 'Melancholic'],
            'disco' => ['Euphoric', 'Joyful', 'High Energy', 'Driving', 'Playful', 'Uplifting'],
            'indie' => ['Dreamy', 'Melancholic', 'Intimate', 'Nostalgic', 'Atmospheric', 'Bittersweet'],
            'shanson' => ['Nostalgic', 'Melancholic', 'Warm', 'Intimate', 'Bittersweet', 'Somber'],
            'latin' => ['Intense', 'Romantic', 'Joyful', 'High Energy', 'Driving', 'Sultry'],
            'kids' => ['Playful', 'Joyful', 'Uplifting', 'Bright Atmosphere', 'Warm'],
        ];

        return $map[$key] ?? ['Uplifting', 'Melancholic', 'Haunting', 'Dark', 'Joyful', 'Nostalgic'];
    }

    private function getInstrumentTagsForGenre(string $key): array
    {
        $map = [
            'rap' => ['808s', 'Drums', 'Synth Bass', 'Synth'],
            'rock' => ['Electric Guitar', 'Drums', 'Bass Guitar', 'Distorted Guitar'],
            'metal' => ['Distorted Guitar', 'Drums', 'Bass Guitar', 'Timpani'],
            'pop' => ['Piano', 'Synth', 'Drums', 'Acoustic Guitar'],
            'rnb' => ['Electric Piano', 'Synth Bass', 'Drums', 'Strings'],
            'electronic' => ['Synth', 'Electronic Drums', 'Synth Bass', 'Lead Synth'],
            'jazz' => ['Piano', 'Saxophone', 'Brush Drums', 'Bass Guitar'],
            'blues' => ['Electric Guitar', 'Harmonica', 'Drums', 'Bass Guitar'],
            'country' => ['Acoustic Guitar', 'Banjo', 'Drums', 'Harmonica'],
            'reggae' => ['Bass Guitar', 'Drums', 'Acoustic Guitar', 'Organ'],
            'folk' => ['Acoustic Guitar', 'Violin', 'Flute', 'Accordion'],
            'disco' => ['Synth Bass', 'Drums', 'Brass Section', 'Strings'],
            'indie' => ['Acoustic Guitar', 'Synth', 'Drums', 'Piano'],
            'shanson' => ['Acoustic Guitar', 'Accordion', 'Piano', 'Violin'],
            'latin' => ['Percussion', 'Acoustic Guitar', 'Brass Section', 'Congas'],
            'kids' => ['Piano', 'Ukulele', 'Acoustic Guitar', 'Tambourine'],
        ];

        return $map[$key] ?? ['Piano', 'Drums', 'Bass Guitar'];
    }

    // === SYSTEM PROMPTS — exact copy from openai_service.py ===

    private string $systemPromptRu = 'You are a professional ghostwriter and hitmaker. Write song lyrics in RUSSIAN language.

### CRITICAL TAG RULES:
ALL tags MUST be in ENGLISH using ONLY valid Suno AI tags listed below.
The LYRICS TEXT should be in Russian, but ALL [tags] must be in English.
Do NOT invent custom tags. Use ONLY tags from the approved list.
Use the | separator to stack tags: [Chorus | Anthemic Chorus | Stacked Harmonies]
Maximum 3-4 tags per section. Place them at the beginning of each section line.
Write all numbers as WORDS (five, hundred). Digits are forbidden.

### VALID TAGS YOU CAN USE:
{valid_tags}

### YOUR TASK:
1. ANALYZE the request: identify mood, key themes, and style.
2. STRUCTURE: Use [Verse 1], [Chorus], [Bridge], [Outro] etc.
3. MOOD TAGS: Add 1-2 mood/energy tags per section from the approved list.
4. Write emotionally resonant Russian lyrics.

### GENRE INSTRUCTIONS:
{genre_instruction}

### ARTIST STYLE:
{artist_instruction}

### OUTPUT FORMAT:
COMMENT: Your brief analysis (in Russian).
TITLE: Song title (in Russian)
LYRICS:
[Verse 1 | Mood Tag]
Russian lyrics here...

[Chorus | Mood Tag | Energy Tag]
Russian lyrics here...';

    private string $systemPromptEn = 'You are a professional Billboard-charting songwriter. Write a hit song in ENGLISH.

### CRITICAL TAG RULES:
ALL tags MUST be in ENGLISH using ONLY valid Suno AI tags listed below.
Do NOT invent custom tags. Use ONLY tags from the approved list.
Use the | separator to stack tags: [Chorus | Anthemic Chorus | Stacked Harmonies]
Maximum 3-4 tags per section. Place them at the beginning of each section line.
Write all numbers as WORDS (five, hundred). Digits are forbidden.

### VALID TAGS YOU CAN USE:
{valid_tags}

### YOUR TASK:
1. ANALYZE the request: identify mood, hooks, and imagery.
2. STRUCTURE: Use [Verse], [Chorus], [Bridge], [Outro] etc.
3. MOOD TAGS: Add 1-2 mood/energy tags per section from the approved list.
4. Write catchy, radio-ready English lyrics.

### GENRE INSTRUCTIONS:
{genre_instruction}

### ARTIST STYLE:
{artist_instruction}

### OUTPUT FORMAT:
COMMENT: Short analysis and message to the user.
TITLE: Song Title
LYRICS:
[Verse 1 | Mood Tag]
Lyrics...

[Chorus | Mood Tag | Energy Tag]
Lyrics...';

    private string $systemPromptUniversal = 'You are a professional hit songwriter.
Write a song in **{target_language}**.

### CRITICAL TAG RULES:
ALL tags MUST be in ENGLISH using ONLY valid Suno AI tags.
The LYRICS TEXT should be in {target_language}, but ALL [tags] must be in English.
Do NOT invent custom tags. Use ONLY tags from the approved list.
Use the | separator to stack tags: [Chorus | Anthemic Chorus | Stacked Harmonies]
Maximum 3-4 tags per section.
Write numbers as words.

### VALID TAGS:
{valid_tags}

### TASK:
1. Analyze the request (mood, topic).
2. Write lyrics strictly in **{target_language}**.
3. Use structure tags: [Verse], [Chorus], [Bridge] etc.
4. Add 1-2 mood/energy tags per section.

### GENRE RULES:
{genre_instruction}

### ARTIST STYLE:
{artist_instruction}

### OUTPUT FORMAT:
COMMENT: Short comment in {target_language}
TITLE: Song Title
LYRICS:
[Verse 1 | Mood Tag]
...';

    // === STRUCTURE PROMPT (for formatting own lyrics) ===
    private string $structurePrompt = 'You are a song structure expert. Your task is to add structural and mood tags to song lyrics.

CRITICAL RULES:
1. You MUST use ONLY the following valid Suno AI tags. No other tags are allowed.
2. ALL tags must be in ENGLISH, even if lyrics are in Russian or another language.
3. Place tags at the beginning of each section.
4. You can stack tags using | separator: [Chorus | Anthemic Chorus | Stacked Harmonies]
5. Keep the original lyrics intact - only add tags.
6. Use 2-3 tags per section maximum (structure + mood/energy).

VALID STRUCTURE TAGS:
[Intro], [Verse], [Verse 1], [Verse 2], [Verse 3], [Pre-Chorus], [Chorus], [Post-Chorus], [Bridge], [Outro], [Hook], [Break], [Drop], [Buildup], [Instrumental], [Instrumental Break], [Interlude], [Solo], [Guitar Solo], [Fade Out]

VALID MOOD/ENERGY TAGS:
[Uplifting], [Melancholic], [Dark], [Joyful], [Nostalgic], [Romantic], [Intense], [Dreamy], [Peaceful], [Euphoric], [Mysterious], [Aggressive], [Epic], [Intimate], [Bittersweet], [Triumphant], [Warm], [Haunting]
[High Energy], [Low Energy], [Chill], [Driving], [Explosive], [Building], [Relaxed], [Steady]

VALID VOCAL TAGS:
[Whisper], [Spoken Word], [Rap], [Harmonies], [Falsetto], [Belting], [Growl], [Soft], [Powerful], [Vulnerable], [Aggressive], [Sultry], [Defiant]

Output ONLY the formatted lyrics with tags, no explanations.';

    public function __construct()
    {
        $this->provider = config('services.ai_provider', 'openai');
    }

    public static function getLanguages(): array
    {
        return self::LANGUAGES;
    }

    /**
     * Генерация текста песни — полная синхронизация с openai_service.py generate_song_lyrics
     */
    public function generate(array $params): array
    {
        $occasion = $params['occasion'] ?? 'Не указан';
        $genre = $params['genre'] ?? 'Поп';
        $description = $params['description'] ?? '';
        $language = $params['language'] ?? 'ru';
        $artist = $params['artist'] ?? null;
        $vocalGender = $params['vocal_gender'] ?? null;

        $langName = self::LANGUAGES[$language] ?? 'Russian';
        $validTags = $this->getValidTagsForPrompt();
        $genreInstr = $this->resolveGenreInstruction($genre, $language);

        $artistInstr = 'Create a unique style suitable for the genre.';
        if ($artist) {
            $artistInstr = "Inspiration: {$artist}. Capture their vibe, flow, and typical themes. Do not copy specific lyrics, but mimic their unique style perfectly.";
        }

        $genderInstr = $this->buildGenderInstruction($vocalGender, $language);

        if ($language === 'ru') {
            $systemPrompt = str_replace(
                ['{valid_tags}', '{genre_instruction}', '{artist_instruction}'],
                [$validTags, $genreInstr, $artistInstr],
                $this->systemPromptRu
            );
            if ($genderInstr) {
                $systemPrompt .= "\n\n### VOCALIST GENDER:\n{$genderInstr}";
            }
            $userPrompt = "Повод/Тема: {$occasion}\nДетали/История: {$description}\n\nНапиши песню на русском языке. Все теги — на английском!";

        } elseif ($language === 'en') {
            $systemPrompt = str_replace(
                ['{valid_tags}', '{genre_instruction}', '{artist_instruction}'],
                [$validTags, $genreInstr, $artistInstr],
                $this->systemPromptEn
            );
            if ($genderInstr) {
                $systemPrompt .= "\n\n### VOCALIST GENDER:\n{$genderInstr}";
            }
            $userPrompt = "Topic: {$occasion}\nDetails: {$description}\n\nWrite lyrics in English.";

        } else {
            $systemPrompt = str_replace(
                ['{target_language}', '{valid_tags}', '{genre_instruction}', '{artist_instruction}'],
                [$langName, $validTags, $genreInstr, $artistInstr],
                $this->systemPromptUniversal
            );
            if ($genderInstr) {
                $systemPrompt .= "\n\n### VOCALIST GENDER:\n{$genderInstr}";
            }
            $userPrompt = "Topic: {$occasion}\nDetails: {$description}\n\nWrite lyrics in {$langName}. ALL tags must be in English!";
        }

        try {
            $result = $this->aiGenerate($systemPrompt, $userPrompt);
            $parsed = $this->parseResponse($result);

            // Auto-generate title if generic
            if (in_array($parsed['title'], ['Моя песня', 'My Song', '']) || empty(trim($parsed['title']))) {
                $parsed['title'] = $this->generateTitleFromLyrics($parsed['lyrics']);
            }

            return $parsed;
        } catch (\Exception $e) {
            Log::error('Lyrics generation error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Перевод текста — как в openai_service.py translate_lyrics
     */
    public function translate(string $lyrics, string $targetLangCode): array
    {
        $targetLang = self::LANGUAGES[$targetLangCode] ?? 'English';

        $systemPrompt = "You are a poetic translator.
Translate the following song lyrics into {$targetLang}.

RULES:
1. Keep the meaning and mood.
2. Try to keep the rhythm and rhyme scheme if possible.
3. KEEP ALL [tags] in English exactly as they are. Do NOT translate tags.
   Tags look like: [Verse 1 | Warm | Soft], [Chorus | High Energy], etc.
4. Only translate the lyrics text, not the tags.
5. Output ONLY the translated text with original English tags.";

        $userPrompt = "Original text:\n{$lyrics}";

        try {
            $result = $this->aiGenerate($systemPrompt, $userPrompt);

            return ['success' => true, 'lyrics' => trim($result)];
        } catch (\Exception $e) {
            Log::error('Translation error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'lyrics' => $lyrics];
        }
    }

    /**
     * Лёгкая переработка текста, чтобы обойти copyright-фингерпринт Suno
     * (413 «matches an existing recording»). Сохраняем смысл, структуру,
     * рифму и язык — меняем формулировки достаточно, чтобы не совпадать
     * с оригиналом дословно.
     */
    public function rephrase(string $lyrics): array
    {
        $isRussian = (bool) preg_match('/[а-яА-ЯёЁ]/u', $lyrics);
        $lang = $isRussian ? 'Russian' : 'English';

        $systemPrompt = "You are a professional songwriter doing a legal cover rewrite.
Rewrite the given lyrics so they are NOT word-for-word identical to the original,
but keep the SAME meaning, mood, story, rhyme scheme, structure and language ({$lang}).

RULES:
1. Change enough wording/phrasing on every line so it is not a verbatim copy,
   but a listener should still recognize the same song's meaning and feel.
2. Keep the same number of lines and overall structure.
3. Keep the LYRICS TEXT in {$lang}.
4. Keep any [tags] in English exactly as they are; do not translate or invent tags.
5. Output ONLY the rewritten lyrics, nothing else.";

        $userPrompt = "Original lyrics:\n{$lyrics}";

        try {
            $result = $this->aiGenerate($systemPrompt, $userPrompt);

            return ['success' => true, 'lyrics' => trim($result)];
        } catch (\Exception $e) {
            Log::error('Rephrase error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'lyrics' => $lyrics];
        }
    }

    /**
     * Улучшение текста — как в openai_service.py improve_song_lyrics
     */
    public function improve(string $currentLyrics, string $feedback, array $params): array
    {
        $genre = $params['genre'] ?? '';
        $artist = $params['artist'] ?? null;
        $vocalGender = $params['vocal_gender'] ?? null;

        $isRussian = (bool) preg_match('/[а-яА-ЯёЁ]/u', $currentLyrics);
        $lyricsLang = $isRussian ? 'Russian' : 'English';
        $validTags = $this->getValidTagsForPrompt();
        $genderPres = $this->buildGenderPreservationInstruction($vocalGender, $isRussian ? 'ru' : 'en');

        $systemPrompt = "You are a professional song editor.
Your task is to modify song lyrics based on user feedback, while KEEPING rhyme, rhythm, and structure.

CRITICAL TAG RULES:
- ALL tags MUST be in ENGLISH using ONLY valid Suno AI tags.
- The LYRICS TEXT stays in {$lyricsLang}, but ALL [tags] must be in English.
- Do NOT invent custom tags. Use ONLY tags from the approved list.
- Use | separator to stack tags: [Chorus | Anthemic Chorus | High Energy]

VALID TAGS:
{$validTags}

VOICE TAG PRESERVATION:
{$genderPres}

OUTPUT FORMAT:
COMMENT: (what was changed, in {$lyricsLang})
TITLE: (song title)
LYRICS:
(full song text with PRESERVED voice tags and valid Suno tags)";

        $genderLabel = $this->getGenderLabel($vocalGender);
        $artistText = $artist ?: 'Not specified';

        $userPrompt = "Genre: {$genre}
Artist style: {$artistText}
Voice type: {$genderLabel}
User feedback: {$feedback}

CURRENT LYRICS (KEEP ALL VOICE TAGS!):
{$currentLyrics}

Rewrite the text according to the feedback. Do NOT remove voice tags!
ALL tags must be valid Suno tags in English.";

        try {
            $result = $this->aiGenerate($systemPrompt, $userPrompt);
            $parsed = $this->parseResponse($result);

            if (in_array($parsed['title'], ['Моя песня', 'My Song', '']) || empty(trim($parsed['title']))) {
                $parsed['title'] = $this->generateTitleFromLyrics($parsed['lyrics']);
            }

            // Post-processing: ensure gender tags
            $parsed['lyrics'] = $this->ensureGenderTags($parsed['lyrics'], $vocalGender, $currentLyrics);

            return $parsed;
        } catch (\Exception $e) {
            Log::error('Lyrics improve error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Форматирование структуры своего текста — как в openai_service.py format_lyrics_structure
     */
    public function formatStructure(string $text): array
    {
        try {
            $result = $this->aiGenerate($this->structurePrompt, $text);

            return ['success' => true, 'lyrics' => trim($result)];
        } catch (\Exception $e) {
            return ['success' => false, 'lyrics' => $text, 'error' => $e->getMessage()];
        }
    }

    /**
     * Генерация названия — как в openai_service.py generate_title_from_lyrics
     */
    public function generateTitleFromLyrics(string $lyrics): string
    {
        $sample = mb_substr($lyrics, 0, 500);
        if (empty(trim($sample))) {
            return 'Моя песня';
        }

        $isRussian = (bool) preg_match('/[а-яА-ЯёЁ]/u', $sample);
        $langInstr = $isRussian
            ? 'Придумай название на РУССКОМ языке.'
            : 'Come up with a title in the SAME LANGUAGE as the lyrics.';

        $systemPrompt = "You are a music naming expert. Generate a short, catchy song title based on the lyrics.

RULES:
1. Title must be 1-5 words maximum.
2. {$langInstr}
3. The title should capture the mood or key theme of the song.
4. Be creative — avoid generic titles like \"My Song\", \"Love Song\", \"Моя песня\".
5. Output ONLY the title, nothing else. No quotes, no explanations.";

        try {
            $result = $this->aiGenerate($systemPrompt, "Lyrics:\n{$sample}");
            $title = trim($result);
            $title = trim($title, " \t\n\r\0\x0B\"'");
            $title = preg_replace('/^[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}]+|[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}]+$/u', '', $title);
            if ($title && mb_strlen($title) < 100 && ! in_array(mb_strtolower($title), ['моя песня', 'my song', 'untitled'])) {
                return $title;
            }
        } catch (\Exception $e) {
            Log::warning('generateTitleFromLyrics error: '.$e->getMessage());
        }

        return 'Моя песня';
    }

    public function ensureTitle(?string $title, string $lyrics): string
    {
        $clean = trim((string) $title);
        // убираем кавычки/ёлочки по краям (как в боте)
        $clean = trim($clean, " \t\n\r\0\x0B\"'");
        $clean = preg_replace('/^[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}]+|[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}]+$/u', '', $clean);
        $clean = trim($clean);

        $generic = ['', 'моя песня', 'my song', 'untitled', 'без названия'];
        if (in_array(mb_strtolower($clean), $generic, true)) {
            return $this->generateTitleFromLyrics($lyrics);
        }

        return $clean;
    }

    // ==========================================
    // GENDER INSTRUCTIONS — exact copy from bot
    // ==========================================

    private function buildGenderInstruction(?string $vocalGender, string $language): string
    {
        if (! $vocalGender || $vocalGender === 'random') {
            return '';
        }

        $langNote = '';
        if (in_array(strtolower($language), ['russian', 'русский', 'ru'])) {
            $langNote = ' Use appropriate Russian grammatical gender (masculine verb endings, pronouns).';
        }

        if ($vocalGender === 'm') {
            return "\n\nIMPORTANT: Write the song from a MALE perspective.{$langNote}"
                .' Add [Male Vocal] tag at the very first line of the lyrics, before any other tags.'
                .' Do NOT add [Female Vocal], [Woman] or other female tags.';
        }

        if ($vocalGender === 'f') {
            $langNoteF = '';
            if (in_array(strtolower($language), ['russian', 'русский', 'ru'])) {
                $langNoteF = ' Use appropriate Russian grammatical gender (feminine verb endings, pronouns).';
            }

            return "\n\nIMPORTANT: Write the song from a FEMALE perspective.{$langNoteF}"
                .' Add [Female Vocal] tag at the very first line of the lyrics, before any other tags.'
                .' Do NOT add [Male Vocal], [Man] or other male tags.';
        }

        if ($vocalGender === 'duet') {
            return "\n\nIMPORTANT: This is a DUET song between a man and a woman."
                ."\n\nUse EXACTLY these voice tags:"
                ."\n[Male Vocal] — before parts sung by the man"
                ."\n[Female Vocal] — before parts sung by the woman"
                ."\n[Both] — before parts they sing together"
                ."\n\nSTRUCTURE RULES:"
                ."\n- Start with [duet man and woman] tag on the very first line"
                ."\n- Every section MUST begin with [Male Vocal], [Female Vocal], or [Both]"
                ."\n- Alternate voices: don't give more than 2 consecutive sections to one voice"
                ."\n- Use [Both] for choruses and emotional climaxes"
                ."\n- You can combine voice tag with mood: [Male Vocal] [Verse 1 | Warm]"
                .$langNote;
        }

        return '';
    }

    private function buildGenderPreservationInstruction(?string $vocalGender, string $lang): string
    {
        if (! $vocalGender || $vocalGender === 'random') {
            return 'Keep any existing voice tags ([Male Vocal], [Female Vocal], etc.) if present in the text.';
        }
        if ($vocalGender === 'm') {
            return 'THIS IS A MALE VOICE. The text MUST have [Male Vocal] tag at the very beginning. If [Male Vocal] is missing — ADD it. Do NOT add [Female Vocal], [Woman] or other female tags.';
        }
        if ($vocalGender === 'f') {
            return 'THIS IS A FEMALE VOICE. The text MUST have [Female Vocal] tag at the very beginning. If [Female Vocal] is missing — ADD it. Do NOT add [Male Vocal], [Man] or other male tags.';
        }
        if ($vocalGender === 'duet') {
            return 'THIS IS A DUET (man and woman). The text MUST start with [duet man and woman] tag. Every section MUST begin with [Male Vocal], [Female Vocal], or [Both]. KEEP the alternation of voices as in the original. Do NOT remove or merge [Male Vocal]/[Female Vocal]/[Both] tags.';
        }

        return '';
    }

    private function getGenderLabel(?string $vocalGender): string
    {
        $labels = [
            'm' => 'Мужской голос',
            'f' => 'Женский голос',
            'duet' => 'Дуэт (мужчина + женщина)',
            'random' => 'Случайный',
        ];

        return $labels[$vocalGender] ?? 'Не указан';
    }

    private function ensureGenderTags(string $lyrics, ?string $vocalGender, string $originalLyrics): string
    {
        if (! $vocalGender || $vocalGender === 'random') {
            return $lyrics;
        }

        if ($vocalGender === 'm') {
            if (! preg_match('/\[Male Vocal\]/i', $lyrics)) {
                $lyrics = "[Male Vocal]\n\n".ltrim($lyrics);
            }

            return $lyrics;
        }
        if ($vocalGender === 'f') {
            if (! preg_match('/\[Female Vocal\]/i', $lyrics)) {
                $lyrics = "[Female Vocal]\n\n".ltrim($lyrics);
            }

            return $lyrics;
        }
        if ($vocalGender === 'duet') {
            if (! preg_match('/\[duet man and woman\]/i', $lyrics)) {
                $lyrics = "[duet man and woman]\n\n".ltrim($lyrics);
            }

            return $lyrics;
        }

        return $lyrics;
    }

    // ==========================================
    // prepare_lyrics_for_user — exact copy from bot
    // ==========================================

    public static function prepareLyricsForUser(string $lyrics): string
    {
        if (empty($lyrics)) {
            return $lyrics;
        }

        $result = $lyrics;

        // Remove service tags
        $removePatterns = [
            '/\[duet man and woman\]\s*/i',
            '/\[Duet\]\s*/i',
            '/\[Song begins\]\s*/i',
            '/\[Male Vocalist?\]\s*/i',
            '/\[Female Vocalist?\]\s*/i',
            '/\[Male Vocal\]\s*/i',
            '/\[Female Vocal\]\s*/i',
            '/\[Man\]\s*/i',
            '/\[Woman\]\s*/i',
            '/\[Both\]\s*/i',
            '/\[Musical transition\]\s*/i',
            '/\[Musical buildup\]\s*/i',
            '/\[Musical crescendo\]\s*/i',
            '/\[Pause\]\s*/i',
            '/\[Fade[^\]]*\]\s*/i',
            '/\[END\]\s*/i',
            '/\[spoken\]\s*/i',
            '/\[sung\]\s*/i',
        ];

        foreach ($removePatterns as $pattern) {
            $result = preg_replace($pattern, '', $result);
        }

        // Translate tags EN -> RU for display
        $tagMap = [
            'Intro' => 'Интро', 'Verse' => 'Куплет', 'Verse 1' => 'Куплет 1',
            'Verse 2' => 'Куплет 2', 'Verse 3' => 'Куплет 3',
            'Pre-Chorus' => 'Предприпев', 'Chorus' => 'Припев',
            'Post-Chorus' => 'Постприпев', 'Bridge' => 'Бридж',
            'Outro' => 'Аутро', 'Hook' => 'Хук', 'Break' => 'Брейк',
            'Drop' => 'Дроп', 'Buildup' => 'Нарастание',
            'Instrumental' => 'Инструментал', 'Instrumental Break' => 'Инструментальная пауза',
            'Interlude' => 'Интерлюдия', 'Solo' => 'Соло',
            'Guitar Solo' => 'Гитарное соло', 'Fade Out' => 'Затухание',
            'Whisper' => 'Шёпот', 'Spoken Word' => 'Речитатив',
            'Rap' => 'Рэп', 'Harmonies' => 'Гармонии', 'Falsetto' => 'Фальцет',
            'Belting' => 'Белтинг', 'Growl' => 'Гроул', 'Scream' => 'Скрим',
            'Uplifting' => 'Воодушевляющий', 'Melancholic' => 'Меланхоличный',
            'Dark' => 'Мрачный', 'Joyful' => 'Радостный', 'Nostalgic' => 'Ностальгический',
            'Romantic' => 'Романтичный', 'Intense' => 'Интенсивный',
            'Dreamy' => 'Мечтательный', 'Peaceful' => 'Спокойный',
            'Euphoric' => 'Эйфорический', 'Mysterious' => 'Загадочный',
            'Aggressive' => 'Агрессивный', 'Epic' => 'Эпический',
            'Intimate' => 'Интимный', 'Bittersweet' => 'Горько-сладкий',
            'Triumphant' => 'Триумфальный', 'Warm' => 'Тёплый',
            'High Energy' => 'Высокая энергия', 'Low Energy' => 'Низкая энергия',
            'Chill' => 'Расслабленный', 'Driving' => 'Драйвовый',
            'Explosive' => 'Взрывной', 'Building' => 'Нарастающий',
            'Relaxed' => 'Расслабленный', 'Steady' => 'Устойчивый',
            'Powerful' => 'Мощно', 'Soft' => 'Мягко', 'Vulnerable' => 'Уязвимо',
            'Sultry' => 'Чувственно', 'Defiant' => 'Дерзко', 'Playful' => 'Игривый',
        ];

        $result = preg_replace_callback('/\[([^\]]+)\]/', function ($match) use ($tagMap) {
            $parts = array_map('trim', explode('|', $match[1]));
            $translated = array_map(function ($part) use ($tagMap) {
                return $tagMap[$part] ?? $part;
            }, $parts);

            return '['.implode(' | ', $translated).']';
        }, $result);

        $result = preg_replace('/\n{3,}/', "\n\n", $result);

        return trim($result);
    }

    // ==========================================
    // prepare_lyrics_for_suno — exact copy from bot
    // ==========================================

    public static function prepareLyricsForSuno(string $lyrics, ?string $vocalGender = null): string
    {
        if (empty($lyrics)) {
            return $lyrics;
        }

        $result = $lyrics;

        // Russian tags -> English
        $ruToEn = [
            'Куплет' => 'Verse', 'Куплет 1' => 'Verse 1', 'Куплет 2' => 'Verse 2',
            'Куплет 3' => 'Verse 3', 'Припев' => 'Chorus', 'Предприпев' => 'Pre-Chorus',
            'Бридж' => 'Bridge', 'Интро' => 'Intro', 'Аутро' => 'Outro',
            'Концовка' => 'Outro', 'Вступление' => 'Intro',
            'Проигрыш' => 'Instrumental Break', 'Соло' => 'Solo',
            'Затухание' => 'Fade Out', 'Нарастание' => 'Buildup',
            'Дроп' => 'Drop', 'Хук' => 'Hook',
            'Грустно' => 'Melancholic', 'Весело' => 'Joyful',
            'Энергично' => 'High Energy', 'Мощно' => 'Powerful',
            'Нежно' => 'Soft', 'Тихо' => 'Soft', 'Громко' => 'Powerful',
            'Шёпотом' => 'Whisper', 'Шепотом' => 'Whisper',
            'Агрессивно' => 'Aggressive', 'Спокойно' => 'Peaceful',
            'Мечтательно' => 'Dreamy', 'Романтично' => 'Romantic',
            'Тепло' => 'Warm', 'Мрачно' => 'Dark', 'Эпично' => 'Epic',
            'Радостно' => 'Joyful', 'Тоскливо' => 'Melancholic',
            'Задумчиво' => 'Dreamy', 'Чувственно' => 'Sultry', 'Дерзко' => 'Defiant',
            'Рэп' => 'Rap', 'Крик' => 'Scream', 'Скрим' => 'Scream',
            'Гроул' => 'Growl', 'Фальцет' => 'Falsetto', 'Хор' => 'Choir',
            'Гармонии' => 'Harmonies', 'Речитатив' => 'Spoken Word',
            'Мужской вокал' => 'Male Vocal', 'Женский вокал' => 'Female Vocal',
            'Дуэт' => 'Duet', 'Мужчина' => 'Man', 'Женщина' => 'Woman', 'Вместе' => 'Both',
        ];

        $result = preg_replace_callback('/\[([^\]]+)\]/', function ($match) use ($ruToEn) {
            $parts = array_map('trim', explode('|', $match[1]));
            $enParts = array_map(function ($part) use ($ruToEn) {
                return $ruToEn[$part] ?? $part;
            }, $parts);

            return '['.implode(' | ', $enParts).']';
        }, $result);

        // Add voice/gender tags
        if ($vocalGender === 'f') {
            $result = "[Female Vocal]\n\n".$result;
        } elseif ($vocalGender === 'm') {
            $result = "[Male Vocal]\n\n".$result;
        } elseif ($vocalGender === 'duet') {
            if (stripos($result, '[duet man and woman]') === false) {
                $result = "[duet man and woman]\n\n".$result;
            }
        }

        // Suno limit: 3000 chars
        $result = mb_substr($result, 0, 3000);

        return trim($result);
    }

    // ==========================================
    // GENRE RESOLUTION — exact copy from bot
    // ==========================================

    private function resolveGenreInstruction(string $genre, string $langCode): string
    {
        $g = mb_strtolower($genre);
        $key = null;

        if ($this->containsAny($g, ['метал', 'metal', 'heavy'])) {
            $key = 'metal';
        } elseif ($this->containsAny($g, ['блюз', 'blues'])) {
            $key = 'blues';
        } elseif ($this->containsAny($g, ['детск', 'kids', 'child'])) {
            $key = 'kids';
        } elseif ($this->containsAny($g, ['инди', 'indie'])) {
            $key = 'indie';
        } elseif ($this->containsAny($g, ['диско', 'disco'])) {
            $key = 'disco';
        } elseif ($this->containsAny($g, ['фолк', 'folk'])) {
            $key = 'folk';
        } elseif ($this->containsAny($g, ['латино', 'latin', 'reggaeton'])) {
            $key = 'latin';
        } elseif ($this->containsAny($g, ['регги', 'reggae'])) {
            $key = 'reggae';
        } elseif ($this->containsAny($g, ['кантри', 'country'])) {
            $key = 'country';
        } elseif ($this->containsAny($g, ['r&b', 'rnb', 'соул', 'soul'])) {
            $key = 'rnb';
        } elseif ($this->containsAny($g, ['электро', 'electro', 'edm', 'house', 'techno', 'dance'])) {
            $key = 'electronic';
        } elseif ($this->containsAny($g, ['рэп', 'хип', 'rap', 'hip', 'trap', 'drill', 'phonk'])) {
            $key = 'rap';
        } elseif ($this->containsAny($g, ['рок', 'rock', 'punk', 'grunge'])) {
            $key = 'rock';
        } elseif ($this->containsAny($g, ['шансон', 'chanson'])) {
            $key = 'shanson';
        } elseif ($this->containsAny($g, ['джаз', 'jazz'])) {
            $key = 'jazz';
        } elseif ($this->containsAny($g, ['поп', 'pop'])) {
            $key = 'pop';
        }

        $baseInstruction = $this->genreRules[$key ?? 'default'] ?? $this->genreRules['default'];
        $resolvedKey = $key ?? 'default';

        $moodTags = $this->getMoodTagsForGenre($resolvedKey);
        $instrTags = $this->getInstrumentTagsForGenre($resolvedKey);

        $moodStr = implode(', ', array_map(fn ($t) => "[{$t}]", $moodTags));
        $instrStr = implode(', ', array_map(fn ($t) => "[{$t}]", $instrTags));

        return "{$baseInstruction}\nRECOMMENDED MOOD/ENERGY TAGS for this genre: {$moodStr}\nRECOMMENDED INSTRUMENT context for this genre: {$instrStr}\nPick 1-2 mood tags per section from this list. Do NOT use all at once.";
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    // ==========================================
    // AI GENERATION — OpenAI (gpt-4.1 default) or Gemini
    // ==========================================

    private function aiGenerate(string $systemPrompt, string $userPrompt): string
    {
        if ($this->provider === 'openai') {
            return $this->generateWithOpenAI($systemPrompt, $userPrompt);
        }

        return $this->generateWithGemini($systemPrompt, $userPrompt);
    }

    private function generateWithOpenAI(string $systemPrompt, string $userPrompt): string
    {
        $model = config('services.openai.model', 'gpt-4.1');
        $apiKey = config('services.openai.api_key');

        $useNewParam = $this->shouldUseNewParam($model);

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.85,
        ];

        if ($useNewParam) {
            $payload['max_completion_tokens'] = 2000;
        } else {
            $payload['max_tokens'] = 2000;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', $payload);

        if (! $response->successful()) {
            throw new \Exception('OpenAI API error: '.$response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    private function generateWithGemini(string $systemPrompt, string $userPrompt): string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.0-flash');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(120)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [['parts' => [['text' => $systemPrompt."\n\n".$userPrompt]]]],
                'generationConfig' => ['temperature' => 0.85, 'maxOutputTokens' => 2000],
            ]
        );

        if (! $response->successful()) {
            throw new \Exception('Gemini API error: '.$response->body());
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    private function shouldUseNewParam(string $model): bool
    {
        foreach ($this->newParamModels as $newModel) {
            if (str_contains(strtolower($model), strtolower($newModel))) {
                return true;
            }
        }

        return false;
    }

    // ==========================================
    // PARSE RESPONSE — same as before
    // ==========================================

    private function parseResponse(string $rawText): array
    {
        $cleanText = trim($rawText);
        $cleanText = preg_replace('/^```(?:json)?\s*/i', '', $cleanText);
        $cleanText = preg_replace('/\s*```$/', '', $cleanText);

        // Try JSON
        $data = json_decode($cleanText, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $this->extractDataArray($data);
        }

        if (preg_match('/\{[\s\S]*\}/', $cleanText, $jsonMatch)) {
            $data = json_decode($jsonMatch[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $this->extractDataArray($data);
            }
        }

        // Text format
        $comment = '';
        $title = 'Моя песня';
        $lyrics = '';

        if (preg_match('/(?:КОММЕНТАРИЙ|COMMENT|Comment)\s*:\s*(.+?)(?=\n\s*(?:НАЗВАНИЕ|TITLE|Title|ТЕКСТ|LYRICS|Lyrics)\s*:)/siu', $cleanText, $m)) {
            $comment = trim($m[1]);
        }

        if (preg_match('/(?:НАЗВАНИЕ|TITLE|Title)\s*:\s*(.+?)(?=\n\s*(?:ТЕКСТ|LYRICS|Lyrics)\s*:)/siu', $cleanText, $m)) {
            $title = trim(explode("\n", trim($m[1]))[0]);
            $title = trim($title, " \t\n\r\0\x0B\"'");
            $title = trim($title, "\xC2\xAB\xC2\xBB"); // « »
            $title = preg_replace('/^[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}]+|[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}]+$/u', '', $title);
        } elseif (preg_match('/(?:НАЗВАНИЕ|TITLE|Title)\s*:\s*(.+)/iu', $cleanText, $m)) {
            $title = trim(explode("\n", trim($m[1]))[0]);
            $title = trim($title, " \t\n\r\0\x0B\"'");
            $title = trim($title, "\xC2\xAB\xC2\xBB");
            $title = preg_replace('/^[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}]+|[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}]+$/u', '', $title);
        }

        if (preg_match('/(?:ТЕКСТ|LYRICS|Lyrics)\s*:\s*\n?(.*)/siu', $cleanText, $m)) {
            $lyrics = trim($m[1]);
        }

        if (! $lyrics && preg_match('/\[(Куплет|Verse|Chorus|Припев|Bridge|Бридж|Outro|Intro|Male Voice|Female Voice|Duet)/iu', $cleanText)) {
            if (preg_match('/(\[(?:Куплет|Verse|Chorus|Припев|Bridge|Бридж|Outro|Intro|Hook|Pre-Chorus|Предприпев|Male Voice|Female Voice|Duet).*?\].*)/siu', $cleanText, $tagMatch)) {
                $lyrics = trim($tagMatch[1]);
            }
        }

        if ($lyrics) {
            $lyrics = preg_replace('/^(?:КОММЕНТАРИЙ|COMMENT)\s*:.*?\n/imu', '', $lyrics);
            $lyrics = preg_replace('/^(?:НАЗВАНИЕ|TITLE)\s*:.*?\n/imu', '', $lyrics);
            $lyrics = trim($lyrics);
        }

        if (! $lyrics) {
            $lyrics = $cleanText;
        }

        return ['success' => true, 'title' => $title, 'lyrics' => $lyrics, 'comment' => $comment];
    }

    private function extractDataArray(array $data): array
    {
        return [
            'success' => true,
            'title' => $data['title'] ?? $data['НАЗВАНИЕ'] ?? 'Без названия',
            'lyrics' => $data['lyrics'] ?? $data['ТЕКСТ'] ?? $data['body'] ?? '',
            'comment' => $data['comment'] ?? $data['КОММЕНТАРИЙ'] ?? '',
        ];
    }
}
