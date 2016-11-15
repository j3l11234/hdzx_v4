<?php
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
        'response' => [
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->format == \yii\web\Response::FORMAT_JSON && $response->data !== null) {
                    $response->data = array_merge($response->data, ['_success' => $response->isSuccessful]);
                    $response->statusCode = 200;
                }
            },
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

    'name'=>'学活场地申请系统',
    'timeZone'=>'Asia/Chongqing',
    'language'=>'zh-CN',
];
