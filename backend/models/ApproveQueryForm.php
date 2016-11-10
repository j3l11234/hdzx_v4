<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use yii\base\UserException;

use common\behaviors\ErrorBehavior;
use common\models\entities\User;
use common\models\entities\Order;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\services\RoomService;
use common\services\ApproveService;
use common\services\OrderService;

/**
 * ApproveQuery form
 */
class ApproveQueryForm extends Model {
    public $type;
    public $start_date;
    public $end_date;
    public $status;
    public $room_id;
    public $dept_id;
    public $conflict_id;
    public $per_page;
    public $cur_page;
    

    const SCENARIO_GET_APPROVE_ORDER    = 'getApproveOrders';
    const SCENARIO_GET_CONFLICT_ORDER   = 'getConflictOrders';

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            ErrorBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[static::SCENARIO_GET_APPROVE_ORDER] = ['type', 'start_date', 'end_date', 'status', 'room_id', 'dept_id', 'conflict_id', 'per_page', 'cur_page'];
        $scenarios[static::SCENARIO_GET_CONFLICT_ORDER] = ['type', 'conflict_id', 'per_page', 'cur_page'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['type'], 'required'],
            [['type'], 'in', 'range' => ['auto', 'manager', 'school',]],
            [['status'], 'in', 'range' => ['pending', 'approved', 'rejected',]],
            [['start_date', 'end_date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['conflict_id'], 'required', 'on' => [static::SCENARIO_GET_CONFLICT_ORDER]],
            [['per_page', 'cur_page'], 'number'],
        ];
    }

    public static function getType($type) {
        switch ($type) {
            case 'auto':
                return ApproveService::TYPE_SIMPLE;
            case 'manager':
                return ApproveService::TYPE_MANAGER;
            case 'school':
                return ApproveService::TYPE_SCHOOL;
            default:
                break;
        }
    }

    public static function getAbsStatus($status) {
        switch ($status) {
            case 'pending':
                return ApproveService::STATUS_ABS_PENDING;
            case 'approved':
                return ApproveService::STATUS_ABS_APPROVED;
            case 'rejected':
                return ApproveService::STATUS_ABS_REJECTED;
            default:
                break;
        }
    }

    /**
     * 查询可以审批的申请
     *
     * @return null
     */
    public function getApproveOrders() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }
        
        if (empty($this->start_date)) {
            $this->start_date = date('Y-m-d');
        }

        if (!empty($this->end_date)
            && strtotime($this->end_date) - strtotime($this->start_date) > 3 * 31 * 86400) {
            throw new UserException('查询日期间隔不能大于3个月');
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $term = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'abs_status' => static::getAbsStatus($this->status),
            'room_id' => $this->room_id,
            'dept_id' => $this->dept_id,
        ];
        $data = ApproveService::getApproveOrders($user, static::getType($this->type), $term);
        $orders = $data['orders'];
        $orderList = $data['orderList'];

        //如果涉及了dept_id筛选，则需去掉此条件在搜索一次，用于分析冲突
        if (!empty($this->dept_id)) {
            unset($term['dept_id']);
            $orderList_all = ApproveService::getApproveOrders($user, static::getType($this->type), $term, TRUE);
        } else {
            $orderList_all = $orderList;
        }

        //分析冲突
        Yii::beginProfile('分析冲突');  
        $conflictOrders = ApproveService::getConflictOrders_batch($orders, $orderList_all  );
        foreach ($orders as $order_id => &$order) {
            $order['conflict'] = $conflictOrders[$order_id];
        }
        unset($order);
        Yii::endProfile('分析冲突');

        $data = [
            'orders' => $orders,
            'orderList' => $orderList,
        ];
        return $data;
    }

    /**
     * 查询冲突申请
     *
     * @return null
     */
    public function getConflictOrders() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }
        
        $order = OrderService::getOrder($this->conflict_id);
        if(empty($order)){
            throw new UserException('申请不存在');
        }
        
        //查找所有相关申请，用于分析冲突
        $user = Yii::$app->user->getIdentity()->getUser();
        $term = [
            'start_date' => $order['date'],
            'end_date' => $order['date'],
            'abs_status' => ApproveService::STATUS_ABS_PENDING,
            'room_id' => $order['room_id'],
        ];
        $order_ids = ApproveService::getApproveOrders($user, static::getType($this->type), $term, TRUE);

        //获取相关申请的详细信息
        $orders = OrderService::getOrders($order_ids);
        if (in_array($this->conflict_id, $order_ids) && $order_ids[0] != $this->conflict_id){
            array_unshift($order_ids, $this->conflict_id);
            $order_ids = array_values(array_unique($order_ids));
        }     

        //分析冲突
        Yii::beginProfile('分析冲突');  
        $conflictOrders = ApproveService::getConflictOrders_batch($orders, $order_ids);
        foreach ($orders as $order_id => &$order) {
            if (isset($conflictOrders[$order_id])){
                $order['conflict'] = $conflictOrders[$order_id];
            } 
        }
        Yii::endProfile('分析冲突');

        $data = [
            'orders' => $orders,
            'orderList' => $order_ids,
        ];
        return $data;
    }
}
