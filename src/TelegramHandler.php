<?php

namespace Flash25ag\TelegramLogger;

use Exception;
use Monolog\Logger;
use Monolog\LogRecord;
use Flash25ag\TelegramLogger\Jobs\SendLogToTelegramJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Monolog\Handler\AbstractProcessingHandler;

class TelegramHandler extends AbstractProcessingHandler
{
    /**
     * Maximum size of a Telegram message.
     */
    protected const TELEGRAM_MESSAGE_SIZE = 4096;

    /**
     * Cache key prefix for Telegram messages.
     */
    protected const TELEGRAM_MESSAGE_CACHE_KEY = 'telegram_message_cache_';

    /**
     * Array of chat IDs to which the log messages will be sent.
     *
     * @var array<int>
     */
    protected array $chatIds;

    /**
     * Bot token for Telegram API.
     *
     * @var string
     */
    protected string $botToken;

    /**
     * Cache time for messages in seconds.
     *
     * @var int
     */
    protected int $cacheTime;

    /**
     * Delay for queuing messages in seconds.
     *
     * @var int
     */
    protected int $queueDelay;

    /**
     * Name of the queue to use.
     *
     * @var string
     */
    protected string $queueName;

    /**
     * API URL for Telegram.
     *
     * @var string
     */
    protected string $apiUrl;

    /**
     * Constructor for TelegramHandler.
     *
     * @param string $chatIds chat IDs to which the log messages will be sent.
     * @param string $botToken Bot token for Telegram API.
     * @param int $cacheTime Cache time for messages in seconds.
     * @param int $queueDelay Delay for queuing messages in seconds.
     * @param string $queueName Name of the queue to use.
     * @param string $apiUrl API URL for Telegram.
     * @param int $level The minimum logging level at which this handler will be triggered.
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not.
     */
    public function __construct(
        string $chatIds,
        string $botToken,
        int $cacheTime,
        int $queueDelay,
        string $queueName,
        string $apiUrl,
        $level = Logger::DEBUG,
        bool $bubble = true
    ) {
        if (empty($chatId) || empty($botToken)) {
            logger()->channel('single')->error("Chat IDs and Bot Token must not be empty.");
            return;
        }

        $this->chatIds = explode(',', $chatIds);
        $this->botToken = $botToken;
        $this->cacheTime = max(0, $cacheTime);
        $this->queueDelay = max(0, $queueDelay);
        $this->queueName = $queueName;
        $this->apiUrl = $apiUrl;

        parent::__construct($level, $bubble);
    }

    /**
     * Writes the log record to Telegram.
     *
     * @param LogRecord $record The log record to write.
     */
    protected function write(LogRecord $record): void
    {
        try {
            $message = $this->truncateMessageToTelegramLimit($record['formatted']);
            $cacheKey = self::TELEGRAM_MESSAGE_CACHE_KEY . md5($message);

            if (Cache::has($cacheKey)) {
                return;
            }

            Cache::put($cacheKey, true, $this->cacheTime);

            foreach ($this->chatIds as $chatId) {
                Queue::laterOn(
                    $this->queueName,
                    now()->addSeconds($this->queueDelay),
                    new SendLogToTelegramJob(
                        $this->apiUrl,
                        $chatId,
                        $this->botToken,
                        $message
                    )
                );
            }
        } catch (Exception $e) {
            logger()->channel('single')->error('TelegramLogger Error: ' . $e->getMessage());
        }
    }

    /**
     * Truncates the message to fit within the Telegram message size limit.
     *
     * @param string $message The message to truncate.
     * @return string The truncated message.
     */
    protected function truncateMessageToTelegramLimit(string $message): string
    {
        return mb_strlen($message) > self::TELEGRAM_MESSAGE_SIZE
            ? mb_substr($message, 0, self::TELEGRAM_MESSAGE_SIZE, 'UTF-8')
            : $message;
    }
}
