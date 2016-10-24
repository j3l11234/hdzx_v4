<?php
$params = array_merge(
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'class' => 'yii\web\User',
            'identityClass' => 'common\services\UserService',
            'enableAutoLogin' => true,
        ],
        'assetManager' => [
            'class' => 'yii\web\AssetManager',
            'forceCopy' => YII_DEBUG ? TRUE : FALSE,
        ],
        'request' => [
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'log' => [
            //'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'common\helpers\FileTarget',
                    'enabled' => YII_DEBUG ? TRUE : FALSE,
                    //'exportInterval' => 1,
                    'logFile' => '@common/runtime/logs/app.log',
                    'logVars' => [],
                    //'levels' => ['error', 'trace','warning'],
                    'levels' => ['profile'],

                ],
            ],
        ],
    ],
    'controllerMap' => [
        'data' => 'common\controllers\DataController',
    ],

    'name'=>'学活场地申请系统',
    'timeZone'=>'Asia/Chongqing',
    'language'=>'zh-CN',
    'params' => $params
];
