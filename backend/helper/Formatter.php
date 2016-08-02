<?php

namespace backend\helper;

use Yii;

/**
 * Formatter 增强扩展
 */
class Formatter extends \yii\i18n\Formatter {
    /**
     * Formats the json value as an HTML-encoded json text.
     * @param string $value the value to be formatted.
     * @return string the formatted result.
     */
    public function asJson($value) {
        if ($value === null) {
            return $this->nullDisplay;
        }
        return json_encode($value);
    }
}
