<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\operations;

use Yii;
use yii\base\Component;
use common\exceptions\OrderOperationException;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\User;
use common\models\services\RoomService;

/**
 * 自动审批撤回 操作
 *
 */
class AutoRevokeOperation extends BaseOrderOperation {

    protected static $type = OrderOperation::TYPE_AUTO_REVOKE;
    protected static $opName = '自动审批撤回';
    
    /**
     * @inheritdoc
     * 该方法将会检查用户是否拥有审批权限
     */
    protected function checkAuth() {
        if (!$this->user->checkPrivilege(User::PRIV_APPROVE_AUTO)) {
            throw new OrderOperationException('该账户无自动审批权限', BaseOrderOperation::ERROR_AUTH_FAILED);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {
        if ($this->order->status != Order::STATUS_AUTO_APPROVED && $this->order->status != Order::STATUS_AUTO_REJECTED){
            throw new OrderOperationException('预约状态异常', BaseOrderOperation::ERROR_INVALID_ORDER_STATUS);
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
        $hours = $this->order->getHours();
        RoomService::applyOrder($this->roomTable, $this->order->id, $hours, false);
    }

    /**
     * @inheritdoc
     */
    protected function setPostStatus() {
        $this->order->status = Order::STATUS_AUTO_PENDING;
    }

}