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
 * @property string $email
 * @property string $alias 显示用户名
 * @property json $managers 负责人List
 * @property integer $privilege 权限表
 * @property integer $status 用户状态
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends BaseUser{
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
     * 自动审批权限(审批/驳回/撤销)
     */
    const PRIV_APPROVE_AUTO         = 0b0000010000;
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
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user}}';
    }


    /**
     * @inheritdoc
     */
    public function scenarios() {
        return [
            'default' => [],
            'create' => ['username', 'password', 'managers', 'email', 'alias', 'privilege', 'status'],
            'update' => ['password', 'managers', 'email', 'alias', 'privilege', 'status']
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
     * just for the ActiveForm
     *
     * @return string empty
     */
    public function getPassword() {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
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

    /**
     * @inheritdoc
     */
    public function fields() {
        $fields = parent::fields();

        unset($fields['auth_key'], $fields['password_hash'], $fields['password_reset_token']);

        return $fields;
    }
}
