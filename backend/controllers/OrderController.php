<?php
namespace backend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\Cors;

use common\services\RoomService;
use common\filter\PrivilegeRule;
use common\models\entities\BaseUser;
use backend\models\OrderQueryForm;
use backend\models\OrderForm;

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
                'only' => ['issue-page', 'getissueorders', 'issueorder',],
                'rules' => [
                    [
                        'class' => PrivilegeRule::className(),
                        'actions' =>  ['issue-page', 'getissueorders', 'issueorder',],
                        'roles' => ['@'],
                        'allow' => true,
                        'privileges' => [BaseUser::PRIV_ISSUE],
                    ],
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
                //'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'fixedVerifyCode' => 'testme',
                'height' => 36,
                'padding' => 0,
            ],
        ];
    }

    /**
     * 发放开门条-页面
     *
     * @return mixed
     */
    public function actionIssuePage()
    {
        return $this->render('/page/issue');
    }


    /**
     * 查询开门条预约
     *
     * @return mixed
     */
    public function actionGetissueorders() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => OrderQueryForm::SCENARIO_GET_ISSUE]);
        $model->load($reqData, '');
        $resData = $model->getIssueOrders();
        return $resData;
    }

    /**
     * 提交预约
     *
     * @return mixed
     */
    public function actionIssueorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->post();
            
        $model = new OrderForm(['scenario' => OrderForm::SCENARIO_ISSUE]);
        $model->load($reqData, '');
        $resData = $model->issueOrder();
        return [
            'message' => $resData,
        ];
    }
}
