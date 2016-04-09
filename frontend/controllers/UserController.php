<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\helpers\Url;
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
     * 登录页面
     *
     * @return mixed
     */
    public function actionLoginPage()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        return $this->render('/page/login');
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!\Yii::$app->user->isGuest) {
            return [
                'error' => 1,
                'message' => '您已经登录',
            ];
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post(),'') && $model->login()) {
            return [
                'error' => 0,
                'message' => '您已经登录成功',
                'url' => Yii::$app->user->getReturnUrl(),
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout() {
        Yii::$app->user->logout();
        
        return $this->goHome();
    }


    /**
     * 申请重设密码
     *
     * @return mixed
     */
    public function actionRequestPasswordReset() {
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
