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
class ApproveException extends Exception {
	 /**
     * 错误信息 权限认证失败
     */
    const AUTH_FAILED         = 0x0001;

    /**
     * 错误信息 权限认证失败
     */
    const TYPE_NOT_FOUND       = 0x0002;

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Approve Exception';
    }
}