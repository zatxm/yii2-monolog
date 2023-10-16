Yii2 monolog
==========

Yii2 monolog是将monolog日志库应用于yii2框架

# Usage

composer require zatxm/yii2-monolog，用于php8+

composer require zatxm/yii2-monolog:"^2.0"，用于php7.2+

```php
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$logger = new Logger('my_logger');
$logger->pushHandler(new StreamHandler(__DIR__.'/my_app.log', Logger::Debug));

return [
    // ...
    'bootstrap' => ['log'],    
    // ...    
    'components' => [
        // ...        
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \Zatxm\YiiMonolog\MonologTarget::class,
                    'extractTrace' => false, //记录堆栈，可以不配置，默认false
                    'logger' => '', //要用原生的monolog一定保留此键，空字符系统会生成monolog logger
                    // 'logger' => $logger, //或者用这个自己定义的monolog logger
                    // 'logger' => function () {
                    //     $logger = new Logger('my_logger');
                    //     $logger->pushHandler(new StreamHandler(__DIR__.'/my_app.log', Logger::Debug));
                    //     return $logger;
                    // } // 或者用闭包
                    'levels' => ['error', 'warning']
                ]
            ],
        ]
    ],
];
```

Yii自带方法快捷使用:

```php
Yii::info('Info message');
Yii::error('Error message');
```

原生的monolog使用，具体用法可查看[monolog库](https://github.com/Seldaek/monolog):

```php
Yii::$app->monolog->info('My logger is now ready');
```

# License

MIT
