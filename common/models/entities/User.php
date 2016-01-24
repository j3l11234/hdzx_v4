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

/**
 * 普通用户
 *
 * @property integer $id
 * @property string $username 用户名
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property integer $dept_id 部门id
 * @property string $email
 * @property string $alias 显示用户名
 * @property string $approve_dept 可以审批的部门
 * @property integer $privilege 权限表
 * @property integer $status 用户状态
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends ActiveRecord {
    /**
     * 用户状态 已删除
     */
    const STATUS_DELETED = 0b000;
    /**
     * 用户状态 启用中
     */
    const STATUS_ACTIVE = 0b010;
    /**
     * 用户状态 黑名单
     */
    const STATUS_BLOCKED = 0b011;

    /**
     * 后台管理权限 房间锁，用户管理，房间管理等
     */
    const PRIV_ADMIN                = 0b0000000001;
    /**
     * 负责人审批权限_按dept_id (审批/驳回/撤销)
     * 只能审批在approve里列举出来的dept_id的order
     */
    const PRIV_APPROVE_MANAGER_DEPT  = 0b0000000010; 
    /**
     * 负责人审批权限_全部(审批/驳回/撤销) (覆盖上一条权限)
     */
    const PRIV_APPROVE_MANAGER_ALL   = 0b0000000100;
    /**
     * 校级审批权限(审批/驳回/撤销)
     */
    const PRIV_APPROVE_SCHOOL       = 0b0000001000;
    /**
     * 自动审批权限(审批/驳回/撤销)
     */
    const PRIV_APPROVE_AUTO         = 0b0000010000;

    //可审批dept_id表
    protected $_approve_dept = [];

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
        return [
            'default' => [],
            'create' => ['username', 'password', 'dept_id', 'email', 'alias', 'approve_dept', 'privilege', 'status'],
            'update' => ['password', 'dept_id', 'email', 'alias', 'approve_dept', 'privilege', 'status']
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED, self::STATUS_BLOCKED]],
        ];
    }

    /**
     * @inheritdoc
     * 
     * json转换
     */
    public function afterFind(){
        $this->_approve_dept = json_decode($this->approve_dept, true);
    }

    /**
     * @inheritdoc
     * 
     * json转换
     */
    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $this->approve_dept = json_encode($this->_approve_dept);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取该账号可审批的dept的id列表
     *
     * @return array 列表
     */
    public function getApproveDeptList() {
        return $this->_approve_dept;
    }

    /**
     * 设置该账号可审批的dept的id列表
     *
     * @return null
     */
    public function setApproveDeptList($_approve_dept) {
        $this->_approve_dept = $_approve_dept;
    }

    /**
     * 检查该用户是否能够审批该dept的order
     *
     * @return boolean 是否可以
     */
    public function checkApproveDept($dept_id) {
        return in_array($dept_id, $this->_approve_dept);
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
     * 该用户是否为学生用户
     *
     * @return boolean
     */
    public function isStudent(){
        return false;
    }

    /**
     * 得到逻辑上的id
     * 如果是学生用户，id前会有"S"
     *
     * @return boolean
     */
    public function getLogicId(){
        return $this->getPrimaryKey();
    }


    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username) {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
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
            'status' => self::STATUS_ACTIVE,
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
     * just for the ActiveForm
     *
     * @return string empty
     */
    public function getPassword() {
        return '';
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => '用户名',
            'password' => '密码',
            'dept_id' => '部门id',
            'email' => 'Email',
            'alias' => '显示用户名',
            'approve_dept' => '可审批部门id',
            'privilege' => '权限',
            'status' => '状态',
        ];
    }
}
