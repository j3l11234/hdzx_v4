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
use common\models\services\RoomService;

/**
 * 取消预约操作
 * 
 */
class CancelOperation extends BaseOrderOperation {

    protected static $type = OrderOperation::TYPE_CANCEL;

    /**
     * 检查用户权限
     * @throws OrderOperationException 如果出现错误
     */
    protected function checkAuth() {
        if ($this->user->getLogicId() != $this->order->user_id) {
            throw new OrderOperationException('该账户不能取消此预约', BaseOrderOperation::ERROR_AUTH_FAILED);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {
        return;
    }

    /**
     * 设置时间表
     * @throws OrderOperationException 如果出现错误
     */
    protected function applyRoomTable() {
        RoomService::applyOrder($this->roomTable, $this->order->id, null, false);
    }

    /**
     * @inheritdoc
     */
    protected function setPostStatus() {    
        $this->order->status = Order::STATUS_CANCELED;
    }

    /**
     * @inheritdoc
     */
    protected function getOpData() {
        $opData = [];
        $opData['operator'] = $this->user->alias;
        if ($this->user->isStudent()) {
            $opData['studentn_no'] = $this->user->id;
        }
        $opData['commemt'] = '取消预约';

        return $opData;
    }
}