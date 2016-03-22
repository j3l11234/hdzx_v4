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
                'class' => 'frontend\actions\MyCaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'height' => 40,
                'padding' => 0,
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
            'status' => 200,
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
            'status' => 200,
        ]);
    }

    /**
     * 查询Getroomtables
     *
     * @return mixed
     */
    public function actionGetroomtables() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new OrderQueryForm(['scenario' => 'getRoomTables']);
        $model->load(Yii::$app->request->get(), '');
        if ($model->validate()) {
            $data = $model->getRoomTables();
            return array_merge($data, [
                'status' => 200,
            ]);
        } else {
            throw new BadRequestHttpException($model->getErrorMessage());
        }
    }

    /**
     * 查询room当日占用
     *
     * @return mixed
     */
    public function actionGetroomuse() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => 'getRoomUse']);
        $model->load($data, '');
        if ($model->validate()) {
            $model->rt_detail = true;
            return $model->getRoomUse();
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

        $captchaAction = $this->createAction('captcha');
        if (!$captchaAction->validate($data['captcha'], false)){
            return [
                'status' => 601,
                'message' => '验证码错误',
            ];
        }

            
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
