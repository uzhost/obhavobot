# 🌤 Telegram Weather Bot (UZ / RU / EN)

A multilingual Telegram bot that fetches current weather information from [OpenWeatherMap](https://openweathermap.org/) and displays it in a clean, emoji-rich format with Uzbek, Russian, and English translations.

---

## ✨ Features

* **Multilingual UI**: Automatically detects Telegram user's language (`uz`, `ru`, `en`) and displays labels accordingly.
* **City Suggestions**: Quick-reply keyboard with sample cities.
* **Weather Icons**: Emoji icons based on condition codes.
* **Additional Data**:

  * Feels-like temperature
  * Humidity
  * Wind speed (m/s + km/h) and direction
  * Atmospheric pressure
  * Local sunrise and sunset times
* **Typing Indicator**: Sends “typing…” while fetching data.
* **Error Handling**: Gracefully handles unknown city names or API issues.
* **Language Switching**: Change language from the keyboard.

---

## 📦 Requirements

* PHP 7.4+ (or newer)
* cURL enabled
* HTTPS hosting (required by Telegram webhooks)
* Telegram Bot Token (from [@BotFather](https://t.me/BotFather))
* OpenWeatherMap API Key

---

## 🔧 Installation

1. **Clone or upload** this project to your server:

   ```bash
   git clone https://github.com/yourusername/telegram-weather-bot.git
   cd telegram-weather-bot
   ```

2. **Set your tokens**
   Open `index.php` and update:

   ```php
   $TELEGRAM_BOT_TOKEN = 'YOUR_TELEGRAM_BOT_TOKEN_HERE';
   $OPENWEATHER_API_KEY = 'YOUR_OPENWEATHER_API_KEY_HERE';
   ```

3. **Upload to your HTTPS server** (e.g., `https://yourdomain.com/weatherbot/index.php`).

4. **Set the Telegram webhook**:

   ```bash
   curl "https://api.telegram.org/bot<YOUR_TELEGRAM_BOT_TOKEN>/setWebhook?url=https://yourdomain.com/weatherbot/index.php"
   ```

5. **Start chatting** with your bot in Telegram.

---

## 📝 Usage

* Type a city name (e.g., `Tashkent`).
* Choose from suggested cities in the keyboard.
* Change language via 🌐 Language button.

Example commands:

```
/start
Tashkent
🌐 Language / Til / Язык
```

---

## 📂 File Structure

```
.
├── index.php       # Main webhook handler
├── README.md       # Documentation
```

---

## ⚙ Configuration

You can change:

* Default language: `$DEFAULT_LANG`
* Sample cities: `$STR['en']['sample_cities']`, `$STR['uz']['sample_cities']`, `$STR['ru']['sample_cities']`
* OpenWeather API parameters: in `ow_get_weather()`

---

## 🚀 Roadmap

* Save user’s default city in a database
* Add 3-day forecast mode
* Imperial units toggle (°F / mph)
* Inline button for “Refresh”

---

## 📜 License

MIT License — free to use, modify, and distribute.
