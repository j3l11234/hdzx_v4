<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\operations;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use common\helpers\Error;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\User;
use common\services\RoomService;

/**
 * 校级审批撤回 操作
 *
 */
class SchoolRevokeOperation extends BaseOrderOperation {

    protected static $type = OrderOperation::TYPE_SCHOOL_REVOKE;
    protected static $opName = '校级审批撤回';
    
    /**
     * @inheritdoc
     * 该方法将会检查用户是否拥有审批权限
     */
    protected function checkAuth() {
        if (!$this->user->checkPrivilege(User::PRIV_APPROVE_SCHOOL)) {
            throw new Exception('该账号无校级审批权限', Error::AUTH_FAILED);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {
        if ($this->order->status != Order::STATUS_SCHOOL_APPROVED && $this->order->status != Order::STATUS_SCHOOL_REJECTED){
            throw new Exception('申请状态异常', Error::INVALID_ORDER_STATUS);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkRoomTable() {
        return;
    }

    /**
     * @inheritdoc
     */
    protected function applyRoomTable() {
        $hours = $this->order->hours;
        $order_id = $this->order->id;
        $this->roomTable->removeUsed($order_id);
        $this->roomTable->addOrdered($order_id, $hours);
    }

    /**
     * @inheritdoc
     */
    protected function setPostStatus() {
        $this->order->status = Order::STATUS_SCHOOL_PENDING;
    }
    
}