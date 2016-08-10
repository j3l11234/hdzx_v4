<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\helpers;

/**
 * 预约操作出现异常
 */
class Error {

    /**
     * 权限认证失败
     */
    const AUTH_FAILED           = 0001;

    /**
     * 预约状态异常
     */
    const INVALID_ORDER_STATUS  = 0002;

    /**
     * 时段被占用
     */
    const ROOMTABLE_USED         = 0003;

    /**
     * 时段被锁定
     */
    const ROOMTABLE_LOCKED      = 0004;

    /**
     * 并发竞争错误
     */
    const COMPET      = 0005;

}