<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace backend\controllers;

use Yii;

use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\data\ActiveDataProvider;

use common\models\entities\BaseUser;
use common\models\entities\User;
use common\models\entities\StudentUser;
use common\services\UserService;
use backend\models\LoginForm;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'logout'],
                'rules' => [
                    [
                        'actions' => ['login',],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout',],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find(['status' => [BaseUser::STATUS_DELETED, BaseUser::STATUS_ACTIVE, BaseUser::STATUS_BLOCKED, BaseUser::STATUS_UNACTIVE, BaseUser::STATUS_UNVERIFY]]),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lists all Student User models.
     * @return mixed
     */
    public function actionStudent()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => StudentUser::find(['status' => [BaseUser::STATUS_DELETED, BaseUser::STATUS_ACTIVE, BaseUser::STATUS_BLOCKED, BaseUser::STATUS_UNACTIVE, BaseUser::STATUS_UNVERIFY]]),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();
        $model->scenario = BaseUser::SCENARIO_CREATE;
        $model->managers = [1];
        $model->status = BaseUser::STATUS_ACTIVE;
        $model->generateAuthKey();
        
        $postData = Yii::$app->request->post();
        if(!empty($postData['User']['managers'])){
            $postData['User']['managers'] = json_decode($postData['User']['managers']);
        }
        if(!empty($postData['User']['privilege'])){
            $postData['User']['privilege'] = BaseUser::privilegeList2Num($postData['User']['privilege']);
        }

        $result = false;
        if ($model->load($postData) && $model->validate()) {
            //密码不为空则修改密码
            if(!empty($model->password)) {
                $model->setPassword($model->password);
            }
            $result = $model->save(false);
        } else {
            $result = false;
        }

        if ($result) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = BaseUser::SCENARIO_UPDATE;

        $postData = Yii::$app->request->post();
        if(!empty($postData['User']['managers'])){
            $postData['User']['managers'] = json_decode($postData['User']['managers']);
        }
        if(!empty($postData['User']['privilege'])){
            $postData['User']['privilege'] = BaseUser::privilegeList2Num($postData['User']['privilege']);
        }
        
        $result = false;
        if ($model->load($postData) && $model->validate()) {
            //密码不为空则修改密码
            if(!empty($model->password)) {
                $model->setPassword($model->password);
            }
            $result = $model->save(false);
        } else {
            $result = false;
        }

        if ($result) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $user = UserService::findUser($id,[BaseUser::STATUS_DELETED, BaseUser::STATUS_ACTIVE, BaseUser::STATUS_BLOCKED, BaseUser::STATUS_UNACTIVE, BaseUser::STATUS_UNVERIFY]);
        
        if (($model = $user) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
