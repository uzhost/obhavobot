<?php

// Telegram API
$token = 'Your_Bot_Token';
$api = 'https://api.telegram.org/bot' . $token;

// OpenWeatherMap API
$OpenApiKey = 'Your_API_Key';

$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $cityName = $message['text'];

    // Get the weather information
        $weatherApiUrl = 'http://api.openweathermap.org/data/2.5/weather?q=' . urlencode($cityName) . '&appid=' . $OpenApiKey . '&units=metric';

    $weatherData = json_decode(file_get_contents($weatherApiUrl), true);

    if ($weatherData && isset($weatherData['weather'])) {
        $weatherDescription = ucfirst($weatherData['weather'][0]['description']);
        $temperature = round($weatherData['main']['temp']);
        $humidity = $weatherData['main']['humidity'];
        $windSpeed = round($weatherData['wind']['speed']); // m/s
        $sunriseTime = date('H:i', $weatherData['sys']['sunrise']);
        $sunsetTime = date('H:i', $weatherData['sys']['sunset']);

        $response = "📍Location (Hudud): <b>" . $cityName . "</b> 
        \n🌦Weather (Ob-havo): " . $weatherDescription . ", 
        \n🌡Temperature (Harorat) : <b>" . $temperature . "°C,</b> 
        \n💧Humidity (Namlik) : <b>" . $humidity . "%,</b> 
        \n🌬Wind speed (Shamol) : <b>" . $windSpeed . " m/s,</b> 
        \n☀️Sunrise (Quyosh chiqishi) : <b>" . $sunriseTime . ",</b> 
        \n☀️Sunset (Quyosh chiqishi) : <b>" . $sunsetTime . "</b>
        \n⛅️ @Obhavoinforobot
        ";
    } else {
        $response = "Sorry, I couldn't find the weather information for " . $cityName . ". Please check the spelling and try again. 
        \n⛅️ @Obhavoinforobot";
    }

    // Send the response message to the user
    $parameters = array(
        'chat_id' => $chatId,
        'text' => $response,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    );
    file_get_contents($api . '/sendMessage?' . http_build_query($parameters));
}
