<?php
namespace common\models;

use Yii;
use yii\base\Model;
use common\models\entities\User;
use common\models\entities\StudentUser;
use common\models\services\UserService;

/**
 * Login form
 */
class LoginForm extends Model {
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user;


    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
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
            $user = $this->getUser();
            if ($user === null) {
                $this->addError('username', '用户不存在');
            } else {
                if ($user->validatePassword($this->password)) {
                    $userService = new UserService($user);
                    return Yii::$app->user->login($userService, $this->rememberMe ? 3600 * 24 * 30 : 0);
                }else{
                    $this->addError('password', '密码不正确');
                }
            }
        }
        return false;
    }

    /**
     * 根据用户名取得正常用户和学生用户
     * 优先从学号判断
     *
     * @return User|null
     */
    protected function getUser() {
        $user = StudentUser::findByUsername($this->username);
        if ($user === null) {
            $user = User::findByUsername($this->username);
        }

        return $user;
    }
}
