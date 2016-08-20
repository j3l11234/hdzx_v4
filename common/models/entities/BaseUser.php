<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\entities;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\behaviors\JsonBehavior;

/**
 * 用户基类
 *
 * @property integer $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $alias
 * @property json $managers 负责人List
 * @property integer $privilege
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 */
class BaseUser extends ActiveRecord {

    /**
     * 后台管理权限 房间锁，用户管理，房间管理等
     */
    const PRIV_ADMIN                = 0b0000000001;
    /**
     * 负责人审批权限_按dept_id (审批/驳回/撤销)
     * 只能审批在approve里列举出来的dept_id的order
     */
    const PRIV_APPROVE_MANAGER_DEPT = 0b0000000010; 
    /**
     * 负责人审批权限_全部(审批/驳回/撤销) (覆盖上一条权限)
     */
    const PRIV_APPROVE_MANAGER_ALL   = 0b0000000100;
    /**
     * 校级审批权限(审批/驳回/撤销)
     */
    const PRIV_APPROVE_SCHOOL       = 0b0000001000;
    /**
     * 琴房审批权限(审批/驳回/撤销)
     */
    const PRIV_APPROVE_SIMPLE         = 0b0000010000;
    /**
     * 开门条发放权限
     */
    const PRIV_TYPE_ISSUE           = 0b0000100000;
    /**
     * 琴房申请权限
     */
    const PRIV_ORDER_SIMPLE         = 0b0001000000;
    /**
     * 活动室申请权限
     */
    const PRIV_ORDER_ACTIVITY       = 0b0010000000;


    /**
     * 用户状态 已删除
     */
    const STATUS_DELETED = 00;
    /**
     * 用户状态 启用中
     */
    const STATUS_ACTIVE = 01;
    /**
     * 用户状态 黑名单
     */
    const STATUS_BLOCKED = 02;
    /**
     * 用户状态 未激活
     */
    const STATUS_UNACTIVE = 04;
    /**
     * 用户状态 未验证
     */
    const STATUS_UNVERIFY = 05;

    /**
     * 场景 创建
     */
    const SCENARIO_CREATE       = 'create';
    /**
     * 场景 跟新
     */
    const SCENARIO_UPDATE       = 'update';

    public $password;

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::className(),
            [
                'class' => JsonBehavior::className(),
                'attributes' => ['managers'],
            ],
        ];
    }

    /*
     * @inheritdoc
     */
    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[static::SCENARIO_CREATE] = ['username', 'password', 'status', 'email', 'alias', 'managers', 'privilege', 'status'];
        $scenarios[static::SCENARIO_UPDATE] = ['username', 'password', 'status', 'email', 'alias', 'managers', 'privilege', 'status'];
        return $scenarios;
    }

    

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'username', 'email', 'alias', 'managers', 'status', 'privilege',], 'required'],
            ['password', 'required', 'on' => static::SCENARIO_CREATE],
            ['username', 'string', 'min' => 3, 'max' => 20],
            ['username', 'unique'], 
            ['password', 'string', 'min' => 6, 'max' => 20],
            ['email', 'email'],
            ['alias', 'string', 'min' => 1, 'max' => 20],
            ['status', 'in', 'range' => [static::STATUS_DELETED, static::STATUS_ACTIVE, static::STATUS_BLOCKED, static::STATUS_UNACTIVE, static::STATUS_UNVERIFY]],
            ['privilege', 'number',],
            [['auth_key', 'password_hash', 'password_reset_token',], 'safe'],
        ];
    }

    /**
     * 添加权限
     *
     * @param int $privNum 权限代号
     * @return null
     */
    public function addPrivilege($privNum) {
        $this->privilege ^= $privNum;
    }

    /**
     * 移除权限
     *
     * @param int $privNum 权限代号
     * @return null
     */
    public function removePrivilege($privNum) {
        $this->privilege &= ~$privNum;
    }

    /**
     * 检查权限
     *
     * @param int $privNum 权限代号
     * @return boolean 是否拥有权限
     */
    public function checkPrivilege($privNum) {
        return ($this->privilege & $privNum) == $privNum;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'username' => '用户名',
            'password' => '密码',
            'email' => '邮箱',
            'alias' => '显示名',
            'manager' => '负责人审批者',
            'status' => '状态',
            'privilege' => '权限', 
        ];
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username, $status = self::STATUS_ACTIVE) {
        return static::findOne(['username' => $username, 'status' => $status]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token) {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => [self::STATUS_ACTIVE, self::STATUS_UNVERIFY],
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token) {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey(){
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken(){
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->password_reset_token = null;
    }
}
