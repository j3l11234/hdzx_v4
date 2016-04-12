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
     * 房间锁-页面
     *
     * @return mixed
     */
    public function actionLockPage()
    {
        return $this->render('/page/lock', [
            'isAdmin' => true,
        ]);
    }

    /**
     * 查询房间锁
     *
     * @return mixed
     */
    public function actionGetlocks() {
        Yii::$app->response->format = Response::FORMAT_JSON;

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

    // /**
    //  * 审批预约
    //  *
    //  * @return mixed
    //  */
    // public function actionApproveorder() {
    //     Yii::$app->response->format = Response::FORMAT_JSON;

    //     $getData = Yii::$app->request->get();
    //     $data = Yii::$app->request->post();
    //     $data['type'] = $getData['type'];
        
    //     $model = new ApproveForm(['scenario' => 'approveOrder']);
    //     $model->load($data, '');
    //     if ($model->validate() && $model->approveOrder()) {
    //         return [
    //             'error' => 0,
    //             'message' => '审批成功',
    //         ];
    //     } else {
    //         return [
    //             'error' => 1,
    //             'message' => $model->getErrorMessage(),
    //         ];
    //     }
    //     return $data;
    // }


    
}
