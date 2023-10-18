<?php
namespace Zatxm\YiiMonolog;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Yii;

class LoggerBuilder
{
    public static function build($extractTrace = false)
    {
        $logger = new Logger('app_log');
        $file = Yii::getAlias('@runtime/logs/app.log');
        $output = "%datetime% %channel%.%level_name% %extra.reqId% %message% %context% %extra% %extra.trace%\n";
        $dateFormatter = 'Y-m-d\TH:i:s.u';
        $formatter = new LineFormatter($output, $dateFormatter, false, true);
        $handler = new StreamHandler($file, Logger::DEBUG, true, 0666);
        $handler->setFormatter($formatter)->setCustomClosure(function () use ($file) {
            $dir = Yii::getAlias('@runtime/logs/backup');
            $ztFile = Yii::getAlias('@runtime/logs/backup/app-'. date('Y-m-d', strtotime('-1 day')) . '.log');
            if (!is_file($ztFile) && is_file($file)) {
                if (!is_dir($dir)) {
                    $status = mkdir($dir, 0777, true);
                    if (false === $status) {
                        throw new \UnexpectedValueException(sprintf('There is no existing directory at "%s" and its not buildable', $dir));
                    }
                }
                try {
                    rename($file, $ztFile);
                } catch (\Throwable $e) {
                }
            }
        });
        $logger->pushHandler($handler);
        $uid = static::generateUid();
        $logger->pushProcessor(function ($record) use ($uid, $extractTrace) {
            $record->extra['reqId'] = $uid ?: static::generateUid();
            if ($extractTrace) {
                $traces = [];
                $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                array_pop($ts);
                foreach ($ts as $trace) {
                    if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII2_PATH) !== 0) {
                        $str = "# {$trace['file']}({$trace['line']}): ";
                        if (isset($trace['class'])) {
                            $str .= $trace['class'] . '->';
                        }
                        $str .= ($trace['function'] ?? 'unknown'). '()';
                        $traces[] = $str;
                    }
                }
                $record->extra['trace'] = $traces ? implode("\n", $traces) : '';
            }
            return $record;
        });
        return $logger;
    }

    /**
     * @param  positive-int $length
     * @return non-empty-string
     */
    private static function generateUid($length = 32)
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }
}
