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
use common\models\entities\BaseUser;
use common\models\entities\Room;
use common\models\entities\OrderOperation;
use common\services\RoomService;

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
    protected static $opName = '提交申请';

    protected $room;
    
    /**
     * @inheritdoc
     */
    public function __construct($order, $user, $roomTable, $extra = null) {
        parent::__construct($order, $user, $roomTable, $extra);
        $this->room = Room::findOne($order->room_id);
    }

    /**
     * @inheritdoc
     */
    protected function checkAuth() {
        if ($this->room->type == Room::TYPE_SIMPLE) { //琴房申请
            if (!$this->user->checkPrivilege(BaseUser::PRIV_ORDER_SIMPLE)) {
                throw new HdzxException('该账号无琴房申请权限', Error::AUTH_FAILED);
            }
        } else if($this->room->type == Room::TYPE_ACTIVITY) { //活动室申请
            if (!$this->user->checkPrivilege(BaseUser::PRIV_ORDER_ACTIVITY)) {
                throw new HdzxException('该账号无活动室申请权限', Error::AUTH_FAILED);
            }
        } else {
            throw new HdzxException('房间类型异常', static::INVALID_ROOM_TYPE);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {
        if ($this->order->status != Order::STATUS_INIT){
            throw new HdzxException('申请状态异常', Error::INVALID_ORDER_STATUS);
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
        if($this->order->type == Order::TYPE_SIMPLE) { //琴房申请
            $this->order->status = Order::STATUS_SIMPLE_PENDING;
        } else if($this->order->type == Order::TYPE_TWICE) { //二级审批
            $this->order->status = Order::STATUS_MANAGER_PENDING;
        } else {
            throw new HdzxException('申请类型异常', static::ERROR_INVALID_TYPE);
        }
    }

}