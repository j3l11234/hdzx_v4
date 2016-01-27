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
 * 校级审批撤回 操作
 *
 */
class SchoolRevokeOperation extends BaseOrderOperation {

    protected static $type = OrderOperation::TYPE_SCHOOL_REVOKE;

    /**
     * @inheritdoc
     * 该方法将会检查用户是否拥有审批权限
     */
    protected function checkAuth() {
        if (!$this->user->checkPrivilege(User::PRIV_APPROVE_SCHOOL)) {
            throw new OrderOperationException('该账户无校级审批权限', BaseOrderOperation::ERROR_AUTH_FAILED);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {
        if ($this->order->status != Order::STATUS_SCHOOL_ACCEPTED && $this->order->status != Order::STATUS_SCHOOL_REJECTED){
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
        $this->order->status = Order::STATUS_SCHOOL_PENDING;
    }

    /**
     * @inheritdoc
     */
    protected function getOpData() {
        $opData = [];
        $opData['operator'] = $this->user->alias;
        $opData['commemt'] = '校级审批撤回';

        return $opData;
    }
}