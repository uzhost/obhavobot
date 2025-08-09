<?php
/**
 * Telegram Weather Bot (UZ/RU/EN)
 * Single-file webhook endpoint
 * PHP 7.4+ required
 */

declare(strict_types=1);

// ================== CONFIG ==================
$TELEGRAM_BOT_TOKEN = getenv('TELEGRAM_BOT_TOKEN') ?: 'YOUR_TELEGRAM_BOT_TOKEN_HERE';
$OPENWEATHER_API_KEY = getenv('OPENWEATHER_API_KEY') ?: 'YOUR_OPENWEATHER_API_KEY_HERE';
$DEFAULT_LANG = 'en';
$TELEGRAM_API = 'https://api.telegram.org/bot' . $TELEGRAM_BOT_TOKEN;

// Save language prefs here (must be writable by PHP)
define('LANG_STORE', __DIR__ . '/lang_prefs.json');

// ================== UTILITIES ==================
function tg_api(string $method, array $params = []): array {
    global $TELEGRAM_API;
    $ch = curl_init($TELEGRAM_API . '/' . $method);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $params,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'description' => $err];
    }
    curl_close($ch);
    $json = json_decode($res, true);
    return is_array($json) ? $json : ['ok' => false, 'description' => 'Invalid JSON'];
}

function tg_sendMessage($chatId, string $text, array $opts = []): void {
    tg_api('sendMessage', array_merge([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ], $opts));
}

function tg_action($chatId, string $action = 'typing'): void {
    tg_api('sendChatAction', ['chat_id' => $chatId, 'action' => $action]);
}

function esc(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kmh(float $ms): float { return $ms * 3.6; }

function windDirection(float $deg): string {
    $dirs = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
    $idx = (int)round(($deg % 360) / 22.5) % 16;
    return $dirs[$idx];
}

function weatherIcon(int $id): string {
    if ($id >= 200 && $id < 300) return "⛈";
    if ($id >= 300 && $id < 400) return "🌦";
    if ($id >= 500 && $id < 600) return "🌧";
    if ($id >= 600 && $id < 700) return "❄️";
    if ($id >= 700 && $id < 800) return "🌫";
    if ($id == 800) return "☀️";
    if ($id > 800) return "⛅️";
    return "🌡";
}

// ================== OPENWEATHER ==================
function ow_get_weather(string $city): array {
    global $OPENWEATHER_API_KEY;
    $url = 'https://api.openweathermap.org/data/2.5/weather?' . http_build_query([
        'q' => $city,
        'appid' => $OPENWEATHER_API_KEY,
        'units' => 'metric',
        'lang' => 'en', // keep description in EN; labels are localized below
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'error' => $err];
    }
    curl_close($ch);
    $data = json_decode($res, true);
    if (!is_array($data)) return ['ok' => false, 'error' => 'Invalid JSON from OpenWeather'];
    if (isset($data['cod']) && (int)$data['cod'] !== 200) {
        return ['ok' => false, 'error' => $data['message'] ?? 'Unknown error'];
    }
    return ['ok' => true, 'data' => $data];
}

// ================== I18N STRINGS ==================
$STR = [
    'en' => [
        'prompt_city'   => "Send me a city name (e.g., <b>Tashkent</b>).",
        'location'      => "Location",
        'weather'       => "Weather",
        'temperature'   => "Temperature",
        'feels_like'    => "Feels like",
        'humidity'      => "Humidity",
        'wind'          => "Wind",
        'pressure'      => "Pressure",
        'sunrise'       => "Sunrise",
        'sunset'        => "Sunset",
        'wind_unit'     => "m/s",
        'wind_kmh'      => "km/h",
        'pressure_unit' => "hPa",
        'not_found'     => "Sorry, I couldn't find weather for <b>%s</b>. Check spelling and try again.",
        'choose_lang'   => "Choose your language:",
        'footer'        => "⛅️ @Obhavoinforobot",
        'samples'       => ["Tashkent","Samarqand","Bukhara","Namangan"],
        'lang_set'      => "Language set: English",
        'menu_lang'     => "🌐 Language / Til / Язык",
    ],
    'uz' => [
        'prompt_city'   => "Shahar nomini yuboring (masalan, <b>Toshkent</b>).",
        'location'      => "Hudud",
        'weather'       => "Ob-havo",
        'temperature'   => "Harorat",
        'feels_like'    => "Seziladigan",
        'humidity'      => "Namlik",
        'wind'          => "Shamol",
        'pressure'      => "Bosim",
        'sunrise'       => "Quyosh chiqishi",
        'sunset'        => "Quyosh botishi",
        'wind_unit'     => "m/s",
        'wind_kmh'      => "km/soat",
        'pressure_unit' => "hPa",
        'not_found'     => "Kechirasiz, <b>%s</b> uchun ob-havo topilmadi. Imloni tekshirib, qayta urinib ko‘ring.",
        'choose_lang'   => "Tilni tanlang:",
        'footer'        => "⛅️ @Obhavoinforobot",
        'samples'       => ["Toshkent","Samarqand","Buxoro","Namangan"],
        'lang_set'      => "Til tanlandi: Oʻzbekcha",
        'menu_lang'     => "🌐 Language / Til / Язык",
    ],
    'ru' => [
        'prompt_city'   => "Отправьте название города (например, <b>Ташкент</b>).",
        'location'      => "Локация",
        'weather'       => "Погода",
        'temperature'   => "Температура",
        'feels_like'    => "Ощущается как",
        'humidity'      => "Влажность",
        'wind'          => "Ветер",
        'pressure'      => "Давление",
        'sunrise'       => "Восход",
        'sunset'        => "Закат",
        'wind_unit'     => "м/с",
        'wind_kmh'      => "км/ч",
        'pressure_unit' => "гПа",
        'not_found'     => "Извините, не удалось найти погоду для <b>%s</b>. Проверьте написание и попробуйте снова.",
        'choose_lang'   => "Выберите язык:",
        'footer'        => "⛅️ @Obhavoinforobot",
        'samples'       => ["Ташкент","Самарканд","Бухара","Наманган"],
        'lang_set'      => "Выбран язык: Русский",
        'menu_lang'     => "🌐 Language / Til / Язык",
    ],
];

// ================== LANGUAGE PERSISTENCE ==================
function load_lang_store(): array {
    if (!file_exists(LANG_STORE)) return [];
    $j = @file_get_contents(LANG_STORE);
    $a = json_decode($j, true);
    return is_array($a) ? $a : [];
}
function save_lang_store(array $store): void {
    @file_put_contents(LANG_STORE, json_encode($store, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

/** Normalize user text: lowercase, unify apostrophes, strip emojis/spaces */
function norm(string $s): string {
    $s = str_replace(
        ["ʻ","’","`","´","‘","ʿ","ʼ","Oʻ","O‘","oʻ","o‘","gʻ","g‘","Gʻ","G‘"],
        ["'","'","'","'","'","'","'","O'","O'","o'","o'","g'","g'","G'","G'"],
        $s
    );
    $s = preg_replace('/[\x{1F1E6}-\x{1F1FF}\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]+/u', '', $s);
    $s = trim($s);
    return mb_strtolower($s, 'UTF-8');
}

/** Try to pick language from free-form text (button text or user message) */
function pick_lang_from_text(string $text): ?string {
    $t = norm($text);
    if (preg_match('/^(en|eng|english|ingliz)\b/u', $t)) return 'en';
    if (preg_match('/^(ru|rus|russkiy|русский|российский)\b/u', $t)) return 'ru';
    if (preg_match('/^(uz|o\'zbek|o\'zbekcha|uzbek|uzbekcha|o‘zbek|oʻzbek)\b/u', $t)) return 'uz';
    return null;
}

function get_user_lang(array $msg, string $default, array $store): string {
    $chatId = $msg['chat']['id'];
    if (isset($store[$chatId])) return $store[$chatId];
    $lc = strtolower($msg['from']['language_code'] ?? $default);
    if (strpos($lc, 'uz') === 0) return 'uz';
    if (strpos($lc, 'ru') === 0) return 'ru';
    return 'en';
}

// ================== KEYBOARDS ==================
function lang_buttons(): array {
    return [
        ['text' => "🇺🇿 Oʻzbekcha"],
        ['text' => "🇷🇺 Русский"],
        ['text' => "🇬🇧 English"],
    ];
}
function sample_city_keyboard(array $labels, string $langMenuLabel): array {
    $rows = [];
    $row = [];
    foreach ($labels as $city) {
        $row[] = ['text' => $city];
        if (count($row) === 3) { $rows[] = $row; $row = []; }
    }
    if ($row) $rows[] = $row;
    $rows[] = [['text' => $langMenuLabel]];
    return $rows;
}
function main_keyboard(array $L): array {
    // two rows: languages row, then sample cities rows
    return array_merge([lang_buttons()], sample_city_keyboard($L['samples'], $L['menu_lang']));
}

// ================== MAIN ==================
$raw = file_get_contents('php://input');
$update = json_decode($raw, true);
if (!$update) { http_response_code(200); echo 'No update'; exit; }

if (!empty($update['message'])) {
    $msg    = $update['message'];
    $chatId = $msg['chat']['id'] ?? null;
    $text   = trim((string)($msg['text'] ?? ''));

    if (!$chatId) { http_response_code(200); echo 'No chat'; exit; }

    $store = load_lang_store();
    $lang = get_user_lang($msg, $DEFAULT_LANG, $store);
    $L = $STR[$lang] ?? $STR[$DEFAULT_LANG];

    $norm = norm($text);
    $asked_language_menu = $norm === norm($L['menu_lang'])
        || in_array($norm, ['language','til','язык','language / til / язык'], true)
        || strpos($text, '🌐') !== false;

    // /start or /help: greet + save auto-detected language
    if ($norm === '/start' || $norm === '/help') {
        $store[$chatId] = $lang;
        save_lang_store($store);
        tg_sendMessage($chatId, $L['prompt_city'], [
            'reply_markup' => json_encode(['keyboard' => main_keyboard($L), 'resize_keyboard' => true])
        ]);
        exit;
    }

    // Language picker explicitly
    if ($norm === '/lang' || $asked_language_menu) {
        tg_sendMessage($chatId, $L['choose_lang'], [
            'reply_markup' => json_encode(['keyboard' => main_keyboard($L), 'resize_keyboard' => true])
        ]);
        exit;
    }

    // If user pressed a language button or typed a language name
    if ($picked = pick_lang_from_text($text)) {
        $lang = $picked;
        $store[$chatId] = $lang;
        save_lang_store($store);
        $L = $STR[$lang] ?? $STR[$DEFAULT_LANG];
        tg_sendMessage($chatId, "✅ " . $L['lang_set'] . "\n\n" . $L['prompt_city'], [
            'reply_markup' => json_encode(['keyboard' => main_keyboard($L), 'resize_keyboard' => true])
        ]);
        exit;
    }

    // If the user sent nothing usable, prompt
    if ($text === '') {
        tg_sendMessage($chatId, $L['prompt_city']);
        exit;
    }

    // Fetch weather
    tg_action($chatId, 'typing');

    $cityQuery = $text; // free-form; OpenWeather handles many variants
    $wx = ow_get_weather($cityQuery);

    if (!$wx['ok']) {
        tg_sendMessage($chatId, sprintf($L['not_found'], esc($cityQuery)) . "\n" . $L['footer'], [
            'reply_markup' => json_encode(['keyboard' => main_keyboard($L), 'resize_keyboard' => true])
        ]);
        exit;
    }

    $d = $wx['data'];

    // Timezone handling: OpenWeather gives sunrise/sunset in UTC; add location tz offset (seconds)
    $tzShift = (int)($d['timezone'] ?? 0);
    $sunrise = date('H:i', (int)($d['sys']['sunrise'] ?? 0) + $tzShift);
    $sunset  = date('H:i', (int)($d['sys']['sunset'] ?? 0) + $tzShift);

    $desc   = ucfirst((string)($d['weather'][0]['description'] ?? ''));
    $wId    = (int)($d['weather'][0]['id'] ?? 0);
    $icon   = weatherIcon($wId);
    $temp   = round((float)($d['main']['temp'] ?? 0));
    $feels  = round((float)($d['main']['feels_like'] ?? 0));
    $hum    = (int)($d['main']['humidity'] ?? 0);
    $press  = (int)($d['main']['pressure'] ?? 0);
    $windMs = (float)($d['wind']['speed'] ?? 0);
    $windKm = round(kmh($windMs));
    $windDeg= isset($d['wind']['deg']) ? windDirection((float)$d['wind']['deg']) : '—';

    $cityShown = esc((string)($d['name'] ?? $cityQuery));
    $country   = esc((string)($d['sys']['country'] ?? ''));
    $titleLine = $country ? "$cityShown, $country" : $cityShown;

    $out =
        "📍 <b>{$L['location']}:</b> {$titleLine}\n" .
        "{$icon} <b>{$L['weather']}:</b> " . esc($desc) . "\n" .
        "🌡 <b>{$L['temperature']}:</b> <b>{$temp}°C</b> • {$L['feels_like']}: {$feels}°C\n" .
        "💧 <b>{$L['humidity']}:</b> {$hum}%\n" .
        "🌬 <b>{$L['wind']}:</b> " . round($windMs) . " {$L['wind_unit']} ({$windKm} {$L['wind_kmh']}), {$windDeg}\n" .
        "🧭 <b>{$L['pressure']}:</b> {$press} {$L['pressure_unit']}\n" .
        "☀️ <b>{$L['sunrise']}:</b> {$sunrise}\n" .
        "🌇 <b>{$L['sunset']}:</b> {$sunset}\n\n" .
        $L['footer'];

    tg_sendMessage($chatId, $out, [
        'reply_markup' => json_encode(['keyboard' => main_keyboard($L), 'resize_keyboard' => true])
    ]);
    exit;
}

// Graceful NO-OP for other update types
http_response_code(200);
echo 'OK';
