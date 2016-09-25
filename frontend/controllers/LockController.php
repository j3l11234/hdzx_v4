<?php
namespace frontend\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;

use frontend\models\LockQueryForm;

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
                    'lock-page', 'getlocks',
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
        return $this->render('/page/lock', [
            'type' => 'user',
        ]);
    }

    /**
     * 查询房间锁
     *
     * @return mixed
     */
    public function actionGetlocks() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new LockQueryForm(['scenario' => 'getLocks']);
        $model->load($reqData, '');
        if ($model->validate()) {
            $resData = $model->getLocks();
            return array_merge($resData, [
                'error' => 0,
            ]);
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
        return $resData;
    }
}
