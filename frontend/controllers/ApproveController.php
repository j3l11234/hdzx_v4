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

use common\models\services\ApproveService;
use frontend\models\ApproveQueryForm;

/**
 * Order controller
 */
class ApproveController extends Controller
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
                        'actions' => ['getautoorder'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['getmanagerorder'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['getschoolorder'],
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
        ];
    }

    /**
     * 查询自动审批的预约
     *
     * @return mixed
     */
    public function actionGetautoorder() {
        $data = Yii::$app->request->get();
        $data['type'] = ApproveService::TYPE_AUTO;
        $model = new ApproveQueryForm(['scenario' => 'getApproveOrder']);
        $model->load($data, '');
        if ($model->validate()) {
            return $model->getApproveOrder();
        } else {
            throw new BadRequestHttpException($model->getErrorMessage());
        }
        return $data;
    }

    /**
     * 查询负责人审批的预约
     *
     * @return mixed
     */
    public function actionGetmanagerorder() {
        $data = Yii::$app->request->get();
        $data['type'] = ApproveService::TYPE_MANAGER;
        $model = new ApproveQueryForm(['scenario' => 'getApproveOrder']);
        $model->load($data, '');
        if ($model->validate()) {
            return $model->getApproveOrder();
        } else {
            throw new BadRequestHttpException($model->getErrorMessage());
        }
        return $data;
    }

    /**
     * 查询校级审批的预约
     *
     * @return mixed
     */
    public function actionGetschoolorder() {
        $data = Yii::$app->request->get();
        $data['type'] = ApproveService::TYPE_SCHOOL;
        $model = new ApproveQueryForm(['scenario' => 'getApproveOrder']);
        $model->load($data, '');
        if ($model->validate()) {
            return $model->getApproveOrder();
        } else {
            throw new BadRequestHttpException($model->getErrorMessage());
        }
        return $data;
    }
}
