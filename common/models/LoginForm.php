<?php
namespace common\models;

use Yii;
use yii\base\Model;
use common\behaviors\ErrorBehavior;
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
        if ($this->validate()) {
            //根据用户名取得学生用户(优先)和正常用户
            $user = StudentUser::findByUsername($this->username);
            if ($user === null) {
                $user = User::findByUsername($this->username);
            }

            if ($user === null) {
                $this->setErrorMessage('用户不存在');
            } else {
                if ($user->validatePassword($this->password)) {
                    $userService = new UserService($user);
                    return Yii::$app->user->login($userService, $this->rememberMe ? 3600 * 24 * 30 : 0);
                }else{
                    $this->setErrorMessage('密码不正确');
                }
            }
        }
        return false;
    }

}
