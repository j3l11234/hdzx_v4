<?php

namespace common\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;
use yii\base\UserException;

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
        return $data;
    }

    /**
     * 查询dept列表
     *
     * @return mixed
     */
    public function actionGetdepts() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = OrderService::queryDeptList();
        return $data;
    }

     /**
     * 查询页面数据
     *
     * @return mixed
     */
    public function actionGetmetadata($page = NULL, $type=NULL) {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        if (isset($reqData['room'])) {
            $data['room'] = RoomService::getRoomList();
        }
        if (isset($reqData['dept'])) {
            $data['dept'] = OrderService::queryDeptList();
        }
        if (isset($reqData['tooltip'])) {
            if ($reqData['tooltip'] == 'order') {
                $data['tooltip'] = SettingService::getSetting(Setting::ORDER_PAGE_TOOLTIP)['value'];
            } else if($reqData['tooltip'] == 'lock') {
                $data['tooltip'] = SettingService::getSetting(Setting::LOCK_PAGE_TOOLTIP)['value'];
            } else if($reqData['tooltip'] == 'login') {
                $data['tooltip'] = '<br/><div class="alert alert-info" role="alert">如果要进行后台操作，如 审批预约/发放开门条/系统管理，请进入<a href="'.Url::to([Yii::$app->params['backendUrl']]).'"><b>后台系统</b></a>登录</div>';
            }
        }

        return $data;   
    }

    /**
     * 查询页面数据
     *
     * @return mixed
     */
    public function actionGetdata($page = NULL, $type=NULL) {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = [];
        if ($page == 'order') {
            $data['room'] = RoomService::getRoomList();
            $data['dept'] = OrderService::queryDeptList();
            $data['tooltip'] = SettingService::getSetting(Setting::ORDER_PAGE_TOOLTIP)['value'];
        } else if ($page == 'lock' && $type =='user') {
            $data['room'] = RoomService::getRoomList();
            $data['tooltip'] = SettingService::getSetting(Setting::LOCK_PAGE_TOOLTIP)['value'];
        } else if ($page == 'lock' && $type =='admin') {
            $data['room'] = RoomService::getRoomList();
            $data['tooltip'] = '';
        } else if ($page == 'login') {
            $data['tooltip'] = '<br/><div class="alert alert-info" role="alert">如果要进行后台操作，如 审批预约/发放开门条/系统管理，请进入<a href="'.Url::to([Yii::$app->params['backendUrl']]).'"><b>后台系统</b></a>登录</div>';
        } else if ($page == 'approve') {
            $data['room'] = RoomService::getRoomList();
            $data['dept'] = OrderService::queryDeptList();
        } else {
            throw new UserException('页面不存在');
        }

        return $data;   
    }
}
