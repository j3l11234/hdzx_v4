<?php
namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * ErrorBehavior
 * 给第一controller层model扩展
 */
class ErrorBehavior extends Behavior {
    private $errorMessage;
    private $message;

    /**
     * @inheritdoc
     */
    public function events() {
        return [
            ActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
        ];
    }

    /**
     * 完成Validate后的处理函数
     * 如果有Error，将其转成字符串
     *
     * @return null
     */
    public function afterValidate($event) {
        if ($this->owner->hasErrors()) {
            $errorMessage = '';
            $errors = $this->owner->getErrors();
            foreach ($errors as $attr => $messages) {
                //$errorMessage .= $attr.':'.implode(',', $messages).';';
                $errorMessage .= implode(',', $messages).',';
            }
            $this->errorMessage = $errorMessage;
        }
    }

    /**
     * 获取错误信息
     *
     * @return String 错误信息
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * 设置错误信息
     *
     * @param String $errorMessage 错误信息
     * @return null
     */
    public function setErrorMessage($errorMessage) {
        $this->errorMessage = $errorMessage;
    }

     /**
     * 获取信息
     *
     * @return String 错误信息
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * 设置信息
     *
     * @param String $errorMessage 错误信息
     * @return null
     */
    public function setMessage($message) {
        $this->message = $message;
    }
}