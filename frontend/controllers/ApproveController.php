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
use frontend\models\ApproveForm;

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
                    [
                        'actions' => ['approveOrder'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                    'approveOrder' => ['post'],
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
     * 查询审批的预约
     *
     * @return mixed
     */
    public function actionGetorder() {
        $data = Yii::$app->request->get();
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
     * 审批预约
     *
     * @return mixed
     */
    public function actionApproveorder() {
        $getData = Yii::$app->request->get();
        $data = Yii::$app->request->post();
        $data['type'] = $getData['type'];
        
        $model = new ApproveForm(['scenario' => 'approveOrder']);
        $model->load($data, '');
        if ($model->validate() && $model->approveOrder()) {
            return [
                'status' => 200,
                'message' => '审批成功',
            ];
        } else {
            throw new BadRequestHttpException($model->getErrorMessage());
        }
        return $data;
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionRejectorder() {
        $getData = Yii::$app->request->get();
        $data = Yii::$app->request->post();
        $data['type'] = $getData['type'];
        
        $model = new ApproveForm(['scenario' => 'rejectOrder']);
        $model->load($data, '');
        if ($model->validate() && $model->rejectOrder()) {
            return [
                'status' => 200,
                'message' => '审批成功',
            ];
        } else {
            throw new BadRequestHttpException($model->getErrorMessage());
        }
        return $data;
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionRevokeorder() {
        $getData = Yii::$app->request->get();
        $data = Yii::$app->request->post();
        $data['type'] = $getData['type'];
        
        $model = new ApproveForm(['scenario' => 'revokeOrder']);
        $model->load($data, '');
        if ($model->validate() && $model->revokeOrder()) {
            return [
                'status' => 200,
                'message' => '撤回成功',
            ];
        } else {
            throw new BadRequestHttpException($model->getErrorMessage());
        }
        return $data;
    }


    
}
