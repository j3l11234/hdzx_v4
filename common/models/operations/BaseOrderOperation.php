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
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\models\services\RoomService;

/**
 * 预约操作的基类
 * 
 */
class BaseOrderOperation extends Component {
    /**
     * 错误信息 权限认证失败
     */
    const ERROR_AUTH_FAILED         = 0001;

    /**
     * 错误信息 预约状态异常
     */
    const ERROR_INVALID_ORDER_STATUS    = 0101;
    /**
     * 错误信息 预约类型异常
     */
    const ERROR_INVALID_ORDER_TYPE      = 0102;

    /**
     * 错误信息 时间表已经被占用
     */
    const ERROR_ROOMTABLE_USED      = 0201;
        /**
     * 错误信息 时间表已经被锁定
     */
    const ERROR_ROOMTABLE_LOCKED    = 0201;

    /**
     * 错误信息 时间表写入失败
     */
    const ERROR_SAVE_ROOMTABLE  = 0301;
    /**
     * 错误信息 保存预约异常
     */
    const ERROR_SVAE_ORDER      = 0302;
    /**
     * 错误信息 保存预约操作时异常
     */
    const ERROR_SVAE_ORDEROP    = 0303;

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
     * @throws OrderOperationException 如果出现错误
     */
    protected function checkAuth() {
    }

    /**
     * 检查前置状态
     * @throws OrderOperationException 如果出现错误
     */
    protected function checkPreStatus() {
    }

    /**
     * 设置后置状态
     * @throws OrderOperationException 如果出现错误
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
            throw new OrderOperationException('该时段已被锁定', BaseOrderOperation::ERROR_ROOMTABLE_LOCKED);
        }

        $used = $this->roomTable->getUsed($hours);
        if (!empty($used)) {
            throw new OrderOperationException('该时段已被占用', BaseOrderOperation::ERROR_ROOMTABLE_USED);
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

        if($this->roomTable->save() !== true){
            throw new OrderOperationException('时间表保存错误'."\n".var_export($this->roomTable->getErrors(), true), BaseOrderOperation::ERROR_SVAE_ORDER);
        }

        if($this->order->save() !== true){
            throw new OrderOperationException('预约状态保存错误'."\n".var_export($this->order->getErrors(), true), BaseOrderOperation::ERROR_SVAE_ORDER);
        }
        
        // 记录操作
        $opData = $this->getOpData();     
        $orderOp = new OrderOperation();
        $orderOp->order_id = $this->order->id;   
        $orderOp->user_id = $this->user->id;
        $orderOp->type = static::$type;
        $orderOp->data = $opData;
        if($orderOp->save() !== true){
            throw new OrderOperationException('预约操作记录保存错误'."\n".var_export($orderOp->getErrors(), true), BaseOrderOperation::ERROR_SVAE_ORDEROP);
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