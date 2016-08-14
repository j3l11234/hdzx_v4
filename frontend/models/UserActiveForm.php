<?php
namespace frontend\models;

use Yii;
use yii\base\InvalidParamException;
use yii\base\Model;
use common\behaviors\ErrorBehavior;
use common\models\entities\StudentUser;

/**
 * UserActive from
 */
class UserActiveForm extends Model {
    public $username;
    public $password;
    public $alias;
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
    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios['request'] = ['username', 'password', 'alias', 'captcha'];
        $scenarios['active'] = [];
        return $scenarios;
    }
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['username', 'password', 'alias', 'captcha'], 'required', 'message'=>'{attribute} 不能为空'],
            [['username', 'password', 'alias'], 'filter', 'filter' => 'trim'],
            ['username', 'match', 'pattern' => '/^\\d{8}$/'],
            ['password', 'string', 'min' => 6],
            ['captcha', 'captcha', 'captchaAction' => 'user/captcha', 'message'=>'验证码不正确'],
        ];
    }

    /**
     * 申请激活
     *
     * @return User|null the saved model or null if saving fails
     */
    public function request() {
        $user = StudentUser::findOne($this->username);
        if(empty($user)){
            $user = new StudentUser();
            $user->id = $this->username;
        } else {
            if ($user->status != StudentUser::STATUS_UNACTIVE) {
                $this->setErrorMessage('用户已经存在并激活');
                return false;
            }
        }

        $user->status = StudentUser::STATUS_UNACTIVE;
        $user->alias = $this->alias;
        $user->username = $this->username;
        $user->email = $this->username.'@bjtu.edu.cn';
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->generatePasswordResetToken();
        if ($user->save()) {
            if(Yii::$app->mailer
                ->compose(['html' => 'userAvtiveToken-html', 'text' => 'userAvtiveToken-text'], ['user' => $user])
                ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->name . ' robot'])
                ->setTo($user->email)
                ->setSubject('用户激活 - ' . \Yii::$app->name)
                ->send()){
                return true;
            }else{
                $this->setErrorMessage('发送邮件失败');
            }
        }
        return false;
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
            throw new InvalidParamException('错误的token.');
        }

        return true;
    }

    /**
     * 激活用户
     *
     * @return boolean 是否激活成功
     */
    public function active() {
        $user = $this->_user;

        $user->status = StudentUser::STATUS_ACTIVE;
        $user->removePasswordResetToken();
        return $user->save();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'username' => '学号',
            'password' => '密码',
            'alias' => '姓名',
            'captcha' => '验证码',
        ];
    }
}
