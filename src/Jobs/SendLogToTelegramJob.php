<?php

namespace Flash25ag\TelegramLogger\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SendLogToTelegramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The Telegram API URL.
     *
     * @var string
     */
    protected string $telegramApiUrl;

    
    /**
     * The chat ID to send the message to.
     *
     * @var int
     */
    protected int $chatId;

    /**
     * The bot token for authentication.
     *
     * @var string
     */
    protected string $botToken;

    /**
     * The message to send.
     *
     * @var string
     */
    protected string $message;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $retryAfter = 120;

    /**
     * Create a new job instance.
     *
     * @param string $telegramApiUrl
     * @param array<int, string> $chatIds
     * @param string $botToken
     * @param string $message
     */
    public function __construct(string $telegramApiUrl, int $chatId, string $botToken, string $message)
    {
        $this->telegramApiUrl = $telegramApiUrl;
        $this->chatId = $chatId;
        $this->botToken = $botToken;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
                $response = Http::post("{$this->telegramApiUrl}/bot{$this->botToken}/sendMessage", [
                    'chat_id' => trim($this->chatId),
                    'text' => $this->message,
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                ]);

                if ($response->failed()) {
                    $this->handleFailedResponse($response);
                    return;
                }
        } catch (Exception $e) {
            logger()->channel('single')->error('SendLogToTelegram Error: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Handle a failed response from the Telegram API.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return void
     * @throws \Exception
     */
    protected function handleFailedResponse(\Illuminate\Http\Client\Response $response): void
    {
        $statusCode = $response->status();
        $errorBody = $response->json();

        $message = null;

        if ($statusCode === 429 && isset($errorBody['parameters']['retry_after'])) {
            $retryAfter = (int) $errorBody['parameters']['retry_after'];
            $message = "Rate limit exceeded. Waiting for {$retryAfter} seconds...";
            logger()->channel('single')->error($message);
            $this->fail(new Exception($message));
            sleep($retryAfter);
        } else {
            $message = "Telegram API Error: " . json_encode($errorBody);
            logger()->channel('single')->error($message);
            $this->fail(new Exception($message));
        }
    }
}
