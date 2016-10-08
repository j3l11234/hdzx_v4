<?php
namespace frontend\models;

use Yii;
use yii\base\Model;
use common\behaviors\ErrorBehavior;
use common\models\entities\User;
use common\models\entities\Order;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\services\RoomService;
use common\services\OrderService;
use common\services\LockService;

/**
 * OrderQuery form
 */
class OrderQueryForm extends Model {
    public $start_date;
    public $end_date;
    public $rooms;
    public $rt_detail = false;
    public $date;
    public $room;

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
    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios['getRoomTables'] = ['start_date', 'end_date', 'rooms', 'rt_detail'];
        $scenarios['getRoomUse'] = ['date', 'room'];
        $scenarios['getMyOrders'] = ['start_date', 'end_date'];
        $scenarios['getUsage'] = ['date'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['room'], 'required'],
            [['date'], 'required', 'on' => 'getRoomUse'],
            [['start_date', 'end_date', 'date'], 'date', 'format'=>'yyyy-MM-dd', 'message' => '{attribute}的格式无效'],
            [['rooms'], 'jsonValidator'],
        ];
    }

    function jsonValidator($attribute, $params) {
        if (!is_array(json_decode($this->$attribute, true))) {
            $this->addError($attribute, $attribute.'格式错误');
        }
    }

    
    /**
     * 查询房间使用表
     *
     * @return User|null the saved model or null if saving fails
     */
    public function getRoomTables() {
        $defaultDateRange = RoomService::getSumDateRange();
        $startDateTs = !empty($this->start_date) ? strtotime($this->start_date) : $defaultDateRange['start'];
        $endDateTs = !empty($this->end_date) ? strtotime($this->end_date) : $defaultDateRange['end'];
        $room_ids = !empty($this->rooms) ? array_intersect(json_decode($this->rooms, TRUE), RoomService::getRoomList(TRUE)) : RoomService::getRoomList(TRUE);

        if ($endDateTs - $startDateTs > 31 * 86400) {
            $this->setErrorMessage('查询日期间隔不能大于1个月');
            return FALSE;
        }
        $now = time();
        if ($now - $startDateTs > 10 * 366 * 86400) {
            $this->setErrorMessage('您查询了太久远的历史');
            return FALSE;
        }
        if ($endDateTs - $now > 10 * 366 * 86400) {
            $this->setErrorMessage('您查询了太遥远的将来');
            return FALSE;
        }

        $dateRooms = [];
        $avails = [];
        $dateList = [];
        $dateRanges = RoomService::getDateRanges($room_ids);
        for ($time = $startDateTs; $time <= $endDateTs; $time = strtotime("+1 day", $time)) {
            $date = date('Y-m-d', $time);
            $dateList[] = $date;
            foreach ($room_ids as $room_id) {
                $dateRoom = $date.'_'.$room_id;
                $dateRange = $dateRanges[$room_id];
                $dateRooms[] = $dateRoom;
                $avails[$dateRoom] = $time >= $dateRange['start'] && $time <= $dateRange['end'];
            }  
        }

        $roomTables = RoomService::getRoomTables($dateRooms);
        foreach ($roomTables as $dateTime => &$roomTable) {
            unset($roomTable['id']);
            if (!$this->rt_detail) {
                unset($roomTable['ordered']);
                unset($roomTable['used']);
                unset($roomTable['locked']);
            }
            $roomTable['available'] = $avails[$dateTime];
        }

        return [
            'dateList' => $dateList,
            'roomList' => $room_ids,
            'roomTables' => $roomTables,
            'start_date' => date('Y-m-d', $startDateTs),
            'end_date' => date('Y-m-d', $endDateTs),
        ];
    }

    /**
     * 取得房间当日占用
     *
     * @return Mixed|null 返回数据
     */
    public function getRoomUse() {
        $room_ids = RoomService::getRoomList(TRUE);
        if (!in_array($this->room, $room_ids)) {
            $this->setErrorMessage('room错误，不存在此房间');
            return FALSE;
        }

        $dateTs = strtotime($this->date);
        $now = time();
        if ($now - $dateTs > 10 * 366 * 86400) {
            $this->setErrorMessage('您查询了太久远的历史');
            return FALSE;
        }
        if ($dateTs - $now > 10 * 366 * 86400) {
            $this->setErrorMessage('您查询了太遥远的将来');
            return FALSE;
        }

        $dateRoom = $this->date.'_'.$this->room;
        $roomTable = RoomService::getRoomTables([$dateRoom])[$dateRoom];
        $dateRange = RoomService::getDateRanges([$this->room])[$this->room];
  
        $roomTable['available'] = $dateTs >= $dateRange['start'] && $dateTs <= $dateRange['end'];
        $ordered_ids = RoomTable::getTable($roomTable['ordered']);
        $used_ids = RoomTable::getTable($roomTable['used']);
        $locked_ids = RoomTable::getTable($roomTable['locked']);

        $orders = OrderService::getOrders(array_merge($ordered_ids, $used_ids));
        foreach ($orders as $order_id => &$order) {
            unset($order['opList']);
        }
        $locks = LockService::getLocks($locked_ids);
        $roomUse = [
            'roomTable' => $roomTable,
            'orders' => $orders,
            'locks' => $locks,
        ];

        return $roomUse;
    }

    /**
     * 查询自己的预约记录
     *
     * @return Mixed|null 返回数据
     */
    public function getMyOrders() {
        $defaultDateRange = RoomService::getSumDateRange();
        $startDateTs = !empty($this->start_date) ? strtotime($this->start_date) : $defaultDateRange['start'];
        $endDateTs = !empty($this->end_date) ? strtotime($this->end_date) : $defaultDateRange['end'];

        if ($endDateTs - $startDateTs > 31*6 * 86400) {
            $this->setErrorMessage('查询日期间隔不能大于6个月');
            return false;
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $data = OrderService::getMyOrders($user, date('Y-m-d', $startDateTs), date('Y-m-d', $endDateTs));

        return array_merge($data, [
            'start_date' => date('Y-m-d', $startDateTs),
            'end_date' => date('Y-m-d', $endDateTs),
        ]);
    }

     /**
     * 查询单个用户的使用情况
     *
     * @return Mixed|null 返回数据
     */
    public function getUsage() {

        if(empty($this->date)){
            $time = time();
        } else {
            $time = strtotime($this->date);
        }
        $user = Yii::$app->user->getIdentity()->getUser();
        $data = OrderService::queryUsage($user, $time);

        return $data;
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'start_date' => '开始日期',
            'end_date' => '结束日期',
        ];
    }
}
