<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\services;

use Yii;
use yii\base\Component;
use yii\caching\TagDependency;
use common\models\entities\Lock;
use common\models\entities\RoomTable;
use common\models\services\RoomService;

/**
 * 房间锁相关服务类
 */
class LockService extends Component {

    /**
     * 得到房间锁定的的日期(带缓存)
     *
     * @param Lock $lock_id 房间锁
     * @return json
     */
    public static function queryLockDateList($lock_id) {
        $cache = Yii::$app->cache;
        $cacheKey = Lock::getCacheKey($lock_id).'_dateList';
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效');

            $lock = Lock::findOne($lock_id);
            $dateList = $lock->getDateListSelf();
            
            $data = $dateList;
            $cache->set($cacheKey, $data, 0, new TagDependency(['tags' => Lock::getCacheKey($lock_id)]));
        } else {
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
    }

    /**
     * 查询一个锁定表(带缓存)
     * 优先从缓存中查询
     *
     * @param string $date 日期
     * @param integer $room_id 房间id
     * @return Array lock_id列表
     */
    public static function queryLockTable($date, $room_id) {
        $cache = Yii::$app->cache;
        $cacheKey = 'LockTable'.'_'.$date.'_'.$room_id;
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效');
            
            $result = Lock::find()->select(['id'])->where(['status' => Lock::STATUS_ENABLE])->all();
            $lockList = [];
            $tags = [];
            foreach ($result as $key => $lock) {
                $lock = self::queryOneLock($lock['id']);
                if(!in_array($room_id, $lock['rooms'])){
                    continue;
                }

                $dateList = self::queryLockDateList($lock['id']);
                if (in_array($date, $dateList)) {
                    $lockList[] = $lock['id'];
                    $tags[] = Lock::getCacheKey($lock['id']);
                }
            }

            $data = $lockList;
            $cache->set($cacheKey, $data, 90*86400, new TagDependency(['tags' => $tags]));
        } else {
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
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

        $lockList = Lock::find()->select(['id'])->where(['status' => Lock::STATUS_ENABLE])->all();
        $lockTables = [];
        foreach ($lockList as $key => $lock) {
            $lock = self::queryOneLock($lock['id']);
            $dateList = self::queryLockDateList($lock['id']);

            foreach ($dateList as $key => $date) {
                $dateTs = strtotime($date);
                if($dateTs < $startDateTs || $dateTs > $endDateTs) {
                    continue;
                }

                foreach ($lock['rooms'] as $key => $room_id) {
                    if (!isset($lockTables[$room_id])) {
                        $lockTables[$room_id] = [];
                    }
                    if (!isset($lockTables[$room_id][$date])) {
                        $lockTables[$room_id][$date] = [];
                    }
                    RoomTable::addTable($lockTables[$room_id][$date], $lock['id'], $lock['hours']);
                }
            } 
        }

        //对roomTable进行差值更新
        foreach ($lockTables as $room_id => $roomLockTables) {
            foreach ($roomLockTables as $date => $lockTable) {
                $roomTable = RoomService::getRoomTable($date, $room_id);
                $data = $roomTable->toArray(['locked']);
                if(json_encode($data['locked']) != json_encode($lockTable)){
                    $roomTable->setLocked($lockTable);
                    $roomTable->useOptimisticLock = false;
                    $roomTable->save();

                    //清除缓存
                    $cache = Yii::$app->cache;
                    $cacheKey = RoomTable::getCacheKey($date, $room_id);
                    $cache->delete($cacheKey);
                }
            }
        }
    }


    /**
     * 查询一个锁(带缓存)
     *
     * @param int $lock_id 房间锁id
     * @return array
     */
    public static function queryOneLock($lock_id) {
        $cache = Yii::$app->cache;
        $cacheKey = Lock::getCacheKey($lock_id);
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效');

            $order = Lock::findOne($lock_id);
            $data = $order->toArray(['id', 'rooms', 'hours', 'start_date', 'end_date', 'status', 'data']);
            $data = array_merge($data, $data['data']);
            unset($data['data']);

            $cache->set($cacheKey, $data, 0, new TagDependency(['tags' => Lock::getCacheKey($lock_id)]));
        } else {
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
    }

    public static function addLock($lock_id){
        //读取解析lock的room和date，使其缓存失效
    }
}