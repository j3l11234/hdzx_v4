<?php
namespace frontend\models;

use Yii;
use yii\base\InvalidParamException;
use yii\base\Model;
use common\behaviors\ErrorBehavior;
use common\models\entities\StudentUser;
use common\models\entities\User;

/**
 * 重设密码
 */
class PasswordResetForm extends Model {

    public $username;
    public $password;
    public $captcha;

    private $_user;

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
    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios['request'] = ['username', 'captcha'];
        $scenarios['reset'] = ['password'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['password', 'username', 'captcha'], 'required', 'message'=>'{attribute} 不能为空'],
            ['password', 'string', 'min' => 6],
            ['captcha', 'captcha', 'captchaAction' => 'user/captcha', 'message'=>'验证码不正确'],
        ];
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return boolean whether the email was send
     */
    public function requestReset() {

        //根据用户名取得学生用户(优先)和正常用户
        $user = StudentUser::findByUsername($this->username);
        if ($user === null) {
            $user = User::findByUsername($this->username);
        }

        if ($user === null) {
            $this->setErrorMessage('用户不存在');
        } else {
            if($user->isStudent()) {
                if (!StudentUser::isPasswordResetTokenValid($user->password_reset_token)) {
                    $user->generatePasswordResetToken();
                }
            } else {
                if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
                    $user->generatePasswordResetToken();
                }
            }
            if ($user->save()) {
                if(Yii::$app->mailer
                    ->compose(['html' => 'passwordResetToken-html', 'text' => 'passwordResetToken-text'], ['user' => $user])
                    ->setFrom([\Yii::$app->params['adminEmail'] => \Yii::$app->name . ' robot'])
                    ->setTo($user->email)
                    ->setSubject('密码重设 - ' . \Yii::$app->name)
                    ->send()){
                    return true;
                }else{
                    $this->setErrorMessage('发送邮件失败');
                }
            }
        }

        return false;
    }

    /**
     * 重设密码
     *
     * @return boolean if password was reset.
     */
    public function resetPassword()
    {
        $user = $this->_user;
        $user->setPassword($this->password);
        $user->removePasswordResetToken();

        return $user->save(false);
    }

    /**
     * 验证token正确性
     *
     * @return boolean 是否验证成功
     */
    public function validateToken($token) {
        if (empty($token) || !is_string($token)) {
            throw new InvalidParamException('token不能为空');
        }

        $this->_user = StudentUser::findByPasswordResetToken($token);
        if (!$this->_user) {
            $this->_user = User::findByPasswordResetToken($token);
        }
        
        if (!$this->_user) {
            throw new InvalidParamException('错误的token.');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'username' => '用户名',
            'password' => '新密码',
            'captcha' => '验证码',
        ];
    }
}
