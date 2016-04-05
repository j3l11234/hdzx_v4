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
use common\models\LoginForm;
use common\models\PasswordResetForm;
use frontend\models\UserActiveForm;

/**
 * User controller
 */
class UserController extends Controller {
    /**
     * @inheritdoc
     */
    public function behaviors() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
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
                'class' => 'frontend\actions\MyCaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin() {
        if (!\Yii::$app->user->isGuest) {
            throw new BadRequestHttpException('已经登录');
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post(),'') && $model->login()) {
            return [
                'status' => 200,
                'message' => '您已经登录成功',
                'user' => $model->getUser()->toArray(['dept_id', 'email', 'alias', 'privilege'])
            ];
        } else {
            throw new BadRequestHttpException($model->getErrorMessage());
        }
    }

    /**
     * 获取自动登录信息
     *
     * @return mixed
     */
    public function actionGetlogin() {
        if (\Yii::$app->user->isGuest) {
            $user = null;
        }else {
            $user = Yii::$app->user->getIdentity()->getUser()->toArray(['dept_id', 'email', 'alias', 'privilege']);
        }

        return [
            'status' => 200,
            'user' => $user,
        ];
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout() {
        Yii::$app->user->logout();
        return [
            'status' => 200,
            'message' => '您已经注销成功'
        ];
    }

    /**
     * 重设密码请求
     *
     * @return mixed
     */
    public function actionRequestPasswordReset() {
        Yii::$app->response->format = Response::FORMAT_HTML;
        $model = new PasswordResetForm(['scenario' => 'request']);
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->requestReset()) {
                Yii::$app->session->setFlash('success', '发送邮件成功，请查收邮件并根据提示操作');
            } else {
                Yii::$app->session->setFlash('error', $model->getErrorMessage());
            }
        }

        return $this->render('requestResetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * 重设密码
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token) {
        Yii::$app->response->format = Response::FORMAT_HTML;

        $model = new PasswordResetForm(['scenario' => 'reset']);

        try {
            $model->validateToken($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->resetPassword()) {
                Yii::$app->session->setFlash('success', '密码重设成功');
                return $this->render('../result');
            } else {
                Yii::$app->session->setFlash('error', $model->getErrorMessage());
            }
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * 申请激活账户
     *
     * @return mixed
     */
    public function actionRequestActiveUser() {
        Yii::$app->response->format = Response::FORMAT_HTML;

        $model = new UserActiveForm(['scenario' => 'request']);
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if($model->request()) {
                Yii::$app->session->setFlash('success', '发送邮件成功，请查收邮件并根据提示操作');
            } else {
                Yii::$app->session->setFlash('error', $model->getErrorMessage());
            }
        }

        return $this->render('requestActiveUser', [
            'model' => $model,
        ]);
    }

    /**
     * 激活账户
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionActiveUser($token) {
        Yii::$app->response->format = Response::FORMAT_HTML;

        $model = new UserActiveForm(['scenario' => 'active']);

        try {
            $model->validateToken($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        
        if ($model->active()) {
            Yii::$app->session->setFlash('success', '激活成功');
        } else {
            Yii::$app->session->setFlash('error', $model->getErrorMessage());
        }

        return $this->render('../result');
    }
}
