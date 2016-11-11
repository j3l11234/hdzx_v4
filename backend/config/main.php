<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'backend\controllers',   
    'modules' => [],
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
                '/approve/simple' => 'approve/approve-simple-page',
                '/approve/manager' => 'approve/approve-manager-page',
                '/approve/school' => 'approve/approve-school-page',
                '/lock' => 'lock/lock-page',
                '/issue' => 'order/issue-page',
            ]
        ],
        'user' => [
            'identityClass' => 'common\services\UserService',
            'enableAutoLogin' => true,
            'idParam' => '__id_admin',
            'loginUrl' => ['user/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'params' => $params,
];
