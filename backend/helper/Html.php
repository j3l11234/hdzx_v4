<?php

namespace backend\helper;

use Yii;

/**
 * Html 增强扩展
 */
class Html extends \yii\helpers\Html {

    /**
     * @inheritdoc
     */
    public static function textarea($name, $value = '', $options = []) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        return parent::textarea($name, $value, $options);
    }
}
