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

use common\services\ApproveService;
use common\services\OrderService;
use backend\models\ApproveQueryForm;
use backend\models\ApproveForm;

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
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => [
                    'approve-auto-page', 'approve-manager-page', 'approve-school-page',
                    'getorders', 'getdepts', 'approveorder', 'rejectorder', 'revokeorder',
                ],
                'rules' => [
                    [
                        'actions' => [
                            'approve-auto-page', 'approve-manager-page', 'approve-school-page',
                            'getorders', 'getdepts', 'approveorder', 'rejectorder', 'revokeorder',
                        ],
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
     * 自动审批-页面
     *
     * @return mixed
     */
    public function actionApproveAutoPage()
    {
        $dateRange = ApproveQueryForm::getDateRange();
        return $this->render('/page/approve', [
            'apprveType' => 'auto',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', $dateRange['end']),
        ]);
    }

    /**
     * 负责人审批-页面
     *
     * @return mixed
     */
    public function actionApproveManagerPage()
    {
        $dateRange = ApproveQueryForm::getDateRange();
        return $this->render('/page/approve', [
            'apprveType' => 'manager',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', $dateRange['end']),
        ]);
    }

    /**
     * 校级审批-页面
     *
     * @return mixed
     */
    public function actionApproveSchoolPage()
    {
        $dateRange = ApproveQueryForm::getDateRange();
        return $this->render('/page/approve', [
            'apprveType' => 'school',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', $dateRange['end']),
        ]);
    }

    /**
     * 查询审批的预约
     *
     * @return mixed
     */
    public function actionGetorders() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->get();
        $model = new ApproveQueryForm(['scenario' => 'getApproveOrder']);
        $model->load($data, '');
        if ($model->validate()) {
            $data = $model->getApproveOrder();
            return array_merge($data, [
                'error' => 0,
            ]);
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
        return $data;
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionApproveorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $getData = Yii::$app->request->get();
        $data = Yii::$app->request->post();
        $data['type'] = $getData['type'];
        
        $model = new ApproveForm(['scenario' => 'approveOrder']);
        $model->load($data, '');
        if ($model->validate() && $model->approveOrder()) {
            return [
                'error' => 0,
                'message' => '审批成功',
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
        return $data;
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionRejectorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $getData = Yii::$app->request->get();
        $data = Yii::$app->request->post();
        $data['type'] = $getData['type'];
        
        $model = new ApproveForm(['scenario' => 'rejectOrder']);
        $model->load($data, '');
        if ($model->validate() && $model->rejectOrder()) {
            return [
                'error' => 0,
                'message' => '审批成功',
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
        return $data;
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionRevokeorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $getData = Yii::$app->request->get();
        $data = Yii::$app->request->post();
        $data['type'] = $getData['type'];
        
        $model = new ApproveForm(['scenario' => 'revokeOrder']);
        $model->load($data, '');
        if ($model->validate() && $model->revokeOrder()) {
            return [
                'error' => 0,
                'message' => '撤回成功',
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
        return $data;
    }

    /**
     * 自动审批-负责人驳回
     *
     * @return mixed
     */
    public function actionAutoapprove1() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = ApproveService::autoApprove1();
        return $data;
    }
    
}
