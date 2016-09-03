<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;

use common\services\ApproveService;
use common\services\OrderService;
use backend\models\ApproveQueryForm;
use backend\models\ApproveForm;
use common\filter\PrivilegeRule;
use common\models\entities\BaseUser;

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
                        'class' => PrivilegeRule::className(),
                        'actions' => [
                            'approve-auto-page', 'approve-manager-page', 'approve-school-page',
                            'getorders', 'getdepts', 'approveorder', 'rejectorder', 'revokeorder',
                        ],
                        'roles' => ['@'],
                        'allow' => true,
                        'privileges' => [BaseUser::PRIV_ADMIN],
                    ],


                ],
            ]
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
        return $this->render('/page/approve', [
            'apprveType' => 'auto',
        ]);
    }

    /**
     * 负责人审批-页面
     *
     * @return mixed
     */
    public function actionApproveManagerPage()
    {
        return $this->render('/page/approve', [
            'apprveType' => 'manager',
        ]);
    }

    /**
     * 校级审批-页面
     *
     * @return mixed
     */
    public function actionApproveSchoolPage()
    {
        return $this->render('/page/approve', [
            'apprveType' => 'school',
        ]);
    }

    /**
     * 查询审批的预约
     *
     * @return mixed
     */
    public function actionGetorders() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new ApproveQueryForm(['scenario' => 'getApproveOrder']);
        if ($model->load($reqData, '') && $model->validate() && $resData = $model->getApproveOrder()) {
            return array_merge($resData, [
                'error' => 0,
            ]);
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionApproveorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $getData = Yii::$app->request->get();
        $reqData = Yii::$app->request->post();
        $reqData['type'] = $getData['type'];
        
        $model = new ApproveForm(['scenario' => 'approveOrder']);
        if ($model->load($reqData, '') && $model->validate() && $model->approveOrder()) {
            return [
                'error' => 0,
                'message' => '审批通过成功',
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionRejectorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $getData = Yii::$app->request->get();
        $reqData = Yii::$app->request->post();
        $reqData['type'] = $getData['type'];
        
        $model = new ApproveForm(['scenario' => 'rejectOrder']);
        if ($model->load($reqData, '') && $model->validate() && $model->rejectOrder()) {
            return [
                'error' => 0,
                'message' => '审批驳回成功',
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionRevokeorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $getData = Yii::$app->request->get();
        $reqData = Yii::$app->request->post();
        $reqData['type'] = $getData['type'];
        
        $model = new ApproveForm(['scenario' => 'revokeOrder']);
        if ($model->load($reqData, '') && $model->validate() && $model->revokeOrder()) {
            return [
                'error' => 0,
                'message' => '审批撤回成功',
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * 自动审批-琴房自动通过
     *
     * @return mixed
     */
    public function actionAutoapprove1() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = ApproveService::autoApprove1();
        return $data;
    }
    
    /**
     * 自动审批-负责人自动驳回
     *
     * @return mixed
     */
    public function actionAutoapprove2() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = ApproveService::autoApprove2();
        return $data;
    }

    /**
     * 自动审批-3天校级审批自动通过
     *
     * @return mixed
     */
    public function actionAutoapprove3() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = ApproveService::autoApprove3();
        return $data;
    }
}
