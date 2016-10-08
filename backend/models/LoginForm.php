<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use common\models\entities\User;
use common\services\UserService;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user;


    /**
     * @inheritdoc
     */
    public function rules()
    {
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
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addError('password', '用户名或密码错误');
            return false;
        }
        if (!empty($user->password_hash)) {
            if (!$user->validatePassword($this->password)) {
                $this->addError('password', '用户名或密码错误');
                return false;
            }
        } else { //旧格式密码兼容
            if (md5($this->password) !== $user->old_passwd) {
                $this->addError('password', '用户名或密码错误');
                return false;
            } else {
                Yii::info('用户旧密码更新, username='.$user->username.', id='.$user->id, '用户操作');
                $user->setPassword($this->password);
                $user->generateAuthKey();
                $user->old_passwd = NULL;
                $user->save();
            }
        }
 
        if (!$user->checkPrivilege(User::PRIV_BACKEND)) {
            $this->addError('username', '该用户无后台登陆权限');
            return false;
        }
        $userService = new UserService($user);
        return Yii::$app->user->login($userService, $this->rememberMe ? 3600 * 24 * 30 : 0);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'username' => '用户名',
            'password' => '密码',
            'rememberMe' => '记住我',
        ];
    }


    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}
