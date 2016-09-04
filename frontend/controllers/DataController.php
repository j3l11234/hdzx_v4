<?php

namespace frontend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use common\services\OrderService;
use common\services\RoomService;

/**
 * DataController 
 */
class DataController extends Controller
{
    public function behaviors()
    {
        return [
             'access' => [
                'class' => AccessControl::className(),
                'only' => ['getrooms', 'getdepts',],
                'rules' => [
                    [
                        'actions' => ['getrooms', 'getdepts',],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * 查询room列表
     *
     * @return mixed
     */
    public function actionGetrooms() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $roomList = RoomService::queryRoomList();
        return array_merge($roomList, [
            'error' => 0,
        ]);
    }

    /**
     * 查询dept列表
     *
     * @return mixed
     */
    public function actionGetdepts() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $deptList = OrderService::queryDeptList();
        return array_merge($deptList, [
            'error' => 0,
        ]);
    }
}
