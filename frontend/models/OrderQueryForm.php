<?php
namespace frontend\models;

use yii\base\Model;
use Yii;
use common\behaviors\ErrorBehavior;
use common\models\entities\User;
use common\models\entities\Order;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\models\services\RoomService;
use common\models\services\OrderService;
use common\models\services\LockService;

/**
 * Signup form
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
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['date', 'room'], 'required'],
            [['start_date', 'end_date', 'date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['rooms'], 'jsonValidator'],
            [['start_date', 'end_date'], 'dateRangeValidator'],
        ];
    }

    function jsonValidator($attribute, $params) {
        if (!is_array(json_decode($this->$attribute, true))) {
            $this->addError($attribute, $attribute.'格式错误');
        }
    }

    function dateRangeValidator($attribute, $params) {
        $range = static::getDateRange();
        
        $date = strtotime($this->$attribute);   
        if($date < $range['start']  || $date > $range['end']){
            $this->addError($attribute, $attribute.'超出范围，只能查询前后一个月内的记录');
        }
    }

    public static function getDateRange(){
        $today = strtotime(date('Y-m-d', time()));
        $start = strtotime("-31 day",$today);
        $end = strtotime("+31 day",$today);

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    /**
     * 查询房间使用表
     *
     * @return User|null the saved model or null if saving fails
     */
    public function getRoomTables() {
        $roomList = !empty($this->rooms) ? json_decode($this->rooms, true) : [];

        $dateRange = RoomService::queryDateRange();
        $startDate = !empty($this->start_date) ? strtotime($this->start_date) : $dateRange['start'];
        $endDate = !empty($this->end_date) ? strtotime($this->end_date) : $dateRange['end'];

        $rooms = RoomService::queryRoomList()['rooms'];
        //计算hourTables
        $roomTables = [];
        foreach ($rooms as $room_id => $room) {
            if(!in_array($room_id, $roomList) && !empty($this->rooms)){
                continue;
            }

            $roomTables[$room_id] = [];
            for ($time=$startDate; $time <= $endDate; $time = strtotime("+1 day", $time)) {
                $date = date('Y-m-d', $time);
                $roomTable = RoomService::queryRoomTable($date, $room_id);
                if(!$this->rt_detail){
                    unset($roomTable['ordered']);
                    unset($roomTable['used']);
                    unset($roomTable['locked']);
                }
                $roomTables[$room_id][$date] = $roomTable;
            }
        }

        //计算dateList
        $dateList = [];
        for ($time=$startDate; $time <= $endDate; $time = strtotime("+1 day", $time)) {
            $dateList[] = date('Y-m-d', $time);
        }

        return [
            'dateList' => $dateList,
            'roomTables' => $roomTables,
        ];
    }

    /**
     * 取得房间当日占用
     *
     * @return Mixed|null 返回数据
     */
    public function getRoomUse() {
        $data = RoomService::queryRoomTable($this->date, $this->room);

        $ordered = RoomTable::getTable($data['ordered']);
        $used = RoomTable::getTable($data['used']);
        $locked = RoomTable::getTable($data['locked']);

        $data = [
            'roomTable' => $data,
            'orders' => [],
            'locks' => [],
        ];
        foreach ($ordered as $order_id) {
            $order = OrderService::queryOneOrder($order_id);
            unset($order['opList']);
            if ($order !== null) {
                $data['orders'][$order_id] = $order;
            }
        }
        foreach ($used as $order_id) {
            $order = OrderService::queryOneOrder($order_id);
            unset($order['opList']);
            if ($order !== null) {
                $data['orders'][$order_id] = $order;
            }
        }
        foreach ($locked as $lock_id) {
            $lock = LockService::queryOneLock($lock_id);
            if ($lock !== null) {
                $data['locks'][$lock_id] = $lock;
            }
        }
        
        return $data;

    }

    /**
     * 查询自己的预约记录
     *
     * @return Mixed|null 返回数据
     */
    public function getMyOrders() {
        $user = Yii::$app->user->getIdentity()->getUser();

        $now = time();
        if(empty($this->start_date)){
            $this->start_date = date('Y-m-d', strtotime("-31 day", $now));
        }
        if(empty($this->end_date)){
            $this->end_date = date('Y-m-d', strtotime("+31 day", $now));
        }

        $data = OrderService::queryMyOrders($user, $this->start_date, $this->end_date);

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
