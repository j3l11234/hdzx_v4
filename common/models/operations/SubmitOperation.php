<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\operations;

use Yii;
use yii\base\Component;
use common\helpers\Error;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\services\RoomService;

/**
 * 提交操作
 * 
 * ```php
 * $extra = [
 *     'roomtable' => RoomTable, //RoomTable
 * ]
 * new SubmitOperation($order, $user, $extra);
 * ``` 
 */
class SubmitOperation extends BaseOrderOperation {

    protected static $type = OrderOperation::TYPE_SUBMIT;
    protected static $opName = '提交预约';

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {
        if ($this->order->status != Order::STATUS_INIT){
            throw new OrderOperationException('预约状态异常', Error::INVALID_ORDER_STATUS);
        }
    }

    /**
     * 设置时间表
     * @throws OrderOperationException 如果出现错误
     */
    protected function applyRoomTable() {
        $hours = $this->order->hours;
        $order_id = $this->order->id;
        $this->roomTable->addOrdered($order_id, $hours);
    }

    /**
     * @inheritdoc
     */
    protected function setPostStatus() {
        $this->order->submit_time = time();
        if($this->order->type == Order::TYPE_SIMPLE){ //自动审批
            $this->order->status = Order::STATUS_SIMPLE_PENDING;
        } else if($this->order->type == Order::TYPE_TWICE) { //二级审批
            $this->order->status = Order::STATUS_MANAGER_PENDING;
        } else {
            throw new OrderOperationException('预约类型异常', static::ERROR_INVALID_TYPE);
        }
    }

}