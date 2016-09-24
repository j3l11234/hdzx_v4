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
use common\helpers\HdzxException;
use common\helpers\Error;
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
     * 查询所有开放房间(带缓存)
     * @param boolean $onlyId 仅获取id
     * @param boolean $useCache 是否使用缓存
     * @return 如果onlyId未真，返回room_id的列表，否则返回room的Map
     */
    public static function getRoomList($onlyId = FALSE, $useCache = TRUE) {
        $cacheKey = 'RoomList';
        $data = Yii::$app->cache->get($cacheKey);
        if ($data == null || !$useCache) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存');

            $rooms = Room::getOpenRooms(false);
            $roomList = array_keys($rooms);
            
            $data = [
                'roomList' => $roomList,
                'rooms' => $rooms,
            ];
            Yii::$app->cache->set($cacheKey, $data, 86400, new TagDependency(['tags' => ['Room']]));
            Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }

        if ($onlyId) {
            $data = $data['roomList'];
        }
        return $data;
    }


    /**
     * 得到房间的日期范围(带缓存)
     *
     * @param Array $room_ids
     * @param boolean $useCache 是否使用缓存
     * @return Array DateRange的Map
     */
    public static function getDateRanges($room_ids, $useCache = true) {
        $dateRanges = [];

        //读取缓存
        $cacheMisses;
        if ($useCache) {
            $cacheMisses = [];
            Yii::beginProfile('Room_DateRange读取缓存', '数据缓存');
            foreach ($room_ids as $room_id) {
                $cacheKey = 'Room_'.$room_id.'_dateRange';
                $cacheData = Yii::$app->cache->get($cacheKey);
                if ($cacheData == null) {
                    Yii::trace($cacheKey.':缓存失效', '数据缓存');
                    $cacheMisses[] = $room_id;
                } else {
                    Yii::trace($cacheKey.':缓存命中', '数据缓存');
                    $dateRanges[$room_id] = $cacheData;
                }
            }
            Yii::endProfile('Room_DateRange读取缓存', '数据缓存');
        } else {
            $cacheMisses = $room_id;
        }

        //获取剩下数据(缓存miss的)
        if (count($cacheMisses) > 0) {
            $cacheNews = [];
            $now = time();
            foreach (Room::find()->where(['in', 'id', $cacheMisses])
                ->asArray()->each(100) as $room) {
                $roomData = json_decode($room['data'], TRUE);
                $dateRange = Room::getDateRange($roomData['max_before'], $roomData['min_before'], $roomData['by_week'], $roomData['open_time'], $now);
                $dateRanges[$room['id']] = $dateRange;
                $cacheNews[] = $room['id'];
            }

            //写入缓存
            Yii::beginProfile('Room_DateRange写入缓存', '数据缓存');
            foreach ($cacheNews as $room_id) {
                $dateRange = $dateRanges[$room_id];
                $cacheKey = 'Room_'.$room_id.'_dateRange';
                Yii::$app->cache->set($cacheKey, $dateRange, $dateRange['expired'], new TagDependency(['tags' => ['Room_'.$room_id,'Room']]));
                Yii::trace($cacheKey.':写入缓存, $expired='.$dateRange['expired'], '数据缓存'); 
            }
            Yii::endProfile('Room_DateRange写入缓存', '数据缓存');
        }

        return $dateRanges;
    }


    /**
     * 查询所有房间的日期范围(带缓存)
     * 优先从缓存中查询
     * 
     * @param boolean $useCache 是否使用缓存
     * @return json
     */
    public static function getSumDateRange($useCache = true) {
        $cacheKey = 'WholeDateRange';
        $cache = Yii::$app->cache;
        $data = $cache->get($cacheKey);
        if ($data == null || !$useCache) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 

            $now = time();
            $startDate = mktime(0, 0, 0, date("m", $now), date("d", $now), date("Y", $now));
            $endDate = $startDate;
            $expired = 86400;

            $roomList = RoomService::getRoomList(TRUE, $useCache);
            $dateRanges = RoomService::getDateRanges($roomList, $useCache);
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
     * 获取房间表
     * 如果对应时间表不存在，将会写入一个新的。在多并发情况下可能会有部分插入失败，但是保证一定有已经插入的存在
     * 调用此方法时不要开事务！
     *
     * @param Array $dateRooms [日期房间]的数组
     * @param $useCache 是否使用缓存(默认为是)
     * @param integer $applyOrder 生成新的房间表时，是否应用预约数据 (默认为是)
     * @param integer $applyLock 生成新的房间表时，是否应用房间锁 (默认为是)
     * @return Array Map形式的Roomtable
     */
    public static function getRoomTables($dateRooms, $useCache = true,
        $applyOrder = true, $applyLock = true) {
        $roomTables = [];

        //读取缓存
        $cacheMisses;
        if ($useCache) {
            $cacheMisses = [];
            Yii::beginProfile('RoomTable读取缓存', '数据缓存');
            foreach ($dateRooms as $dateRoom) {
                $cacheKey = 'RoomTable'.'_'.$dateRoom;
                $cacheData = Yii::$app->cache->get($cacheKey);
                if ($cacheData == null) {
                    Yii::trace($cacheKey.':缓存失效', '数据缓存');
                    $cacheMisses[] = $dateRoom;
                } else {
                    Yii::trace($cacheKey.':缓存命中', '数据缓存');
                    $roomTables[$dateRoom] = $cacheData;
                }
            }
            Yii::endProfile('RoomTable读取缓存', '数据缓存');
        } else {
            $cacheMisses = $dateRooms;
        }

        //获取剩下数据(缓存miss的)
        if(count($cacheMisses) > 0) {
            $hours = Yii::$app->params['order.hours'];
            $cacheNews = [];

            //从数据库获取剩余数据
            foreach (RoomTable::find()
                ->where(['in', 'id', $cacheMisses])
                ->select(['id', 'ordered', 'used', 'locked'])
                ->asArray()->each(100) as $roomTable) {
                $roomTable['ordered'] = json_decode($roomTable['ordered'], true);
                $roomTable['used'] = json_decode($roomTable['used'], true);
                $roomTable['locked'] = json_decode($roomTable['locked'], true);
                $roomTable['hourTable'] = RoomTable::getHourTable($roomTable['ordered'], $roomTable['used'], $roomTable['locked'], $hours);
                $roomTable['chksum'] = substr(md5(json_encode($roomTable)), 0, 6);
                $roomTables[$roomTable['id']] = $roomTable;
                $cacheNews[] = $roomTable['id'];
            }

            $dbMisses = [];
            foreach ($cacheMisses as $dateRoom) {
                if (!isset($roomTables[$dateRoom])) {
                    $dbMisses[] = $dateRoom;
                }
            }
            
            //生成缺失数据(数据库中不存在的)
            if(count($dbMisses) > 0) {
                static::addRoomTables($dbMisses, $applyOrder, $applyLock);
                foreach (RoomTable::find()
                    ->where(['in', 'id', $dbMisses])
                    ->select(['id', 'ordered', 'used', 'locked'])
                    ->asArray()->each(100) as $roomTable) {
                    $roomTable['ordered'] = json_decode($roomTable['ordered'], true);
                    $roomTable['used'] = json_decode($roomTable['used'], true);
                    $roomTable['locked'] = json_decode($roomTable['locked'], true);
                    $roomTable['hourTable'] = RoomTable::getHourTable($roomTable['ordered'], $roomTable['used'], $roomTable['locked'], $hours);
                    $roomTable['chksum'] = substr(md5(json_encode($roomTable)), 0, 6);
                    $roomTables[$roomTable['id']] = $roomTable;
                    $cacheNews[] = $roomTable['id'];
                }

                //验证是否全部添加成功
                //即使在并发情况下，部分插入失败(那意味着其他并发已经插入了)，也能取得所有数据
                $dbFails = [];
                foreach ($dbMisses as $dateRoom) {
                    if (!isset($roomTables[$dateRoom])) {
                        $dbFails[] = $dateRoom;
                    }
                }
                if(count($dbFails) > 0) {
                    throw new HdzxException('房间表创建失败', Error::ROOMTABLE_ADD);
                }
            }

            //写入缓存
            Yii::beginProfile('RoomTable写入缓存', '数据缓存');
            foreach ($cacheNews as $dateRoom) {
                $roomTable = $roomTables[$dateRoom];
                $cacheKey = 'RoomTable'.'_'.$dateRoom;
                Yii::$app->cache->set($cacheKey, $roomTable, 0, new TagDependency(['tags' => [$cacheKey, 'RoomTable']]));
                Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
            }
            Yii::endProfile('RoomTable写入缓存', '数据缓存');
        }
        return $roomTables;
    }

    /**
     * 批量生成roomtable
     * 如果对应roomtable已经存在，将会更新新的内容
     * 调用此方法时不要开事务！
     *
     * @param Array $dateRooms [日期房间]的数组
     * @param integer $applyOrder 生成新的房间表时，是否应用预约数据 (默认为是)
     * @param integer $applyLock 生成新的房间表时，是否应用房间锁 (默认为是)
     * @return Array Map形式的roomtable
     */
    public static function addRoomTables($dateRooms, $applyOrder = true, $applyLock = true) {
        if ($applyOrder) {
            //批量获取申请
            $orderTables = OrderService::getOrderTables($dateRooms);
        }
        if ($applyLock) {
            //批量获取房间锁
            $lockTables = LockService::getLockTables($dateRooms);
        }

        $roomTableRows = [];
        foreach ($dateRooms as $dateRoom) {
            $dateRoomSplit = explode('_', $dateRoom);
            $roomTable = [
                'id'        => $dateRoom,
                'date'      => $dateRoomSplit[0],
                'room_id'   => $dateRoomSplit[1],
                'ordered'   => [],
                'used'      => [],
                'locked'    => [],
            ];

             
            if ($applyOrder && isset($orderTables[$dateRoom])) {
                //应用申请
                $roomTable['ordered'] = $orderTables[$dateRoom]['ordered'];
                $roomTable['used'] = $orderTables[$dateRoom]['used'];
            };

            
            if ($applyLock && isset($lockTables[$dateRoom])) {
                //应用房间锁
                $roomTable['locked'] = $lockTables[$dateRoom];
            }

            $roomTableRows[] = [
                $roomTable['id'],
                $roomTable['date'],
                $roomTable['room_id'],
                json_encode($roomTable['ordered']),
                json_encode($roomTable['used']),
                json_encode($roomTable['locked']),
                time(), //created_at
                time(), //updated_at
            ];
        }

        //分组批量插入
        $rowsChunks = array_chunk($roomTableRows, 100);
        foreach ($rowsChunks as $rowsChunk) {
            Yii::$app->db->createCommand()->batchInsert(RoomTable::tableName(),
                ['id', 'date', 'room_id', 'ordered', 'used', 'locked', 'created_at','updated_at'],
                $rowsChunk)->execute();
        }
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

     /**
     * 应用房间锁到时间表
     *
     * @param date $start_date 开始时间
     * @param date $end_date 结束时间
     * @return array
     */
    public static function applyLock($start_date, $end_date) {
        $startDateTs = strtotime($start_date);
        $endDateTs = strtotime($end_date);
        $roomList = Room::getOpenRooms(true);
        $dateRoomList = [];
        foreach ($roomList as $room_id) {
            for ($time = $startDateTs; $time <= $endDateTs; $time = strtotime("+1 day", $time)) {
                $date = date('Y-m-d', $time);
                $dateRoomList[] = [$date,$room_id];
            }
        }

        $lockTables = LockService::getLockTables($dateRoomList);

        $roomTables = RoomService::getRoomTables($dateRoomList,true, false);
        $roomTableRows = [];
        foreach ($roomList as $room_id) {
            for ($time = $startDateTs; $time <= $endDateTs; $time = strtotime("+1 day", $time)) {
                $date = date('Y-m-d', $time);
                $lockTable = $lockTables[$date.'_'.$room_id];
                $roomTable = $roomTables[$date.'_'.$room_id];
                if(json_encode($roomTable['locked']) != json_encode($lockTable)){
                    $roomTable['locked'] = $lockTable;
                    $roomTableRows[] = [$date.'_'.$room_id, json_encode($roomTable['locked'])];
                    //清除缓存
                    TagDependency::invalidate(Yii::$app->cache, 'RoomTable'.'_'.$date.'_'.$room_id);
                }
            }
        }

        $rows_chunks = array_chunk($roomTableRows, 100);
        foreach ($rows_chunks as $rows_chunk) {
            $sql = Yii::$app->db->getQueryBuilder()->batchInsert(RoomTable::tableName(), ['id','locked'], $rows_chunk);
            Yii::$app->db->createCommand($sql.' ON DUPLICATE KEY UPDATE locked=VALUES(locked)')->execute();
        }

    }

}