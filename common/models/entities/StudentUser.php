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
 * @property integer $dept_id
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
     * @inheritdoc
     */
    public function getLogicId() {
        return 'S'.$this->getPrimaryKey();
    }
    
}
