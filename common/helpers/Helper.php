<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\helpers;

use Yii;
use yii\bootstrap\Alert;

/**
 * 预约操作出现异常
 */
class Helper {
    public static function renderFlash() {
        if( Yii::$app->getSession()->hasFlash('success') ) {
            return Alert::widget([
                'options' => [
                    'class' => 'alert-success',
                ],
                'body' => Yii::$app->getSession()->getFlash('success'),
            ]);
        }
        if( Yii::$app->getSession()->hasFlash('error') ) {
            return Alert::widget([
                'options' => [
                    'class' => 'alert-danger',
                ],
                'body' => Yii::$app->getSession()->getFlash('error'),
            ]);
        }
        return '';
    }
    
}