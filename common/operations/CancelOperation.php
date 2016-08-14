<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\operations;

use Yii;
use yii\base\Exception;
use common\helpers\Error;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\User;
use common\services\RoomService;

/**
 * 取消预约操作
 * 
 */
class CancelOperation extends BaseOrderOperation {

    protected static $type = OrderOperation::TYPE_CANCEL;
    protected static $opName = '取消预约';

    /**
     * @inheritdoc
     */
    protected function checkAuth() {
        if (!$this->user->checkPrivilege(User::PRIV_ADMIN) &&
            $this->user->id != $this->order->user_id) {
            throw new Exception('该账号无权取消此申请', Error::AUTH_FAILED);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {

    }

    /**
     * @inheritdoc
     */
    protected function applyRoomTable() {
        $order_id = $this->order->id;
        $this->roomTable->removeOrdered($order_id);
        $this->roomTable->removeUsed($order_id);
    }

    /**
     * @inheritdoc
     */
    protected function checkRoomTable() {
        
    }

    /**
     * @inheritdoc
     */
    protected function setPostStatus() {    
        $this->order->status = Order::STATUS_CANCELED;
    }

}