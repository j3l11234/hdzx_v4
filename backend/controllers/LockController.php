<?php
namespace backend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;

use common\models\entities\BaseUser;
use backend\models\LockQueryForm;
use backend\models\LockForm;

/**
 * Lock controller
 */
class LockController extends Controller
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
                    'lock-page', 'getlocks', 'approveorder', 'rejectorder', 'revokeorder',
                ],
                'rules' => [
                    [
                        'actions' => [
                            'lock-page', 'getlocks',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
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
        ];
    }

    /**
     * 房间锁-页面
     *
     * @return mixed
     */
    public function actionLockPage()
    {
        $this->checkPrivilege(BaseUser::PRIV_ADMIN);
        return $this->render('/page/lock', [
            'type' => 'admin',
        ]);
    }

    /**
     * 查询房间锁
     *
     * @return mixed
     */
    public function actionGetlocks() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->checkPrivilege(BaseUser::PRIV_ADMIN);

        $data = Yii::$app->request->get();
        $model = new LockQueryForm(['scenario' => 'getLocks']);
        $model->load($data, '');
        if ($model->validate()) {
            $data = $model->getLocks();
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
     * 新增房间锁
     *
     * @return mixed
     */
    public function actionSubmitlock() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->checkPrivilege(BaseUser::PRIV_ADMIN);

        $reqData = Yii::$app->request->post(); 
        $model = new LockForm();
        if (empty($reqData['lock_id'])) {
            $model->scenario =  LockForm::SCENARIO_ADD_LOCK;
        } else {
             $model->scenario =  LockForm::SCENARIO_EDIT_LOCK;
        }
        $model->load($reqData, '');
        if ($model->validate() && $resData = $model->submitLock()) {
            return [
                'error' => 0,
                'message' => $model->getMessage(),
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * 新增房间锁
     *
     * @return mixed
     */
    public function actionDeletelock() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->checkPrivilege(BaseUser::PRIV_ADMIN);

        $reqData = Yii::$app->request->post(); 
        $model = new LockForm();
        $model->scenario =  LockForm::SCENARIO_DELETE_LOCK;

        $model->load($reqData, '');
        if ($model->validate() && $resData = $model->deleteLock()) {
            return [
                'error' => 0,
                'message' => $model->getMessage(),
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * 新增房间锁
     *
     * @return mixed
     */
    public function actionApplylock() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->checkPrivilege(BaseUser::PRIV_ADMIN);

        $reqData = Yii::$app->request->post(); 
        $model = new LockForm();
        $model->scenario =  LockForm::SCENARIO_APPLY_LOCK;

        $model->load($reqData, '');
        if ($model->validate() && $resData = $model->applyLock()) {
            return [
                'error' => 0,
                'message' => $model->getMessage(),
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    protected function checkPrivilege($privilege) {
        $user = Yii::$app->user->getIdentity()->getUser();
        if(empty($user) || !$user->checkPrivilege($privilege)){
            throw new ForbiddenHttpException('您没有权限执行该操作');
        }
    }
}
