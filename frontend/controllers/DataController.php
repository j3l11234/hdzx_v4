<?php

namespace frontend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use common\models\entities\Setting;
use common\services\OrderService;
use common\services\RoomService;
use common\services\SettingService;

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

        $data = RoomService::getRoomList();
        return array_merge($data, [
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

        ;
        return array_merge($deptList, [
            'error' => 0,
        ]);
    }

    /**
     * 查询dept列表
     *
     * @return mixed
     */
    public function actionGetdata($page = NULL) {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = [];
        if ($page == 'order') {
            $data['room'] = RoomService::getRoomList();
            $data['dept'] = OrderService::queryDeptList();
            $data['tooltip'] = SettingService::getSetting(Setting::ORDER_PAGE_TOOLTIP)['value'];
        } else {
            return [
                'error' => 1,
                'message' => '页面不存在',
            ];
        }

        return array_merge($data, [
            'error' => 0,
        ]);   
    }
}
