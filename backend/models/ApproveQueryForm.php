<?php
namespace backend\models;

use Yii;
use yii\base\Model;
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
    public $start_date;
    public $end_date;
    public $status;
    public $type;

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
        $scenarios['getApproveOrder'] = ['start_date', 'end_date', 'status', 'type'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['type'], 'required'],
            [['start_date', 'end_date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['type'], 'in', 'range' => ['auto', 'manager', 'school',]],
        ];
    }

    function getType($type) {
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

    /**
     * 查询可以审批的预约
     *
     * @return null
     */
    public function getApproveOrder() {
        $defaultDateRange = RoomService::getSumDateRange();
        $startDateTs = !empty($this->start_date) ? strtotime($this->start_date) : $defaultDateRange['start'];
        $endDateTs = !empty($this->end_date) ? strtotime($this->end_date) : $defaultDateRange['end'];

        if ($endDateTs - $startDateTs > 3 * 31 * 86400) {
            $this->setErrorMessage('查询日期间隔不能大于3个月');
            return FALSE;
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $numType = $this->getType($this->type);

        $data = ApproveService::getApproveOrders($user, $numType, date('Y-m-d', $startDateTs), date('Y-m-d', $endDateTs));
        $orders = $data['orders'];
        $orderList = $data['orderList'];

        //解析roomTable，用于分析冲突
        $dateRooms = [];
        foreach ($orders as $order_id => $order) {
            $dateRooms[] = $order['date'].'_'.$order['room_id'];
        }
        $roomTables = RoomService::getRoomTables($dateRooms);
        foreach ($roomTables as $dateRoom => &$roomTable) {
            unset($roomTable['id']);
            unset($roomTable['locked']);
            unset($roomTable['hourTable']);
            unset($roomTable['chksum']);
        }

        $data = [
            'orders' => $orders,
            'orderList' => $orderList,
            'roomTables' => $roomTables,
            'start_date' => date('Y-m-d', $startDateTs),
            'end_date' => date('Y-m-d', $endDateTs),
        ];
        return $data;
    }
}
