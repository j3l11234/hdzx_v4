<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\base\UserException;

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
                    'getorders', 'getconflictorders', 'approveorder', 'rejectorder', 'revokeorder',
                ],
                'rules' => [
                    [
                        'class' => PrivilegeRule::className(),
                        'actions' => [
                            'approve-auto-page', 'approve-manager-page', 'approve-school-page',
                            'getorders', 'getconflictorders', 'approveorder', 'rejectorder', 'revokeorder',
                        ],
                        'roles' => ['@'],
                        'allow' => true,
                        //'privileges' => [BaseUser::PRIV_ADMIN],
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
    public function actionApproveSimplePage()
    {
        return $this->render('/page/approve', [
            'apprveType' => 'simple',
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
     * 查询审批的申请
     *
     * @return mixed
     */
    public function actionGetorders() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new ApproveQueryForm(['scenario' => ApproveQueryForm::SCENARIO_GET_APPROVE_ORDER]);
        $model->load($reqData, '');
        $resData = $model->getApproveOrders();
        return $resData;
    }


    /**
     * 查询与单个申请相冲突的申请
     *
     * @return mixed
     */
    public function actionGetconflictorders() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new ApproveQueryForm(['scenario' => ApproveQueryForm::SCENARIO_GET_CONFLICT_ORDER]);
        $model->load($reqData, '');
        $resData = $model->getConflictOrders();
        return $resData;
    }

    
    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionApproveorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        $model = new ApproveForm(['scenario' => 'approveOrder']);
        $model->load($reqData, '');
        $resData = $model->approveOrder();
        return [
            'message' => $resData,
        ];
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionRejectorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        $model = new ApproveForm(['scenario' => 'rejectOrder']);
        $model->load($reqData, '');
        $resData = $model->rejectOrder();
        return [
            'message' => $resData,
        ];
    }

    /**
     * 审批预约
     *
     * @return mixed
     */
    public function actionRevokeorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        $model = new ApproveForm(['scenario' => 'revokeOrder']);
         $model->load($reqData, '');
        $resData = $model->revokeOrder();
        return [
            'message' => $resData,
        ];
    }

    /**
     * 自动审批-琴房自动通过
     *
     * @return mixed
     */
    public function actionAutoapprove1() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        if (!isset($reqData['token']) || $reqData['token'] != Yii::$app->params['cronKey']) {
            throw new UserException('token不正确');
        }

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
        
        $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        if (!isset($reqData['token']) || $reqData['token'] != Yii::$app->params['cronKey']) {
            throw new UserException('token不正确');
        }

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
        
        $reqData = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        if (!isset($reqData['token']) || $reqData['token'] != Yii::$app->params['cronKey']) {
            throw new UserException('token不正确');
        }

        $data = ApproveService::autoApprove3();
        return $data;
    }
}
