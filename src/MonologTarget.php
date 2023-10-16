<?php
/**
 * Monolog Target
 */
namespace Zatxm\YiiMonolog;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LogLevel;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\Target;

class MonologTarget extends Target
{
    private $extractTrace = false;

    private $_levels = [];
    private $_levelMap = [
        Logger::LEVEL_ERROR => LogLevel::ERROR,
        Logger::LEVEL_WARNING => LogLevel::WARNING,
        Logger::LEVEL_INFO => LogLevel::INFO,
        Logger::LEVEL_TRACE => LogLevel::DEBUG,
        Logger::LEVEL_PROFILE => LogLevel::DEBUG,
        Logger::LEVEL_PROFILE_BEGIN => LogLevel::DEBUG,
        Logger::LEVEL_PROFILE_END => LogLevel::DEBUG,

        // Psr Levels
        LogLevel::EMERGENCY => LogLevel::EMERGENCY,
        LogLevel::ALERT => LogLevel::ALERT,
        LogLevel::CRITICAL => LogLevel::CRITICAL,
        LogLevel::ERROR => LogLevel::ERROR,
        LogLevel::WARNING => LogLevel::WARNING,
        LogLevel::NOTICE => LogLevel::NOTICE,
        LogLevel::INFO => LogLevel::INFO,
        LogLevel::DEBUG => LogLevel::DEBUG,
    ];

    private $logger = null;

    public function __construct($config = [])
    {
        $this->_levels = $this->_levelMap;
        parent::__construct($config);
    }

    /**
     * Sets MonologLogger
     * @param MonologLogger|callable $logger
     */
    public function setLogger($logger)
    {
        if ($logger instanceof MonologLogger) {
            $this->logger = $logger;
        } elseif (is_callable($logger)) {
            $this->logger = call_user_func($logger);
        } else {
            $this->logger = $this->getLogger();
        }
        Yii::$app->components = array_merge(Yii::$app->components, ['monolog'=>$this->logger]);
    }

    /**
     * @return MonologLogger
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = LoggerBuilder::build($this->extractTrace);
        }
        return $this->logger;
    }

    public function setExtractTrace($extractTrace)
    {
        $this->extractTrace = $extractTrace;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        foreach ($this->messages as $message) {
            $level = $message[1];

            $context = [];
            if (isset($message[4])) {
                $context['trace'] = $message[4];
            }

            if (isset($message[5])) {
                $context['memory'] = $message[5];
            }

            if (isset($message[2])) {
                $context['category'] = $message[2];
            }

            if (isset($message[3])) {
                $context['timestamp'] = $message[3];
            }

            $text = $message[0];
            if (!is_string($text)) {
                if ($text instanceof \Throwable || $text instanceof \Exception) {
                    $context['exception'] = $text;
                    $context['trace'] = explode(PHP_EOL, $text->getTraceAsString());
                    $text = $text->getMessage();
                } else {
                    $text = VarDumper::export($text);
                }
            }

            $this->getLogger()->log($this->_levelMap[$level], $text, $context);
        }
    }

    public static function filterMessages(
        $messages,
        $levels = [],
        $categories = [],
        $except = []
    ) {
        $filterByLevel = function ($message) use ($levels) {
            return isset($levels[$message[1]]);
        };
        return array_filter(parent::filterMessages($messages, 0, $categories, $except), $filterByLevel);
    }

    public function getLevels()
    {
        return $this->_levels;
    }

    /**
     * Sets the message levels that this target is interested in.
     *
     * The parameter can be an array.
     * Valid level names include: 'error',
     * 'warning', 'info', 'trace' and 'profile'; valid level values include:
     * [[Logger::LEVEL_ERROR]], [[Logger::LEVEL_WARNING]], [[Logger::LEVEL_INFO]],
     * [[Logger::LEVEL_TRACE]], [[Logger::LEVEL_PROFILE]] and Psr Log levels:
     * [[LogLevel::EMERGENCY]], [[LogLevel::ALERT]], [[LogLevel::CRITICAL]],
     * [[LogLevel::ERROR]], [[LogLevel::WARNING]], [[LogLevel::NOTICE]],
     * [[LogLevel::INFO]] and [[LogLevel::DEBUG]].
     *
     * For example,
     *
     * ```php
     * ['error', 'warning', LogLevel::CRITICAL, LogLevel::EMERGENCY]
     * ```
     *
     * @param array $levels message levels that this target is interested in.
     * @throws InvalidConfigException if $levels value is not correct.
     */
    public function setLevels($levels)
    {
        static $levelMap = [
            'error' => Logger::LEVEL_ERROR,
            'warning' => Logger::LEVEL_WARNING,
            'info' => Logger::LEVEL_INFO,
            'trace' => Logger::LEVEL_TRACE,
            'profile' => Logger::LEVEL_PROFILE,
        ];

        if (is_array($levels)) {
            $intrestingLevels = [];
            foreach ($levels as $level) {
                if (!isset($this->_levels[$level]) && !isset($levelMap[$level])) {
                    throw new InvalidConfigException("Unrecognized level: $level");
                }
                if (isset($levelMap[$level])) {
                    $intrestingLevels[$levelMap[$level]] = $this->_levels[$levelMap[$level]];
                }
                if (isset($this->_levels[$level])) {
                    $intrestingLevels[$level] = $this->_levels[$level];
                }
            }
            $this->_levels = $intrestingLevels;
        } else {
            throw new InvalidConfigException("Incorrect {$levels} value");
        }
    }
}
