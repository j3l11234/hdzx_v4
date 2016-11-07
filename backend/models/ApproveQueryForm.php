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
     * 查询可以审批的预约
     *
     * @return null
     */
    public function getApproveOrder() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }
        
        $defaultDateRange = RoomService::getSumDateRange();
        $startDateTs = !empty($this->start_date) ? strtotime($this->start_date) : $defaultDateRange['start'];
        $endDateTs = !empty($this->end_date) ? strtotime($this->end_date) : $defaultDateRange['end'];

        if ($endDateTs - $startDateTs > 3 * 31 * 86400) {
            throw new UserException('查询日期间隔不能大于3个月');
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $data = ApproveService::getApproveOrders($user, static::getType($this->type), date('Y-m-d', $startDateTs), date('Y-m-d', $endDateTs), static::getAbsStatus($this->status), $this->room_id, $this->dept_id);
        $orders = $data['orders'];
        $orderList = $data['orderList'];

        //解析roomTable，用于分析冲突
        Yii::beginProfile('分析冲突');
        $conflictOrders = ApproveService::getConflictOrders_batch($orderList, $user, static::getType($this->type));
        foreach ($orders as $order_id => &$order) {
            $order['conflict'] = $conflictOrders[$order_id];
        }
        Yii::endProfile('分析冲突');

        //只显示冲突申请
        if (!empty($this->conflict_id)) {
            $conflict_id = $this->conflict_id;
            $orders_ = [];
            $orderList_ = [];
            if(isset($orders[$conflict_id])) {
                $orders_[$conflict_id] = $orders[$conflict_id];
                $orderList_[] = $conflict_id;
                foreach ($conflictOrders[$conflict_id] as $order_id) {
                    if(!isset($orders[$order_id])) {
                        continue;
                    }
                    $orders_[$order_id] = $orders[$order_id];
                    $orderList_[] = (string)$order_id;
                }
            }
            $orders = $orders_;
            $orderList = $orderList_;
        }

        $data = [
            'orders' => $orders,
            'orderList' => $orderList,
            'start_date' => date('Y-m-d', $startDateTs),
            'end_date' => date('Y-m-d', $endDateTs),
        ];
        return $data;
    }
}
