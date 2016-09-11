<?php
namespace backend\models;

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
    public $password_old;
    public $password;
    public $password_repeat;

    private $_user;

    const SCENARIO_RESET      = 'reset';

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
        $scenarios[static::SCENARIO_RESET] = ['password', 'password_old', 'password_repeat'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['password', 'password_old', 'password_repeat'], 'required'],
            ['password', 'string', 'min' => 5, 'max' => 20],
            ['password', 'compare'],
            ['password_old', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->$attribute)) {
                $this->addError($attribute, '旧密码验证错误');
            }
        }
    }

    /**
     *
     * @return boolean whether the email was send
     */
    public function resetPassword() {
        $user = $this->getUser();
        $user->setPassword($this->password);

        if ($user->save(false)) {
            $this->setMessage('密码修改成功');
            return true;
        } else {
            Yii::error('密码修改失败', __METHOD__);
            $this->setMessage('密码修改失败');
            return false;
        }
    }

    /**
     * get che user
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Yii::$app->user->getIdentity()->getUser();
        }

        return $this->_user;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'password_old' => '旧密码',
            'password' => '新密码',
            'password_repeat' => '确认密码',
        ];
    }
}
