<?php
namespace frontend\models;

use Yii;
use yii\base\InvalidParamException;
use yii\base\Model;
use common\behaviors\ErrorBehavior;
use common\models\entities\BaseUser;
use common\models\entities\StudentUser;
use common\models\entities\User;

/**
 * UserActive from
 */
class UserActiveForm extends Model {
    public $username;
    public $password;
    public $email;
    public $alias;
    public $manager;
    public $captcha;

    private $_user;

    const SCENARIO_STU_REQUEST      = 'stuRequest';
    const SCENARIO_STU_VERIFY       = 'stuVerify';
    const SCENARIO_DEPT_REQUEST     = 'deptRequest';
    const SCENARIO_DEPT_VERIFY      = 'deptVerify';
    const SCENARIO_DEPT_ACTIVE      = 'deptActive';

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
        $scenarios[static::SCENARIO_STU_REQUEST] = ['username', 'password', 'alias', 'captcha'];
        $scenarios[static::SCENARIO_STU_VERIFY] = [''];
        $scenarios[static::SCENARIO_DEPT_REQUEST] = ['username', 'password', 'email', 'alias', 'captcha'];
        $scenarios[static::SCENARIO_DEPT_VERIFY] = [''];
        $scenarios[static::SCENARIO_DEPT_ACTIVE] = [''];
        return $scenarios;
    }
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['username', 'password', 'alias', 'captcha'], 'required', 'message'=>'{attribute} 不能为空'],
            [['username', 'password', 'alias'], 'filter', 'filter' => 'trim'],
            ['username', 'match', 'pattern' => '/^\\d{8}$/', 'on' => self::SCENARIO_STU_REQUEST],
            ['username', 'match', 'pattern' => '/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u', 'message'=>'用户名由汉字、英文字母、数字及下划线组成，长度在3-15之间', 'on' => self::SCENARIO_DEPT_REQUEST],
            ['email', 'match', 'pattern' => '/^\\d{8}@bjtu\\.edu\\.cn$/', 'message'=>'请输入学号邮箱', 'on' => self::SCENARIO_DEPT_REQUEST],
            ['password', 'string', 'min' => 6],
            ['captcha', 'captcha', 'captchaAction' => 'user/captcha', 'message'=>'验证码不正确'],
        ];
    }

    /**
     * 申请激活学生用户
     *
     * @return User|null the saved model or null if saving fails
     */
    public function stuRequest() {
        $user = StudentUser::findByUsername($this->username, [BaseUser::STATUS_ACTIVE, BaseUser::STATUS_BLOCKED, BaseUser::STATUS_UNACTIVE, BaseUser::STATUS_UNVERIFY]);
        if(empty($user)){
            $user = new StudentUser();
            $user->id = 'S'.$this->username;
        } else {
            if ($user->status != BaseUser::STATUS_UNVERIFY) {
                $this->setErrorMessage('用户已存在');
                return false;
            }
        }
        $user->username = $this->username;
        $user->setPassword($this->password);
        $user->email = $this->username.'@bjtu.edu.cn';
        $user->alias = $this->alias;
        $user->status = BaseUser::STATUS_UNVERIFY;
        $user->privilege = BaseUser::PRIV_ORDER_SIMPLE | BaseUser::PRIV_ORDER_ACTIVITY;
        
        $user->generateAuthKey();
        $user->generatePasswordResetToken();
        if ($user->save()) {
            if(Yii::$app->mailer
                ->compose(['html' => 'userAvtiveToken-html', 'text' => 'userAvtiveToken-text'], ['user' => $user, 'type' => 'student'])
                ->setFrom([\Yii::$app->params['adminEmail'] => \Yii::$app->name . ' robot'])
                ->setTo($user->email)
                ->setSubject('账号验证 - ' . \Yii::$app->name)
                ->send()){
                $this->setMessage('发送邮件成功，请查收邮件并根据提示操作');
                return true;
            }else{
                $this->setErrorMessage('发送邮件失败');
                Yii::error('发送邮件失败', __METHOD__);
            }
        } else {
            Yii::error($user->getErrors(), __METHOD__);
        }
        return false;
    }

    /**
     * 申请激活社团用户
     *
     * @return User|null the saved model or null if saving fails
     */
    public function deptRequest() {
        $user = User::findByUsername($this->username, [BaseUser::STATUS_ACTIVE, BaseUser::STATUS_BLOCKED, BaseUser::STATUS_UNACTIVE, BaseUser::STATUS_UNVERIFY]);
        if(empty($user)){
            $user = new User();
        } else {
            if ($user->status != User::STATUS_UNVERIFY) {
                $this->setErrorMessage('用户已经存在');
                return false;
            }
        }

        $user->username = $this->username;
        $user->setPassword($this->password);
        $user->email = $this->email;
        $user->alias = $this->alias;
        $user->managers = [1];
        $user->status = BaseUser::STATUS_UNVERIFY;
        $user->privilege = BaseUser::PRIV_ORDER_ACTIVITY;
        $user->generateAuthKey();
        $user->generatePasswordResetToken();
        if ($user->save()) {
            if(Yii::$app->mailer
                ->compose(['html' => 'userAvtiveToken-html', 'text' => 'userAvtiveToken-text'], ['user' => $user, 'type' => 'dept'])
                ->setFrom([\Yii::$app->params['adminEmail'] => \Yii::$app->name . ' robot'])
                ->setTo($user->email)
                ->setSubject('账号验证 - ' . \Yii::$app->name)
                ->send()){
                $this->setMessage('发送邮件成功，请查收邮件并根据提示操作');
                return true;
            }else{
                Yii::error('发送邮件失败', __METHOD__);
            }
        } else {
            Yii::error($user->getErrors(), __METHOD__);
        }
        return false;
    }


    /**
     * 激活学生用户
     *
     * @return boolean 成功
     */
    public function verify() {
        $user = $this->_user;
        switch ($this->scenario) {
            case static::SCENARIO_STU_VERIFY:
                $user->status = BaseUser::STATUS_ACTIVE;
                $message = '邮箱验证成功，该账号已经激活，您现在可以使用该账号登录了！';
                break;
            case static::SCENARIO_DEPT_VERIFY:
                $user->status = User::STATUS_UNACTIVE;
                $message = '邮箱验证成功，该账号等待管理员进一步审批';
                break;
            default:
                throw new InvalidParamException('无效的链接');
                break;
        }

        $user->removePasswordResetToken();
        if ($user->save()) {
            $this->setMessage($message);
            return true;
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

        switch ($this->scenario) {
            case static::SCENARIO_STU_VERIFY:
                $this->_user = StudentUser::findByPasswordResetToken($token);
                break;
            case static::SCENARIO_DEPT_VERIFY:
                $this->_user = User::findByPasswordResetToken($token);
                break;
            default:
                throw new InvalidParamException('无效的链接');
                break;
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
            'password' => '密码',
            'email' => '邮箱',
            'manager' => '审批者',
            'alias' => '姓名',
            'captcha' => '验证码',
        ];
    }
}
