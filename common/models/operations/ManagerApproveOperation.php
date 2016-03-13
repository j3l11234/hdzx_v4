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
 * 负责人审批通过 操作
 *
 */
class ManagerApproveOperation extends BaseOrderOperation {

    protected static $type = OrderOperation::TYPE_MANAGER_APPROVE;
    protected static $opName = '负责人审批通过';
    
    /**
     * @inheritdoc
     * 该方法将会检查用户是否拥有审批权限
     */
    protected function checkAuth() {
        if (!$this->user->checkPrivilege(User::PRIV_APPROVE_MANAGER_ALL)) {
            if(!$this->user->checkPrivilege(User::PRIV_APPROVE_MANAGER_DEPT)){
                throw new OrderOperationException('该账户无负责人审批权限', BaseOrderOperation::ERROR_AUTH_FAILED);
            }else{
                if(!$this->user->checkApproveDept($this->order->dept_id)){
                    throw new OrderOperationException('该账户对该预约无负责人审批权限', BaseOrderOperation::ERROR_AUTH_FAILED);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {
        if ($this->order->status != Order::STATUS_MANAGER_PENDING){
            throw new OrderOperationException('预约状态异常', BaseOrderOperation::ERROR_INVALID_ORDER_STATUS);
        }
    }

    protected function applyRoomTable() {
        $hours = $this->order->getHours();
        RoomService::applyOrder($this->roomTable, $this->order->id, $hours, false);
    }

    /**
     * @inheritdoc
     */
    protected function setPostStatus() {
        $this->order->status = Order::STATUS_MANAGER_APPROVED;
    }

}