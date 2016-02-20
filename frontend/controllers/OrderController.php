<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\Cors;
use common\models\services\RoomService;
use common\models\services\OrderService;
use frontend\models\OrderQueryForm;
use frontend\models\OrderSubmitForm;

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
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'cors' => [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['submitorder', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['submitorder'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
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
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
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
        return $roomList;
    }

    /**
     * 查询dept列表
     *
     * @return mixed
     */
    public function actionGetdepts() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $deptList = OrderService::queryDeptList();
        return $deptList;
    }

    /**
     * 查询room列表
     *
     * @return mixed
     */
    public function actionGetroomtables() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->getIsPost() ? Yii::$app->request->post() : Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => 'getRoomTables']);
        if ($model->load($data, '') && $model->validate()) {
            return $model->getRoomTables();
        } else {
            throw new BadRequestHttpException($model->getErrorMessage());
        }
        return $data;
    }

     /**
     * 查询room列表
     *
     * @return mixed
     */
    public function actionSubmitorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->post();
        $model = new OrderSubmitForm(['scenario' => 'submitOrder']);

        if ($model->load($data, '') && $model->validate() && $result = $model->submitOrder()) {
            //return $result;
            return [
                'status' => 200,
                'message' => '提交成功',
            ];
        } else {
             throw new BadRequestHttpException($model->getErrorMessage());
        }
    }
}
