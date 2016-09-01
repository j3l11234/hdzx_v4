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
    const AUTH_FAILED           = 1;

    /**
     * 预约状态异常
     */
    const INVALID_ORDER_STATUS  = 2;

    /**
     * 时段被占用
     */
    const ROOMTABLE_USED         = 3;

    /**
     * 时段被锁定
     */
    const ROOMTABLE_LOCKED      = 4;

    /**
     * 并发竞争错误
     */
    const COMPET      = 5;

    /**
     * 无效审批类型
     */
    const INVALID_APPROVE_TYPE = 6;

    /**
     * 无效房间类型
     */
    const INVALID_ROOM_TYPE = 7;

    /**
     * 无效申请类型
     */
    const INVALID_ORDER_TYPE = 8;

    /**
     * 保存房间锁失败
     */
    const SAVE_LOCK = 9;
}