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
     * @param boolean $useCache 是否使用缓存
     * @return json
     */
    public static function queryRoomTable($date, $room_id, $useCache = true) {
        $cache = Yii::$app->cache;
        $cacheKey = 'RoomTable'.'_'.$date.'_'.$room_id;
        $data = $cache->get($cacheKey);
        if ($data == null || !$useCache) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 
            $roomTable = self::getRoomTable($date, $room_id, true, true);
            $data = $roomTable->toArray(['ordered', 'used', 'locked']);
            
            $startHour = Yii::$app->params['order.startHour'];
            $endHour = Yii::$app->params['order.endHour'];
            $hours = [];
            for ($hour = $startHour; $hour <=$endHour ; $hour++) { 
                $hours[] = $hour;
            }
            $data['hourTable'] = RoomTable::getHourTable($data['ordered'], $data['used'], $data['locked'], $hours);
            $data['chksum'] = substr(md5(json_encode($data)), 0, 6);
            $cache->set($cacheKey, $data, 86400*7, new TagDependency(['tags' => $cacheKey]));
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
    }

    /**
     * 查询一系列房间表(带缓存)
     * 优先从缓存中查询
     *
     * @param string $dateRoomList 日期房间的列表
     * @param integer $room_id 房间id
     * @param boolean $useCache 是否使用缓存
     * @return json
     */
    public static function queryRoomTables($dateRoomList, $useCache = true) {
        $cache = Yii::$app->cache;
        $result = [];
        $missList = [];
        foreach ($dateRoomList as $dateRoom) {
            $cacheKey = 'RoomTable'.'_'.$dateRoom[0].'_'.$dateRoom[1];
            $data = $cache->get($cacheKey);
            if ($data == null || !$useCache) {
                Yii::trace($cacheKey.':缓存失效', '数据缓存');
                $missList[] = $dateRoom;
            } else {
                Yii::trace($cacheKey.':缓存命中', '数据缓存');
                $result[$dateRoom[0].'_'.$dateRoom[1]] = $data;
            }
        }

        if(count($missList) > 0) {
            $roomTables = static::getRoomTables($missList, true, true);
            $startHour = Yii::$app->params['order.startHour'];
            $endHour = Yii::$app->params['order.endHour'];
            $hours = [];
            for ($hour = $startHour; $hour <=$endHour ; $hour++) { 
                $hours[] = $hour;
            }

            Yii::beginProfile('RoomTable写入缓存', '数据缓存');
            foreach ($roomTables as $dateRoom => $roomTable) {
                $roomTable['hourTable'] = RoomTable::getHourTable($roomTable['ordered'], $roomTable['used'], $roomTable['locked'], $hours);
                $roomTable['chksum'] = substr(md5(json_encode($roomTable)), 0, 6);
                $result[$dateRoom] = $roomTable;
                $cacheKey = 'RoomTable'.'_'.$dateRoom;
                $cache->set($cacheKey, $roomTable, 0, new TagDependency(['tags' => [$cacheKey,'RoomTable']]));
                Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
            }
            Yii::endProfile('RoomTable写入缓存', '数据缓存');
        }

        return $result;
    }


    /**
     * 查询所有打开房间(带缓存)
     * 优先从缓存中查询
     *
     * @return json
     */
    public static function queryRoomList($useCache = true) {
        $cacheKey = 'roomList';
        $cache = Yii::$app->cache;
        $data = $cache->get($cacheKey);
        if ($data == null || !$useCache) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 
            $data = [];
            $roomList_ = Room::getOpenRooms(false);
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
            $cache->set($cacheKey, $data, 86400, new TagDependency(['tags' => [$cacheKey, 'Room']]));
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
    }


    /**
     * 得到房间的日期范围(带缓存)
     *
     * @param int $room_id
     * @param boolean $useCache 是否使用缓存
     * @return json
     */
    public static function queryRoomDateRange($room_id, $useCache = true) {
        $cache = Yii::$app->cache;
        $cacheKey = 'Room_'.$room_id.'_dateRange';
        $data = $cache->get($cacheKey);
        if ($data == null || !$useCache) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存');

            $now = time();
            $room = Room::findOne($room_id);
            $roomData = $room->data;
            $dateRange = Room::getDateRange($roomData['max_before'], $roomData['min_before'], $roomData['by_week'], $roomData['open_time'], $now);
            
            $data = $dateRange;
            $cache->set($cacheKey, $data, $data['expired'], new TagDependency(['tags' => 'Room_'.$room_id]));
            Yii::trace($cacheKey.':写入缓存, $expired='.$data['expired'], '数据缓存'); 
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
    }

    /**
     * 批量得到房间的日期范围(带缓存)
     *
     * @param list $room_idList
     * @param boolean $useCache 是否使用缓存
     * @return json
     */
    public static function queryDateRanges($room_idList, $useCache = true) {
        $cache = Yii::$app->cache;
        $result = [];
        $missList = [];
        foreach ($room_idList as $room_id) {
            $cacheKey = 'Room_'.$room_id.'_dateRange';
            $data = $cache->get($cacheKey);
            if ($data == null || !$useCache) {
                Yii::trace($cacheKey.':缓存失效', '数据缓存');
                $missList[] = $room_id;
            } else {
                Yii::trace($cacheKey.':缓存命中', '数据缓存');
                $result[(string)$room_id] = $data;
            }
        }
        if(count($missList) > 0) {
            $now = time();
            $rooms = Room::find()->where(['in', 'id', $missList])->all();
            foreach ($rooms as $room) {
                $room_id = $room->id;
                $roomData = $room->data;
                $dateRange = Room::getDateRange($roomData['max_before'], $roomData['min_before'], $roomData['by_week'], $roomData['open_time'], $now);
                $result[$room_id] = $dateRange;

                $cacheKey = 'Room_'.$room_id.'_dateRange';
                $cache->set($cacheKey, $dateRange, $dateRange['expired'], new TagDependency(['tags' => 'Room_'.$room_id]));
                Yii::trace($cacheKey.':写入缓存, $expired='.$dateRange['expired'], '数据缓存'); 
            }
        }

        return $result;
    }



    /**
     * 查询所有房间的日期范围(带缓存)
     * 优先从缓存中查询
     * 
     * @param boolean $useCache 是否使用缓存
     * @return json
     */
    public static function queryWholeDateRange($useCache = true) {
        $cacheKey = 'dateRange';
        $cache = Yii::$app->cache;
        $data = $cache->get($cacheKey);
        if ($data == null || !$useCache) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 

            $now = time();
            $startDate = mktime(0, 0, 0, date("m", $now), date("d", $now), date("Y", $now));
            $endDate = $startDate;
            $expired = 86400;

            $roomList = Room::getOpenRooms(true);
            $dateRanges = RoomService::queryDateRanges($roomList, $useCache);
            foreach ($dateRanges as $dateRange) {
                $endDate = max($endDate, $dateRange['end']);
                $expired = min($expired,  $dateRange['expired']);
            }
            $data = [
                'start' => $startDate,
                'end' => $endDate,
            ];
            $cache->set($cacheKey, $data, $expired, new TagDependency(['tags' => 'Room']));
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
            $roomTable->id = $date.'_'.$room_id;
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
     * 批量得到房间表
     * 如果方法不存在将会创建新的，多并发情况下可能会出现创建失败的情况，但是保证一定会存在。
     * 调用此方法时不要开事务！
     *
     * @param array $dateRoomList 日期房间的列表
     * @param integer $applyOrder 是否自动应用预约
     * @param integer $applyLock 是否自动应用房间锁
     * @return array of roomtable(array)
     */
    public static function getRoomTables($dateRoomList, $applyOrder = false, $applyLock = false) {
        $idList = [];
        foreach ($dateRoomList as $dateRoom) {
            $idList[] = $dateRoom[0].'_'.$dateRoom[1];
        }

        $roomTables = [];
        foreach (RoomTable::find()
            ->where(['in', 'id', $idList])
            ->select(['id', 'ordered', 'used', 'locked'])
            ->asArray()->each(100) as $roomTable) {
            $roomTable['ordered'] = json_decode($roomTable['ordered'], true);
            $roomTable['used'] = json_decode($roomTable['used'], true);
            $roomTable['locked'] = json_decode($roomTable['locked'], true);
            $roomTables[$roomTable['id']] = $roomTable;
        }

        //判断不存在的roomTable,准备查询条件
        $missList = [];
        $orderWhere = [];
        foreach ($dateRoomList as $dateRoom) {
            if (!isset($roomTables[$dateRoom[0].'_'.$dateRoom[1]])) {
                $missList[] = $dateRoom;
                if (!isset($orderWhere[$dateRoom[0]])){
                    $orderWhere[$dateRoom[0]] = [];
                }
                $orderWhere[$dateRoom[0]][] = $dateRoom[1];
            }
        }

        if(count($missList) > 0) {
            if ($applyOrder) {
                //批量获取申请
                $orderFind = Order::find()->where('1=0')->select(['id', 'date', 'room_id', 'status', 'hours']);
                foreach ($orderWhere as $date => $rooms_ids) {
                    $orderFind->union(Order::find()->where(['date'=>$date,'room_id'=>$rooms_ids])->select(['id', 'date', 'room_id', 'status', 'hours']));
                }
                $result = $orderFind->asArray()->all();
                
                $orders = [];           
                foreach ($result as $order) {
                    $dateRoom = $order['date'].'_'.$order['room_id'];
                    if (!isset($orders[$dateRoom])){
                        $orders[$dateRoom] = [];
                    }
                    $orders[$dateRoom][] = [
                        'id' => $order['id'],
                        'status' => $order['status'],
                        'hours' => json_decode($order['hours'],true),
                    ];
                }
            }
            if ($applyLock) {
                //批量获取房间锁
                $lockTables = LockService::queryLockTables($missList);
            }
            
            $roomTableRows = [];
            $roomTableAttrs = ['id', 'date', 'room_id', 'ordered', 'used', 'locked', 'created_at','updated_at'];
            foreach ($missList as  $dateRoom) {
                $date = $dateRoom[0];
                $room_id = $dateRoom[1];

                $roomTable = new RoomTable();
                $roomTable->id = $date.'_'.$room_id;
                $roomTable->date = $date;
                $roomTable->room_id = $room_id;

                 //应用预约
                if ($applyOrder) {
                    if (isset($orders[$roomTable->id])) {
                        $orderList = $orders[$roomTable->id];
                        foreach ($orderList as $key => $order) {
                            $rtStatus = Order::getRoomTableStatus($order['status']);
                            if ($rtStatus == Order::ROOMTABLE_ORDERED) {
                                $roomTable->addOrdered($order['id'], $order['hours']);
                            } if ($rtStatus == Order::ROOMTABLE_USED) {
                                $roomTable->addUsed($order['id'], $order['hours']);
                            } 
                        }
                    } 
                };

                //应用房间锁
                if ($applyLock) {
                    if (isset($lockTables[$roomTable->id])) {
                        $roomTable->locked = $lockTables[$roomTable->id];
                    }
                }

                $insertData = $roomTable->getInsertData($roomTableAttrs);
                $roomTableRows[] = [
                    $insertData['id'],
                    $insertData['date'],
                    $insertData['room_id'],
                    $insertData['ordered'],
                    $insertData['used'],
                    $insertData['locked'],
                    $insertData['created_at'],
                    $insertData['updated_at'],
                ];

                $roomTable = $roomTable->toArray(['ordered', 'used', 'locked']);
                $roomTables[$date.'_'.$room_id] = $roomTable;
            }
            $rows_chunks = array_chunk($roomTableRows, 100);
            foreach ($rows_chunks as $rows_chunk) {
                Yii::$app->db->createCommand()->batchInsert(RoomTable::tableName(), $roomTableAttrs, $rows_chunk)->execute();
            }
        }
        return $roomTables;
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