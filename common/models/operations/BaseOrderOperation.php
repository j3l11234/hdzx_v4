<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\operations;

use Yii;
use yii\base\Component;
use yii\db\StaleObjectException;
use common\helpers\Error;
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\models\services\RoomService;

/**
 * 预约操作的基类
 * 
 */
class BaseOrderOperation extends Component {
    protected $order;
    protected $user;
    protected $roomTable;
    protected $extra;

    protected static $type = 0;
    protected static $opName = '';

    /**
     * Constructor.
     * @param Order $order 预约
     * @param User $user 用户
     * @param RoomTable $roomTable 房间表
     * @param mixed $extra 额外信息
     */
    public function __construct($order, $user, $roomTable, $extra = null) {
        $this->order = $order;
        $this->user = $user;
        $this->roomTable = $roomTable;
        $this->extra = $extra;
    }

    /**
     * 检查用户权限
     * @throws Exception 如果权限认证失败
     */
    protected function checkAuth() {
    }

    /**
     * 检查前置状态
     * @throws Exception 如果检查失败
     */
    protected function checkPreStatus() {
    }

    /**
     * 设置后置状态
     * @throws Exception 如果出现错误
     */
    protected function setPostStatus() {
    }

    /**
     * 检查时间表
     * @throws OrderOperationException 如果出现错误
     */
    protected function checkRoomTable() {
        $hours = $this->order->hours;

        $locked = $this->roomTable->getLocked($hours);
        if (!empty($locked)) {
            throw new \Exception('该时段已被锁定', Error::ROOMTABLE_LOCKED);
        }

        $used = $this->roomTable->getUsed($hours);
        if (!empty($used)) {
            throw new \Exception('该时段已被占用', Error::ROOMTABLE_USED);
        }
    }

    /**
     * 设置时间表
     * @throws OrderOperationException 如果出现错误
     */
    protected function applyRoomTable() {
        
    }

    /** 
     * 执行操作
     * @throws OrderOperationException 如果出现错误
     */
    public function doOperation(){
        // 检查操作权限
        $this->checkAuth();

        // 检查前置状态
        $this->checkPreStatus();

        // 检查时间表
        $this->checkRoomTable();

        // 写入时间表
        $this->applyRoomTable();
        
        // 设置后置状态
        $this->setPostStatus();

        // 记录操作
        $opData = $this->getOpData();     
        $orderOp = new OrderOperation();
        $orderOp->order_id = $this->order->id;   
        $orderOp->user_id = $this->user->id;
        $orderOp->type = static::$type;
        $orderOp->data = $opData;

        try {
            $this->roomTable->save();
            $this->order->save();
            $orderOp->save();
        } catch (StaleObjectException $e) {
            throw new \Exception('并发访问冲突', Error::COMPET, $e);
        }
    }

     /**
     * 获取操作数据写入操作记录
     * @return mixed 操作数据
     */
    protected function getOpData() {
        $opData = [];
        $opData['operator'] = $this->user->alias;
        $opData['commemt'] = !empty($this->extra['comment']) ? $this->extra['comment'] : static::$opName;
        if ($this->user->isStudent()) {
            $opData['studentn_no'] = $this->user->id;
        }

        return $opData;
    }
}