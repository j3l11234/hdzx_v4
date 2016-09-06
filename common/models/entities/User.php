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
 * @property json $manage_depts 可负责人审批dept
 * @property integer $privilege 权限表
 * @property integer $status 用户状态
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends BaseUser {


    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user}}';
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
     * @inheritdoc
     */
    public function fields() {
        $fields = parent::fields();
        unset($fields['auth_key'], $fields['password_hash'], $fields['password_reset_token']);
        return $fields;
    }
}
