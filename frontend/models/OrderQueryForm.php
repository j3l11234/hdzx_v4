<?php
namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\base\UserException;

use common\behaviors\ErrorBehavior;
use common\models\entities\User;
use common\models\entities\Order;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\services\RoomService;
use common\services\OrderService;
use common\services\LockService;
use common\helpers\DateRoom;

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
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $now = time();
        $defaultDateRange = RoomService::getSumDateRange(strtotime("+24 hours",$now));
        $startDateTs = !empty($this->start_date) ? strtotime($this->start_date) : $now;
        $endDateTs = !empty($this->end_date) ? strtotime($this->end_date) : $defaultDateRange['end'];
        $room_ids = !empty($this->rooms) ? array_intersect(json_decode($this->rooms, TRUE), RoomService::getRoomList(TRUE)) : RoomService::getRoomList(TRUE);

        if ($endDateTs - $startDateTs > 31 * 86400) {
            throw new UserException('查询日期间隔不能大于1个月');
        }
        
        if ($now - $startDateTs > 10 * 366 * 86400) {
            throw new UserException('查询了太久远的历史');
        }
        if ($endDateTs - $now > 10 * 366 * 86400) {
            throw new UserException('您查询了太遥远的将来');
        }

        $dateRooms = [];
        $dateList = [];
        
        for ($time = $startDateTs; $time <= $endDateTs; $time = strtotime("+1 day", $time)) {
            $date = date('Y-m-d', $time);
            $dateList[] = $date;
            foreach ($room_ids as $room_id) {
                $dateRoom = new DateRoom($date, $room_id);         
                $dateRooms[] = $dateRoom;
            }  
        }

        $roomTables = RoomService::getRoomTables($dateRooms);
        $openPeriods = RoomService::getOpenPeriods($dateRooms);
        foreach ($roomTables as $dateTimeKey => &$roomTable) {
            unset($roomTable['id']);
            if (!$this->rt_detail) {
                unset($roomTable['ordered']);
                unset($roomTable['used']);
                unset($roomTable['rejected']);
                unset($roomTable['locked']);
            }
            $roomTable['period'] = $openPeriods[$dateTimeKey];
        }

        return [
            'dateList' => $dateList,
            'roomList' => $room_ids,
            'roomTables' => $roomTables,
            'serverTime' => microtime(true),
        ];
    }

    /**
     * 取得房间当日占用
     *
     * @return Mixed|null 返回数据
     */
    public function getRoomUse() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $room_ids = RoomService::getRoomList(TRUE);
        if (!in_array($this->room, $room_ids)) {
            throw new UserException('room错误，不存在此房间');
        }

        $dateTs = strtotime($this->date);
        $now = time();
        if ($now - $dateTs > 10 * 366 * 86400) {
            throw new UserException('您查询了太久远的历史');
        }
        if ($dateTs - $now > 10 * 366 * 86400) {
            throw new UserException('您查询了太遥远的将来');
        }

        $dateRoom = new DateRoom($this->date, $this->room);
        $roomTable = RoomService::getRoomTables([$dateRoom])[$dateRoom->key];
        $openPeriod = RoomService::getOpenPeriods([$dateRoom])[$dateRoom->key];
  
        $roomTable['period'] = $openPeriod;

        $ordered_ids = RoomTable::getTable($roomTable['ordered']);
        $used_ids = RoomTable::getTable($roomTable['used']);
        $rejected_ids = RoomTable::getTable($roomTable['rejected']);
        $locked_ids = RoomTable::getTable($roomTable['locked']);

        $orders = OrderService::getOrders(array_merge($ordered_ids, $used_ids, $rejected_ids));
        foreach ($orders as $order_id => &$order) {
            $order = [
                'id' => $order['id'],
                'date' => $order['date'],
                'room_id' => $order['room_id'],
                'dept_name' => $order['dept_name'],
                'room_name' => $order['room_name'],
                'submit_time' => $order['submit_time'],
                'hours' => $order['hours'],
                'status' => $order['status'],
                'student_no' => $order['student_no'],
                'title' => $order['title'],
            ];
        }
        $locks = LockService::getLocks($locked_ids);
        $roomUse = [
            'roomTable' => $roomTable,
            'orders' => $orders,
            'locks' => $locks,
            'serverTime' => microtime(true),
        ];

        return $roomUse;
    }

    /**
     * 查询自己的预约记录
     *
     * @return Mixed|null 返回数据
     */
    public function getMyOrders() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        if (empty($this->start_date)) {
            $this->start_date = date('Y-m-d');
        }

        if (!empty($this->end_date)
            && strtotime($this->end_date) - strtotime($this->start_date) > 6 * 31 * 86400) {
            throw new UserException('查询日期间隔不能大于6个月');
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $data = OrderService::getMyOrders($user, $this->start_date, $this->end_date);

        return array_merge($data, [
        ]);
    }

     /**
     * 查询单个用户的使用情况
     *
     * @return Mixed|null 返回数据
     */
    public function getUsage() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

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
