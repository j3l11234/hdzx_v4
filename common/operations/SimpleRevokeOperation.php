<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\operations;

use Yii;
use yii\base\Component;
use common\helpers\HdzxException;
use common\helpers\Error;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\User;
use common\services\RoomService;

/**
 * 自动审批撤回 操作
 *
 */
class SimpleRevokeOperation extends BaseOrderOperation {

    protected static $type = OrderOperation::TYPE_SIMPLE_REVOKE;
    protected static $opName = '琴房审批撤回';
    
    /**
     * @inheritdoc
     * 该方法将会检查用户是否拥有审批权限
     */
    protected function checkAuth() {
        if (!$this->user->checkPrivilege(User::PRIV_APPROVE_SIMPLE)) {
            throw new HdzxException('该账号无琴房审批权限', Error::AUTH_FAILED);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {
        if ($this->order->status != Order::STATUS_SIMPLE_APPROVED && $this->order->status != Order::STATUS_SIMPLE_REJECTED){
            throw new HdzxException('申请状态异常', Error::INVALID_ORDER_STATUS);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkRoomTable() {
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
        $this->order->status = Order::STATUS_SIMPLE_PENDING;
    }

}