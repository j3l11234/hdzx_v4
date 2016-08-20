<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\services;

use Yii;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;
use common\models\entities\BaseUser;
use common\models\entities\StudentUser;
use common\models\entities\User;

/**
 * 用户认证类
 *
 * 根据id识别普通用户和学生用户
 */
class UserService implements IdentityInterface {
	protected $user;

	/**
     * Constructor.
     * @param User $user user
     */
    public function __construct($user) {
        $this->user = $user;
    }

    /**
     * 获取对应用户
     * @return User $user
     */
    public function getUser() {
    	return $this->user;
    }

	/**
     * @inheritdoc
     * 根据id去判断对应的用户
     */
    public static function findIdentity($id) {
        $user = static::findUser($id);

    	$identity = $user !== null ? new static($user) : null;
    	return $identity;
    }

    /**
     * @inheritdoc
     * 根据id去判断对应的用户
     * @return User/StuUser $user
     */
    public static function findUser($id, $status = [BaseUser::STATUS_ACTIVE]) {
        if (substr($id, 0, 1) === 'S') {
            return StudentUser::findOne(['id' => $id, 'status' => $status]);
        } else {
            return User::findOne(['id' => $id, 'status' => $status]);
        }
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->user->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->user->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @inheritdoc
     */
    public function __get($name){
        return $this->user->$name;
    }
}