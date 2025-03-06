<?php

namespace Flash25ag\TelegramLogger\Formatters;

use Exception;
use Monolog\LogRecord;
use Monolog\Formatter\FormatterInterface;

/**
 * Class TelegramLogFormatter
 *
 * This class formats log records for Telegram messaging.
 */
class TelegramLogFormatter implements FormatterInterface
{
    /**
     * Format a log record.
     *
     * @param LogRecord $record
     * @return string
     */
    public function format(LogRecord $record): string
    {
        try {
            if (!isset($record['datetime'], $record['level_name'], $record['message'])) {
                logger()->channel('single')->error("Invalid log record format.");
                return '';
            }

            $exceptionDetails = $this->getExceptionDetails($record);

            return sprintf(
                "🕒 <b>Time:</b> <code>%s</code>\n🏷 <b>App Name:</b> <code>%s</code>\n⚠️ <b>Level:</b> <code>%s</code>\n🌍 <b>Env:</b> <code>%s</code>\n💬 <b>Message:</b> <code>%s</code>\n📂 <b>File:</b> <code>%s</code>\n🔢 <b>Line:</b> <code>%s</code>\n🔗 <b>URL:</b> <code>%s</code>\n📌 <b>IP:</b> <code>%s</code>\n📩 <b>Req Method:</b> <code>%s</code>\n📝 <b>Req Input:</b>%s",
                htmlspecialchars($record['datetime']->format('Y-m-d H:i:s')),
                htmlspecialchars(config('app.name')),
                htmlspecialchars(strtoupper($record['level_name'])),
                htmlspecialchars(config('app.env')),
                htmlspecialchars($exceptionDetails['message']),
                htmlspecialchars($exceptionDetails['file']),
                htmlspecialchars($exceptionDetails['line']),
                htmlspecialchars(request()->fullUrl() ?? 'N/A'),
                htmlspecialchars(request()->ip() ?? 'N/A'),
                htmlspecialchars(request()->method() ?? 'N/A'),
                $this->formatRequestInput(request()->except('password', 'password_confirmation'))
            );
        } catch (Exception $e) {
            logger()->channel('single')->error('Error formatting log: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Format multiple log records.
     *
     * @param array $records
     * @return array
     */
    public function formatBatch(array $records): array
    {
        return array_map([$this, 'format'], $records);
    }

    /**
     * Format request input for logging.
     *
     * @param array $input
     * @return string
     */
    private function formatRequestInput(array $input): string
    {
        if (empty($input)) {
            return ' N/A';
        }

        return '<pre>' . str_replace(
            ["\n", " ", '<', '>'],
            ['', '', '&lt;', '&gt;'],
            json_encode($input, JSON_UNESCAPED_UNICODE)
        ) . '</pre>';
    }

    /**
     * Get exception details from the log record.
     *
     * @param LogRecord $record
     * @return array
     */
    private function getExceptionDetails(LogRecord $record): array
    {
        $exception = $record['context']['exception'] ?? null;

        return [
            'file' => $exception ? $exception->getFile() : 'N/A',
            'line' => $exception ? $exception->getLine() : 'N/A',
            'message' => $exception ? $exception->getMessage() : 'N/A',
        ];
    }
}
