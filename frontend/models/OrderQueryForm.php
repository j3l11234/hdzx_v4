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
            [['start_date', 'end_date', 'date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['rooms'], 'jsonValidator'],
        ];
    }

    function jsonValidator($attribute, $params) {
        if (!is_array(json_decode($this->$attribute, true))) {
            $this->addError($attribute, $attribute.'格式错误');
        }
    }

    function dateRangeValidator($attribute, $params) {
        $range = static::getDefaultDateRange();      
        $date = strtotime($this->$attribute);   


        if($date < $range['start']  || $date > $range['end']){
            $this->addError($attribute, $attribute.'超出范围，只能查询前后一个月内的记录');
        }
    }

    public static function getDefaultDateRange() {
        $today = strtotime(date('Y-m-d', time()));
        $start = strtotime("-1 month",$today);
        $end = strtotime("+1 month",$today);

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
        $dateRange = RoomService::queryDateRange();
        $startDate = !empty($this->start_date) ? strtotime($this->start_date) : $dateRange['start'];
        $endDate = !empty($this->end_date) ? strtotime($this->end_date) : $dateRange['end'];
        $roomList = Room::getOpenRooms(true);
        $rooms = !empty($this->rooms) ? json_decode($this->rooms, true) : $roomList;


        $limitRange = static::getDefaultDateRange();  
        if ($startDate < $limitRange['start']  || $endDate > $limitRange['end']) {
            $this->setErrorMessage('日期超出范围，只能查询前后一个月内的记录');
            return false;
        }

        //计算hourTables
        $roomTables = [];
        foreach ($roomList as $room_id) {
            if(!in_array($room_id, $rooms) ){
                continue;
            }
            $roomDateRange = RoomService::queryRoomDateRange($room_id);
            for ($time=$startDate; $time <= $endDate; $time = strtotime("+1 day", $time)) {
                $date = date('Y-m-d', $time);
                $roomTable = RoomService::queryRoomTable($date, $room_id);
                if(!$this->rt_detail){
                    unset($roomTable['ordered']);
                    unset($roomTable['used']);
                    unset($roomTable['locked']);
                }
                $roomTable['available'] = $time >= $roomDateRange['start'] && $time <= $roomDateRange['end'];
                $roomTables[$room_id.'_'.$date] = $roomTable;
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
            'start_date' => date('Y-m-d', $startDate),
            'end_date' => date('Y-m-d', $endDate),
        ];
    }

    /**
     * 取得房间当日占用
     *
     * @return Mixed|null 返回数据
     */
    public function getRoomUse() {
        $data = RoomService::queryRoomTable($this->date, $this->room);
        $roomDateRange = RoomService::queryRoomDateRange($this->room);
        $time = strtotime($this->date);
        $data['available'] = $time >= $roomDateRange['start'] && $time <= $roomDateRange['end'];

        $ordered = RoomTable::getTable($data['ordered']);
        $used = RoomTable::getTable($data['used']);
        $locked = RoomTable::getTable($data['locked']);

        $data = [
            'roomTable' => $data,
            'orders' => [],
            'locks' => [],
        ];

        $orders = OrderService::queryOrders($ordered);
        foreach ($orders as $order_id => &$order) {
            unset($order['opList']);
            $data['orders'][$order_id] = $order;
        }
        $orders = OrderService::queryOrders($used);
        foreach ($orders as $order_id => &$order) {
            unset($order['opList']);
            $data['orders'][$order_id] = $order;
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
        $dateRange = static::getDefaultDateRange();
        $startDate = !empty($this->start_date) ? $this->start_date : date('Y-m-d', $dateRange['start']);
        $endDate = !empty($this->end_date) ? $this->end_date : date('Y-m-d', $dateRange['end']);

        if (strtotime($endDate) -strtotime($startDate) > 31*6 * 86400) {
            $this->setErrorMessage('查询日期间隔不能大于6个月');
            return false;
        }

        $user = Yii::$app->user->getIdentity()->getUser();

        $data = OrderService::queryMyOrders($user, $startDate, $endDate);

        return array_merge($data, [
            'start_date' => $startDate,
            'end_date' => $endDate,
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
