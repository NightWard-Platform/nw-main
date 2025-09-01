<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@common' => dirname(__DIR__, 2) . '/common',
        '@backend' => dirname(__DIR__, 2) . '/backend',
        '@frontend' => dirname(__DIR__, 2) . '/frontend',
    ],
    'controllerMap' => [
        'daemon' => [
            'class' => 'inpassor\daemon\Controller',
            'uid' => 'daemon', // The daemon UID. Giving daemons different UIDs makes possible to run several daemons.
            'pidDir' => '@runtime/daemon', // PID file directory.
            'logsDir' => '@runtime/logs', // Log files directory.
            'clearLogs' => false, // Clear log files on start.
            'workersMap' => [
                'webhook' => [
                    'class' => 'console\workers\Webhook',
                    'active' => true, // If set to false, worker is disabled.
                    'maxProcesses' => 1, // The number of maximum processes of the daemon worker running at once.
                    'delay' => 1, // The time, in seconds, the timer should delay in between executions of the daemon worker.
                ],
            ],
        ],
        'fixture' => [
            'class' => \yii\console\controllers\FixtureController::class,
            'namespace' => 'common\fixtures',
        ],
    ],
    'components' => [
        'rabbitmq' => [
            'class' => 'common\components\RabbitMq',
            'host' => 'rabbitmq',
            'port' => 5672,
            'user' => 'admin',
            'password' => 'admin'
        ],
        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'params' => $params,
];
