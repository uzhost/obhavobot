<?php
/**
 * Telegram Weather Bot (UZ/RU/EN)
 * Webhook endpoint
 * Requirements: PHP 7.4+, HTTPS hosting
 */

declare(strict_types=1);

// ========= CONFIG =========
$TELEGRAM_BOT_TOKEN = 'YOUR_TELEGRAM_BOT_TOKEN_HERE';
$OPENWEATHER_API_KEY = 'YOUR_OPENWEATHER_API_KEY_HERE';
$TELEGRAM_API = 'https://api.telegram.org/bot' . $TELEGRAM_BOT_TOKEN;

// Optional: default fallback language
$DEFAULT_LANG = 'en';

// ========= HELPERS =========
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

function tg_sendChatAction($chatId, string $action = 'typing'): void {
    tg_api('sendChatAction', ['chat_id' => $chatId, 'action' => $action]);
}

function esc(string $text): string {
    // Escape for HTML parse_mode
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function windDirection(float $deg): string {
    $dirs = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
    $idx = (int)round(($deg % 360) / 22.5) % 16;
    return $dirs[$idx];
}

function weatherIcon(int $id): string {
    // OpenWeather condition code → emoji
    if ($id >= 200 && $id < 300) return "⛈";
    if ($id >= 300 && $id < 400) return "🌦";
    if ($id >= 500 && $id < 600) return "🌧";
    if ($id >= 600 && $id < 700) return "❄️";
    if ($id >= 700 && $id < 800) return "🌫";
    if ($id == 800) return "☀️";
    if ($id > 800) return "⛅️";
    return "🌡";
}

function kmh(float $ms): float {
    return $ms * 3.6;
}

function ow_get_weather(string $city): array {
    global $OPENWEATHER_API_KEY;
    $url = 'https://api.openweathermap.org/data/2.5/weather?' . http_build_query([
        'q' => $city,
        'appid' => $OPENWEATHER_API_KEY,
        'units' => 'metric',
        'lang' => 'en', // descriptions in English; we’ll localize labels
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
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Invalid JSON from OpenWeather'];
    }
    if (isset($data['cod']) && (int)$data['cod'] !== 200) {
        $msg = $data['message'] ?? 'Unknown error';
        return ['ok' => false, 'error' => $msg];
    }
    return ['ok' => true, 'data' => $data];
}

// ========= I18N =========
$STR = [
    'en' => [
        'prompt_city' => "Send me a city name (e.g., <b>Tashkent</b>).",
        'location'    => "Location",
        'weather'     => "Weather",
        'temperature' => "Temperature",
        'feels_like'  => "Feels like",
        'humidity'    => "Humidity",
        'wind'        => "Wind",
        'pressure'    => "Pressure",
        'sunrise'     => "Sunrise",
        'sunset'      => "Sunset",
        'wind_unit'   => "m/s",
        'wind_kmh'    => "km/h",
        'pressure_unit' => "hPa",
        'not_found'   => "Sorry, I couldn't find weather for <b>%s</b>. Check spelling and try again.",
        'choose_lang' => "Choose your language:",
        'footer'      => "⛅️ @Obhavoinforobot",
        'sample_cities' => ["Tashkent","Samarqand","Bukhara","Namangan"]
    ],
    'uz' => [
        'prompt_city' => "Shahar nomini yuboring (masalan, <b>Toshkent</b>).",
        'location'    => "Hudud",
        'weather'     => "Ob-havo",
        'temperature' => "Harorat",
        'feels_like'  => "Seziladigan",
        'humidity'    => "Namlik",
        'wind'        => "Shamol",
        'pressure'    => "Bosim",
        'sunrise'     => "Quyosh chiqishi",
        'sunset'      => "Quyosh botishi",
        'wind_unit'   => "m/s",
        'wind_kmh'    => "km/soat",
        'pressure_unit' => "gPa", // hPa ga o‘xshash; istasangiz "hPa"ni qoldiring
        'not_found'   => "Kechirasiz, <b>%s</b> uchun ob-havo topilmadi. Imloni tekshirib, qayta urinib ko‘ring.",
        'choose_lang' => "Tilni tanlang:",
        'footer'      => "⛅️ @Obhavoinforobot",
        'sample_cities' => ["Toshkent","Samarqand","Buxoro","Namangan"]
    ],
    'ru' => [
        'prompt_city' => "Отправьте название города (например, <b>Ташкент</b>).",
        'location'    => "Локация",
        'weather'     => "Погода",
        'temperature' => "Температура",
        'feels_like'  => "Ощущается как",
        'humidity'    => "Влажность",
        'wind'        => "Ветер",
        'pressure'    => "Давление",
        'sunrise'     => "Восход",
        'sunset'      => "Закат",
        'wind_unit'   => "м/с",
        'wind_kmh'    => "км/ч",
        'pressure_unit' => "гПа",
        'not_found'   => "Извините, не удалось найти погоду для <b>%s</b>. Проверьте написание и попробуйте снова.",
        'choose_lang' => "Выберите язык:",
        'footer'      => "⛅️ @Obhavoinforobot",
        'sample_cities' => ["Ташкент","Самарканд","Бухара","Наманган"]
    ],
];

function detect_lang(array $message, string $default = 'en'): string {
    $lc = strtolower($message['from']['language_code'] ?? $default);
    if (strpos($lc, 'uz') === 0) return 'uz';
    if (strpos($lc, 'ru') === 0) return 'ru';
    return 'en';
}

function lang_buttons(): array {
    return [
        [['text' => "🇺🇿 Oʻzbekcha"],['text' => "🇷🇺 Русский"],['text' => "🇬🇧 English"]],
    ];
}

function sample_city_keyboard(array $labels): array {
    // one-row chips; adjust as you like
    $rows = [];
    $row = [];
    foreach ($labels as $i => $city) {
        $row[] = ['text' => $city];
        if (count($row) === 3) { $rows[] = $row; $row = []; }
    }
    if ($row) $rows[] = $row;
    $rows[] = [['text' => "🌐 Language / Til / Язык"]];
    return $rows;
}

// ========= MAIN =========
$update = json_decode(file_get_contents('php://input'), true);
if (!$update) { http_response_code(200); exit('No update'); }

if (!empty($update['message'])) {
    $msg     = $update['message'];
    $chatId  = $msg['chat']['id'];
    $text    = trim($msg['text'] ?? '');
    $lang    = detect_lang($msg, $DEFAULT_LANG);
    $L       = $GLOBALS['STR'][$lang] ?? $GLOBALS['STR'][$DEFAULT_LANG];

    // Handle language switching by buttons text
    $lower = mb_strtolower($text, 'UTF-8');
    if (in_array($lower, ['english','🇬🇧 english'], true)) { $lang = 'en'; $L = $GLOBALS['STR'][$lang]; }
    if (in_array($lower, ['oʻzbekcha','uzbek','🇺🇿 oʻzbekcha','o\'zbekcha'], true)) { $lang = 'uz'; $L = $GLOBALS['STR'][$lang]; }
    if (in_array($lower, ['русский','🇷🇺 русский'], true)) { $lang = 'ru'; $L = $GLOBALS['STR'][$lang]; }

    // Quick “start” / help
    if ($text === '/start' || $text === '/help' || $lower === 'language' || $lower === 'til' || $lower === 'язык' || $lower === '🌐 language / til / язык') {
        tg_api('sendMessage', [
            'chat_id' => $chatId,
            'text' => $L['prompt_city'],
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['keyboard' => array_merge(lang_buttons(), sample_city_keyboard($L['sample_cities'])), 'resize_keyboard' => true, 'one_time_keyboard' => false])
        ]);
        exit;
    }

    // If user presses “Language” button explicitly
    if ($lower === '🌐 language / til / язык') {
        tg_api('sendMessage', [
            'chat_id' => $chatId,
            'text' => $L['choose_lang'],
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['keyboard' => array_merge(lang_buttons(), sample_city_keyboard($L['sample_cities'])), 'resize_keyboard' => true])
        ]);
        exit;
    }

    if ($text === '') {
        tg_api('sendMessage', [
            'chat_id' => $chatId,
            'text' => $L['prompt_city'],
            'parse_mode' => 'HTML',
        ]);
        exit;
    }

    tg_sendChatAction($chatId, 'typing');

    $cityNameRaw = $text;
    $cityName = esc($cityNameRaw);

    $wx = ow_get_weather($cityNameRaw);
    if (!$wx['ok']) {
        tg_api('sendMessage', [
            'chat_id' => $chatId,
            'text' => sprintf($L['not_found'], esc($cityNameRaw)) . "\n" . $L['footer'],
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['keyboard' => sample_city_keyboard($L['sample_cities']), 'resize_keyboard' => true])
        ]);
        exit;
    }

    $d = $wx['data'];
    $tzShift = (int)($d['timezone'] ?? 0); // seconds offset from UTC
    $sunrise = date('H:i', ($d['sys']['sunrise'] ?? 0) + $tzShift);
    $sunset  = date('H:i', ($d['sys']['sunset'] ?? 0) + $tzShift);

    $desc     = ucfirst($d['weather'][0]['description'] ?? '');
    $wId      = (int)($d['weather'][0]['id'] ?? 0);
    $icon     = weatherIcon($wId);
    $temp     = round((float)$d['main']['temp']);
    $feels    = round((float)$d['main']['feels_like']);
    $hum      = (int)$d['main']['humidity'];
    $press    = (int)$d['main']['pressure'];
    $windMs   = (float)($d['wind']['speed'] ?? 0);
    $windKmH  = round(kmh($windMs));
    $windDeg  = isset($d['wind']['deg']) ? windDirection((float)$d['wind']['deg']) : '—';

    $cityShown = esc($d['name'] ?? $cityNameRaw);
    $country   = esc($d['sys']['country'] ?? '');
    $titleLine = $country ? "$cityShown, $country" : $cityShown;

    $textOut =
        "📍 <b>{$L['location']}:</b> {$titleLine}\n" .
        "{$icon} <b>{$L['weather']}:</b> " . esc($desc) . "\n" .
        "🌡 <b>{$L['temperature']}:</b> <b>{$temp}°C</b>  •  {$L['feels_like']}: {$feels}°C\n" .
        "💧 <b>{$L['humidity']}:</b> {$hum}%\n" .
        "🌬 <b>{$L['wind']}:</b> " . round($windMs) . " {$L['wind_unit']} ({$windKmH} {$L['wind_kmh']}), {$windDeg}\n" .
        "🧭 <b>{$L['pressure']}:</b> {$press} {$L['pressure_unit']}\n" .
        "☀️ <b>{$L['sunrise']}:</b> {$sunrise}\n" .
        "🌇 <b>{$L['sunset']}:</b> {$sunset}\n\n" .
        $L['footer'];

    tg_api('sendMessage', [
        'chat_id' => $chatId,
        'text' => $textOut,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
        'reply_markup' => json_encode(['keyboard' => sample_city_keyboard($L['sample_cities']), 'resize_keyboard' => true])
    ]);

    exit;
}

// Answer callback queries or other updates politely (optional)
http_response_code(200);
echo 'OK';
