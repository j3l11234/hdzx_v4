<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\base\UserException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\Cors;

use common\services\RoomService;
use frontend\models\OrderQueryForm;
use frontend\models\OrderSubmitForm;
use frontend\actions\CreatePdfAction;

/**
 * Order controller
 */
class OrderController extends Controller
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => [
                    'order-page', 'myorder-page', 
                    'getrooms', 'getdepts', 'getroomtables', 'getroomuse', 'getusage', 'getmyorders', 'submitorder', 'cancelorder'],
                'rules' => [
                    [
                        'actions' => [
                            'order-page', 'myorder-page', 
                            'getrooms', 'getdepts', 'getroomtables', 'getroomuse', 'getusage', 'getmyorders', 'submitorder', 'cancelorder'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => [''],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'getrooms' => ['get'],
                    'getdepts' => ['get'],
                    'getroomtables' => ['get'],
                    'getroomuse' => ['get'],
                    'submitorder' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'frontend\actions\MyCaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                //'fixedVerifyCode' => 'testme',
                'height' => 36,
                'padding' => 0,
            ],
            'getapply' => [
                'class' => CreatePdfAction::className(),
                'expire' => 300,
                //'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * 预约房间-页面
     *
     * @return mixed
     */
    public function actionOrderPage()
    {
        return $this->render('/page/order');
    }

    /**
     * 我的预约-页面
     *
     * @return mixed
     */
    public function actionMyorderPage()
    {
        return $this->render('/page/myorder');
    }

    /**
     * 查询Getroomtables
     *
     * @return mixed
     */
    public function actionGetroomtables() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => 'getRoomTables']);
        $model->load($reqData, '');
        $resData = $model->getRoomTables();
        return $resData;
    }

    /**
     * 查询room当日占用
     *
     * @return mixed
     */
    public function actionGetroomuse() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => 'getRoomUse']);
        $model->load($reqData, '');
        $resData = $model->getRoomUse();
        return $resData;
    }

    /**
     * 查询单个用户的使用情况
     *
     * @return mixed
     */
    public function actionGetusage() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => 'getUsage']);
        $model->load($reqData, '');
        $resData = $model->getUsage();
        return $resData;
    }


    /**
     * 提交预约
     *
     * @return mixed
     */
    public function actionSubmitorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->post();

        $captchaAction = $this->createAction('captcha');
        if (empty($reqData['captcha']) || !$captchaAction->validate($reqData['captcha'], false)) {
            throw new UserException('验证码错误');
        }
            
        $model = new OrderSubmitForm(['scenario' => OrderSubmitForm::SCENARIO_SUBMIT_ORDER]);
        $model->load($reqData, '');
        $resData = $model->submitOrder();
        return [
            'message' => $resData,
        ];
    }


    /**
     * 取消预约
     *
     * @return mixed
     */
    public function actionCancelorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post()); 
        $model = new OrderSubmitForm(['scenario' => OrderSubmitForm::SCENARIO_CANCEL_ORDER]);
        $model->load($reqData, '');
        $resData = $model->cancelOrder();
        return [
            'message' => $resData,
        ];
    }


    /**
     * 取消预约
     *
     * @return mixed
     */
    public function actionPaperorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post()); 
        $model = new OrderSubmitForm(['scenario' => OrderSubmitForm::SCENARIO_PAPER_ORDER]);
        $model->load($reqData, '');

        $orderData = $model->paperOrder();
        $action = $this->createAction('getapply');
        $url = $action->setPdfData($orderData);
        return [
            'url' => $url,
            'expire' => $action->expire,
        ];
    }


    /**
     * 查询我的预约
     *
     * @return mixed
     */
    public function actionGetmyorders() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => 'getMyOrders']);
        $model->load($reqData, '');
        $resData = $model->getMyOrders();
        return $resData;
    }


    /**
     * 更新申请额外信息
     * (旧数据兼容方案)
     *
     * @return mixed
     */
    public function actionUpdateorderext() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post()); 
        $model = new OrderSubmitForm(['scenario' => OrderSubmitForm::SCENARIO_UPDATE_ORDER_EXT]);
        $model->load($reqData, '');
        $resData = $model->updateOrderExt();
        return $resData;
    }
}
