<?php
namespace frontend\controllers;

use Yii;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\Cors;

/**
 * User controller
 */
class UserController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'cors' => [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                ],
            ],
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
                'class' => 'yii\captcha\CaptchaAction',
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
        Yii::$app->response->format = Response::FORMAT_JSON;

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
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (\Yii::$app->user->isGuest) {
            return [
                'user' => null,
            ];
        }else {
            return [
                'user' => \Yii::$app->user->getIdentity()->getUser()->toArray(['dept_id', 'email', 'alias', 'privilege']),
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
        return [
            'status' => 200,
            'message' => '您已经注销成功'
        ];
    }
}
