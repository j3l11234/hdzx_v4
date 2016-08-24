<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\services;

use Yii;
use yii\base\Component;
use yii\caching\TagDependency;
use common\exceptions\RoomTableException;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\models\entities\Order;
use common\services\OrderService;
use common\services\LockService;

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
     * @param boolean $cache 是否使用缓存
     * @return json
     */
    public static function queryRoomTable($date, $room_id, $cache = true) {
        $cache = Yii::$app->cache;
        $cacheKey = 'RoomTable'.'_'.$date.'_'.$room_id;
        $data = $cache->get($cacheKey);
        if ($data == null || !$cache) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 
            $roomTable = self::getRoomTable($date, $room_id, true, true);
            $data = $roomTable->toArray(['ordered', 'used', 'locked']);
            
            $startHour = Yii::$app->params['order.startHour'];
            $endHour = Yii::$app->params['order.endHour'];
            $hours = [];
            for ($hour = $startHour; $hour <=$endHour ; $hour++) { 
                $hours[] = $hour;
            }
            $data['hourTable'] = $roomTable->getHourTable($hours);
            $data['chksum'] = substr(md5(json_encode($data)), 0, 6);
            $cache->set($cacheKey, $data, 86400*7, new TagDependency(['tags' => $cacheKey]));
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
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
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 
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
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
    }

     /**
     * 查询所有房间的日期范围(带缓存)
     * 优先从缓存中查询
     * 
     * @param boolean $cache 是否使用缓存
     * @return json
     */
    public static function queryDateRange($cache = true) {
        $now = time();
        $cacheKey = 'dateRange_'.date('Y-m-d', $now);
        $cache = Yii::$app->cache;
        $data = $cache->get($cacheKey);
        if ($data == null || !$cache) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 

            $startDate = mktime(0, 0, 0, date("m", $now), date("d", $now), date("Y", $now));
            $endDate = $startDate;
            $expired = 86400;

            $roomList = Room::getOpenRooms();
            foreach ($roomList as $room) {
                $roomData = $room->data;
                $dateRange = Room::getDateRange($roomData['max_before'], $roomData['min_before'], $roomData['by_week'], $roomData['open_time'], $now);
                $endDate = max($endDate, $dateRange['end']);
                $expired = min($expired,  $dateRange['expired']);
            }
            $data = [
                'start' => $startDate,
                'end' => $endDate,
            ];
            $cache->set($cacheKey, $data, $expired);
            Yii::trace($cacheKey.':写入缓存, $expired='.$expired, '数据缓存'); 
        }else{
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
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
     * @param integer $lock 是否自动应用房间锁
     * @return RoomTable
     */
    public static function getRoomTable($date, $room_id, $applyOrder = false, $applyLock = false) {
        $roomTable = RoomTable::findByDateRoom($date, $room_id);
        if ($roomTable === null) {
            $roomTable = new RoomTable();
            $roomTable->date = $date;
            $roomTable->room_id = $room_id;

            //应用预约
            if ($applyOrder) {
                $orderList = Order::findByDateRoom($date, $room_id);
                //codecept_debug($orderList);
                
                foreach ($orderList as $key => $order) {
                    $rtStatus = Order::getRoomTableStatus($order->status);
                    if ($rtStatus == Order::ROOMTABLE_ORDERED) {
                        $roomTable->addOrdered($order->id, $order->hours);
                    } if ($rtStatus == Order::ROOMTABLE_USED) {
                        $roomTable->addUsed($order->id, $order->hours);
                    } 
                }
            };

            //应用房间锁
            if ($applyLock) {
                $lockList = LockService::queryLockTable($date, $room_id);
                foreach ($lockList as $key => $lock_id) {
                    $lock = LockService::queryOneLock($lock_id);
                    $roomTable->addLocked($lock_id, $lock['hours']);
                }
            }
            //codecept_debug($roomTable->toArray(['ordered','used','locked']));
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