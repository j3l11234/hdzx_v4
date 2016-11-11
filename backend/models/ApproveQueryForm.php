<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use yii\base\UserException;
use yii\data\Pagination;

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
    public $per_page = 8;
    public $cur_page = 1;
    

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
            [['type'], 'in', 'range' => [ApproveService::TYPE_SIMPLE, ApproveService::TYPE_MANAGER, ApproveService::TYPE_SCHOOL]],
            [['status'], 'in', 'range' => [ApproveService::STATUS_ABS_PENDING, ApproveService::STATUS_ABS_APPROVED, ApproveService::STATUS_ABS_REJECTED]],
            [['start_date', 'end_date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['conflict_id'], 'required', 'on' => [static::SCENARIO_GET_CONFLICT_ORDER]],
            [['per_page', 'cur_page'], 'number'],
        ];
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
            'abs_status' => $this->status,
            'room_id' => $this->room_id,
            'dept_id' => $this->dept_id,
        ];
        $order_ids = ApproveService::getApproveOrders($user, $this->type, $term);

        //分页处理
        $pagination = new Pagination(['totalCount' => count($order_ids)]);
        $pagination->setPageSize($this->per_page, true);
        $pagination->setPage($this->cur_page-1, true);
        $order_ids = array_slice($order_ids, $pagination->getOffset(), $pagination->getLimit());
        $orders = OrderService::getOrders($order_ids);

        //解析order_info用于分析冲突
        $ordersInfos = [];
        foreach ($orders as $order) {
            $ordersInfos[$order['id']] = [
                'id' => $order['id'],
                'date' => $order['date'],
                'room_id' => $order['room_id'],
                'hours' => $order['hours'],
            ];
        }

        //如果涉及了dept_id和status筛选，则需去掉此条件在搜索一次，用于分析冲突
        if (!empty($this->dept_id) || !empty($this->status)) {
            $term = [
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'room_id' => $this->room_id,
            ];
            $order_ids_all = ApproveService::getApproveOrders($user, $this->type, $term);
        } else {
            $order_ids_all = $order_ids;
        }

        //分析冲突
        Yii::beginProfile('分析冲突');  
        $conflictOrders_map = ApproveService::getConflictOrders_batch($ordersInfos, $order_ids_all);
        foreach ($orders as &$order) {
            $conflictOrders = $conflictOrders_map[$order['id']];
            $order['conflict'] = [
                'ordered' => !empty($conflictOrders['ordered']),
                'used' => !empty($conflictOrders['used']),
                'rejected' => !empty($conflictOrders['rejected'])
            ];
        }
        unset($order);
        Yii::endProfile('分析冲突');

        $data = [
            'orders' => $orders,
            'orderList' => $order_ids,
            '_page' => [
                'per_page' => $pagination->getPageSize(),
                'cur_page' => $pagination->getPage()+1,
                'total_page' => $pagination->getPageCount(),
                'total' => $pagination->totalCount,
            ]
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
        
        //解析order_info用于分析冲突
        $ordersInfo = [
            'id' => $order['id'],
            'date' => $order['date'],
            'room_id' => $order['room_id'],
            'hours' => $order['hours'],
        ];

        //查找所有相关申请，用于分析冲突
        $user = Yii::$app->user->getIdentity()->getUser();
        $term = [
            'start_date' => $order['date'],
            'end_date' => $order['date'],
            'room_id' => $order['room_id'],
        ];
        $order_ids_all = ApproveService::getApproveOrders($user, $this->type, $term);

        //分析冲突
        Yii::beginProfile('分析冲突');  
        $conflictOrders = ApproveService::getConflictOrders($ordersInfo, $order_ids_all);
        $order_ids = array_merge([$this->conflict_id], $conflictOrders['ordered'], $conflictOrders['used'], $conflictOrders['rejected']);
        Yii::endProfile('分析冲突');

        //分页处理
        $pagination = new Pagination(['totalCount' => count($order_ids)]);
        $pagination->setPageSize($this->per_page, true);
        $pagination->setPage($this->cur_page-1, true);
        $order_ids = array_slice($order_ids, $pagination->getOffset(), $pagination->getLimit());
        $orders = OrderService::getOrders($order_ids);

        $orders[$this->conflict_id]['conflict'] = [
            'ordered' => !empty($conflictOrders['ordered']),
            'used' => !empty($conflictOrders['used']),
            'rejected' => !empty($conflictOrders['rejected'])
        ];

        $data = [
            'orders' => $orders,
            'orderList' => $order_ids,
            '_page' => [
                'per_page' => $pagination->getPageSize(),
                'cur_page' => $pagination->getPage()+1,
                'total_page' => $pagination->getPageCount(),
                'total' => $pagination->totalCount,
            ]
        ];
        return $data;
    }
}
