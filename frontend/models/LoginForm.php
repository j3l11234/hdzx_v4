<?php
namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\base\UserException;
use common\behaviors\ErrorBehavior;
use common\models\entities\BaseUser;
use common\models\entities\User;
use common\models\entities\StudentUser;
use common\services\UserService;

/**
 * Login form
 */
class LoginForm extends Model {
    public $username;
    public $password;
    public $rememberMe = true;

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            ErrorBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            // username and password are both required
            [['username', 'password'], 'required', 'message'=>'{attribute} 不能为空'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
        ];
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean 是否登录成功
     */
    public function login() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        //根据用户名取得学生用户(优先)和正常用户
        $user = StudentUser::findByUsername($this->username, [BaseUser::STATUS_ACTIVE, BaseUser::STATUS_BLOCKED, BaseUser::STATUS_UNACTIVE, BaseUser::STATUS_UNVERIFY]);
        if ($user === null) {
            $user = User::findByUsername($this->username, [BaseUser::STATUS_ACTIVE, BaseUser::STATUS_BLOCKED, BaseUser::STATUS_UNACTIVE, BaseUser::STATUS_UNVERIFY]);
        }

        if ($user === null) {
            throw new UserException('用户不存在');
        }

        if (!$user->validatePassword($this->password)) {
            throw new UserException('密码不正确');
        }

        if ($user->status != BaseUser::STATUS_ACTIVE) {
            throw new UserException('该用户当前不可登陆');
        }

        $userService = new UserService($user);
        if(!Yii::$app->user->login($userService, $this->rememberMe ? 3600 * 24 * 30 : 0)) {
            throw new UserException('登录失败');
        }
        
        return '登录成功';
    }

}
