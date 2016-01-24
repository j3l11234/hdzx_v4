<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\exceptions;

use yii\base\Exception;

/**
 * 预约操作出现异常
 */
class OrderOperationException extends Exception {
	
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Order Operation Exception';
    }
}