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
     * 获取错误信息
     * 如果有Validate Error，将其转成字符串
     *
     * @return String 错误信息
     */
    public function getErrorMessage() {
        if(empty($this->errorMessage) && $this->owner->hasErrors()) {
            $errorMessage = '';
            $errors = $this->owner->getErrors();
            foreach ($errors as $attr => $messages) {
                //$errorMessage .= $attr.':'.implode(',', $messages).';';
                $errorMessage .= implode(',', $messages).',';
            }
            $this->errorMessage = $errorMessage;
        }
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