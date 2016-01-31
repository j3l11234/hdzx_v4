<?php
namespace frontend\controllers;

use Yii;
use common\models\services\RoomService;
use frontend\models\OrderQueryForm;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\Cors;

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
            'cors' => [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
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
    }
  
}
