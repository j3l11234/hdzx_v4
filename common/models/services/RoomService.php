<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\services;

use Yii;
use yii\base\Component;
use common\exception\RoomTableException;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\models\services\OrderService;

/**
 * 房间相关服务类
 * 负责RoomTable数据的读取，获取
 * 预约信息注册，房间锁信息的注册
 */
class RoomService extends Component {

    /**
     * 查询一个房间表(带缓存)
     * 优先从缓存中查询
     *
     * @param string $date 预约日期
     * @param integer $room_id 房间id
     * @return json
     */
    public static function queryRoomTable($date, $room_id) {
        $cache = Yii::$app->cache;
        $cacheKey = RoomTable::getCacheKey($date, $room_id);
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效'); 
            $roomTable = self::getRoomTable($date, $room_id);
            $data = $roomTable->toArray(['ordered', 'used', 'locked']);
            
            $startHour = Yii::$app->params['order.startHour'];
            $endHour = Yii::$app->params['order.endHour'];
            $hours = [];
            for ($hour = $startHour; $hour <=$endHour ; $hour++) { 
                $hours[] = $hour;
            }
            $data['hourTable'] = $roomTable->getHourTable($hours);
            $cache->set($cacheKey, $data);
        } else {
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
    }

    /**
     * 查询一个房间占用信息
     * 优先从缓存中查询
     *
     * @param string $date 预约日期
     * @param integer $room_id 房间id
     * @return Mixed 房间占用信息
     */
    public static function queryRoomUse($date, $room_id){
        $data = self::queryRoomTable($date, $room_id);

        $ordered = RoomTable::getTable($data['ordered']);
        $used = RoomTable::getTable($data['used']);
        $locked = RoomTable::getTable($data['locked']);

        $data['orders'] = [];
        foreach ($ordered as  $order_id) {
            $order = OrderService::queryOneOrder($order_id);
            if ($order !== null) {
                $data['orders'][$order_id] = $order;
            }
        }
        foreach ($used as  $order_id) {
            $order = OrderService::queryOneOrder($order_id);
            if ($order !== null) {
                $data['orders'][$order_id] = $order;
            }
        }
        
        return $data;
    }

    /**
     * 查询所有打开房间(带缓存)
     * 优先从缓存中查询
     *
     * @return json
     */
    public static function queryRoomList() {
        $cacheKey = 'roomList';
        $cache = Yii::$app->cache;
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效'); 
            $data = [];
            $roomList_ = Room::getOpenRooms();
            $roomList = [];
            $rooms = [];
            foreach ($roomList_ as $key => $room) {
                $room = $room->toArray(['id','number', 'name', 'type', 'data']);
                $room = array_merge($room, $room['data']);
                unset($room['data']);
                $roomList[] = $room['id'];
                $rooms[$room['id']] = $room;
            }
            $data = [
                'roomList' => $roomList,
                'rooms' => $rooms,
            ];
            $cache->set($cacheKey, $data);
        }else{
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
    }

    /**
     * 得到一个房间表
     * 如果方法不存在将会创建一个，多并发情况下可能会创建多个，但是读取的保证是最早创建的唯一一个
     * 调用此方法时不要开事务！
     *
     * @param string $date 预约日期
     * @param integer $room_id 房间id
     * @return RoomTable
     */
    public static function getRoomTable($date, $room_id) {
        $roomTable = RoomTable::findByDateRoom($date, $room_id);
        if ($roomTable === null) {
            $roomTable = new RoomTable();
            $roomTable->date = $date;
            $roomTable->room_id = $room_id;
            $roomTable->save();

            //重新查找，保证并发唯一
            $roomTable = RoomTable::findByDateRoom($date, $room_id);
        }
        return $roomTable;
    }

    /**
     * 应用一个预约
     * 将这个预约信息写入roomTable
     * 该方法将会把对应的roomTable项的id先清空再写入，所以如果$hours为null或者空数组，等同于将该order清除掉
     *
     * @param RoomTable $roomTable roomTable
     * @param id $id 预约id
     * @param array $hours 预约小时数组
     * @param boolean $isUsed true写入used,false写入order
     * @return true 如果写入成功
     * @throws StaleObjectException 如果存在并发冲突
     */
    public static function applyOrder($roomTable, $id, $hours, $isUsed = false) {
        $roomTable->removeOrdered($id);
        $roomTable->removeUsed($id);
        if ($isUsed) {
            $roomTable->addUsed($id, $hours);
        }else{
            $roomTable->addOrdered($id, $hours);
        }
        //清除缓存
        $cache = Yii::$app->cache;
        $cacheKey = RoomTable::getCacheKey($roomTable->date, $roomTable->room_id);
        $cache->delete($cacheKey);

        return $roomTable->save();
    }
}