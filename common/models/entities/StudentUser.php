<?php
namespace common\models\entities;

use Yii;

/**
 * Student User model
 *
 * @property integer $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property json $managers 负责人List
 * @property string $email
 * @property string $alias
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class StudentUser extends BaseUser {
    
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user_stu}}';
    }

    /**
     * @inheritdoc
     */
    public function isStudent() {
        return true;
    }
    
    /**
     * 检查权限
     *
     * @param int $privNum 权限代号
     * @return boolean 是否拥有权限
     */
    public function checkPrivilege($privNum) {
        return false;
    }
}
