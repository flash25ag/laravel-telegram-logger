# Telegram Logger

Telegram Logger is a Laravel package for sending application logs to Telegram using Monolog.

## Installation

Add this package to your Laravel project using Composer:

```sh
composer require flash25ag/laravel-telegram-logger
```

## Configuration

Add the configuration to `config/logging.php`:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'telegram'],
    ],
    'telegram' => [
        'driver' => 'monolog',
        'handler' => \flash25ag\TelegramLogger\TelegramHandler::class,
        'formatter' => \flash25ag\TelegramLogger\Formatters\TelegramLogFormatter::class,
        'handler_with' => [
            'chatIds' => env('TELEGRAM_LOGGER_CHAT_IDS'),
            'botToken' => env('TELEGRAM_LOGGER_BOT_TOKEN'),
            'cacheTime' => env('TELEGRAM_LOGGER_CACHE_TIME', 60),
            'queueDelay' => env('TELEGRAM_LOGGER_QUEUE_DELAY', 5),
            'queueName' => env('TELEGRAM_LOGGER_QUEUE_NAME', 'telegram_logs'),
            'apiUrl' => env('TELEGRAM_LOGGER_API_URL', 'https://api.telegram.org'),
        ],
    ],
],
```

Add the following variables to your `.env` file:

```env
TELEGRAM_LOGGER_API_URL="https://api.telegram.org"
TELEGRAM_LOGGER_CHAT_IDS=123456789,987654321
TELEGRAM_LOGGER_BOT_TOKEN=your-telegram-bot-token
TELEGRAM_LOGGER_CACHE_TIME=60
TELEGRAM_LOGGER_QUEUE_DELAY=5
TELEGRAM_LOGGER_QUEUE_NAME=telegram_logs
```

## Usage

Use the `telegram` log channel in Laravel:

```php
use Illuminate\Support\Facades\Log;

Log::channel('telegram')->error('This is a log message to Telegram.');
```

## License

This package is released under the MIT license.