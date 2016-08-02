<?php

namespace backend\helper;

use Yii;
/**
 * ActiveField 增强扩展
 */
class ActiveField extends \yii\widgets\ActiveField {

    /**
     * @inheritdoc
     */
    public function textarea($options = []) {
        $options = array_merge($this->inputOptions, $options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeTextarea($this->model, $this->attribute, $options);
        return $this;
    }

    public function textInput($options = []) {
        $options = array_merge($this->inputOptions, $options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeTextInput($this->model, $this->attribute, $options);
        return $this;
    }

}
