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
use common\models\entities\Lock;
use common\models\entities\RoomTable;
use common\services\RoomService;

/**
 * 房间锁相关服务类
 */
class LockService extends Component {

    /**
     * 查询锁列表
     *
     * @return json
     */
    public static function getLockList() {
        $data = [];
        $result = Lock::find()->select(['id'])->all();
        $lockList = [];
        $locks = [];
        foreach ($result as $key => $lock) {
            $lock = static::queryOneLock($lock['id']);
            $lockList[] = $lock['id'];
            $locks[$lock['id']] = $lock;
        }
        $data = [
            'lockList' => $lockList,
            'locks' => $locks,
        ];
        return $data;
    }

    /**
     * 得到房间锁定的的日期(带缓存)
     *
     * @param Lock $lock_id 房间锁
     * @return json
     */
    public static function queryLockDateList($lock_id) {
        $cache = Yii::$app->cache;
        $cacheKey = 'Lock_'.$lock_id.'_dateList';
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存');

            $lock = static::queryOneLock($lock_id);
            $dateList = Lock::getDateList($lock['loop_type'], $lock['loop_day'], $lock['start_date'], $lock['end_date']);
            
            $data = $dateList;
            $cache->set($cacheKey, $data, 0, new TagDependency(['tags' => 'Lock_'.$lock_id]));
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
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
            Yii::trace($cacheKey.':缓存失效', '数据缓存');
            
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
                    $tags[] = 'Lock'.'_'.$lock['id'];
                }
            }

            $data = $lockList;
            $tags[] = 'LockTable';
            $tags[] = $cacheKey;
            $cache->set($cacheKey, $data, 90*86400, new TagDependency(['tags' => $tags]));
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
    }

     /**
     * 批量查询锁定表(带缓存)
     * 优先从缓存中查询
     *
     * @param array $dateRoomList 日期房间的列表
     * @param integer $room_id 房间id
     * @return Array lock_id列表
     */
    public static function queryLockTables($dateRoomList) {
        $cache = Yii::$app->cache;
        $result = [];
        $missList = [];

        foreach ($dateRoomList as $dateRoom) {
            $cacheKey = 'LockTable'.'_'.$dateRoom[0].'_'.$dateRoom[1];            
            $data = $cache->get($cacheKey);
            if ($data == null || !$useCache) {
                Yii::trace($cacheKey.':缓存失效', '数据缓存');
                $missList[] = $dateRoom;
            } else {
                Yii::trace($cacheKey.':缓存命中', '数据缓存');
                $result[$dateRoom] = $data;
            }
        }

        if(count($missList) > 0) {
            $startDateTs = -1;
            $endDateTs = -1;
            $room_ids = [];
            foreach ($missList as $dateRoom) {
                $dateTs = strtotime($dateRoom[0]);
                if($startDateTs == -1 || $startDateTs > $dateTs){
                    $startDateTs = $dateTs;
                }
                if($endDateTs == -1 || $endDateTs < $dateTs){
                    $endDateTs = $dateTs;
                }
                $room_ids[$dateRoom[1]] = true;
            }
            $room_ids =array_keys($room_ids);
            Yii::trace('$startDateTs='.$startDateTs, '数据');
            Yii::trace('$endDateTs='.$endDateTs, '数据');
            Yii::trace('$room_ids='.var_export($room_ids,TRUE), '数据');

            $result = Lock::find()->select(['id'])->where(['status' => Lock::STATUS_ENABLE])
            ->asArray()->all();
            $lock_idList = array_column($result, 'id');
            $locks = static::queryLocks($lock_idList);

            $lockTables = [];
            foreach ($locks as $lock_id => $lock) {
                $dateList = self::queryLockDateList($lock_id);
                foreach ($dateList as $date) {
                    $dateTs = strtotime($date);
                    if ($dateTs < $startDateTs || $dateTs > $endDateTs) {
                        continue;
                    }
                    foreach ($lock['rooms'] as $room_id) {
                        if(!empty($room_ids) && !in_array($room_id, $lock['rooms'])){
                            continue;
                        }
                        if (!isset($lockTables[$date.'_'.$room_id])) {
                            $lockTables[$date.'_'.$room_id] = [];
                        }
                        $lockTables[$date.'_'.$room_id] = RoomTable::addTable($lockTables[$date.'_'.$room_id], $lock['id'], $lock['hours']);
                    }
                }
            }

            Yii::beginProfile('LockTable写入缓存', '数据缓存');
            foreach ($lockTables as $dateRoomKey => $lockTable) {
                $result[$dateRoomKey] = $lockTable;

                $cacheKey = 'LockTable'.'_'.$dateRoomKey;
                $cache->set($cacheKey, $data, 0, new TagDependency(['tags' => [$cacheKey, 'LockTable']]));
                Yii::trace($cacheKey.':写入缓存', '数据缓存');
            }
            Yii::endProfile('LockTable写入缓存', '数据缓存');

            return $result; 
        }
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
        foreach ($lockList as $lock) {
            $lock = static::queryOneLock($lock['id']);
            $dateList = static::queryLockDateList($lock['id']);

            foreach ($dateList as $date) {
                $dateTs = strtotime($date);
                if($dateTs < $startDateTs || $dateTs > $endDateTs) {
                    continue;
                }

                foreach ($lock['rooms'] as $room_id) {
                    if (!isset($lockTables[$room_id])) {
                        $lockTables[$room_id] = [];
                    }
                    if (!isset($lockTables[$room_id][$date])) {
                        $lockTables[$room_id][$date] = [];
                    }
                    $lockTables[$room_id][$date] = RoomTable::addTable($lockTables[$room_id][$date], $lock['id'], $lock['hours']);
                }
            } 
        }

        //对roomTable进行差值更新
        foreach ($lockTables as $room_id => $roomLockTables) {
            foreach ($roomLockTables as $date => $lockTable) {
                $roomTable = RoomService::getRoomTable($date, $room_id, true, false);
                $data = $roomTable->toArray(['locked']);
                if(json_encode($data['locked']) != json_encode($lockTable)){
                    $roomTable->locked = $lockTable;
                    $roomTable->useOptimisticLock = false;
                    $roomTable->save();

                    //清除缓存
                    TagDependency::invalidate(Yii::$app->cache, 'RoomTable'.'_'.$date.'_'.$room_id);
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
        $cacheKey = 'Lock'.'_'.$lock_id;
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存');

            $lock = Lock::findOne($lock_id);
            $data = $lock->toArray(['id', 'rooms', 'hours', 'start_date', 'end_date', 'status', 'data']);
            $data = array_merge($data, $data['data']);
            unset($data['data']);

            $cache->set($cacheKey, $data, 0, new TagDependency(['tags' => $cacheKey]));
            Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
    }


    /**
     * 批量查询锁(带缓存)
     *
     * @param array $lock_idList 房间锁id
     * @return array
     */
    public static function queryLocks($lock_idList, $useCache= true) {
        $cache = Yii::$app->cache;
        $result = [];
        $missList = [];
        foreach ($lock_idList as $lock_id) {
            $cacheKey = 'Lock'.'_'.$lock_id;
            $data = $cache->get($cacheKey);
            if ($data == null || !$useCache) {
                Yii::trace($cacheKey.':缓存失效', '数据缓存');
                $missList[] = $lock_id;
            } else {
                Yii::trace($cacheKey.':缓存命中', '数据缓存');
                $result[(string)$lock_id] = $data;
            }
        }
        if(count($missList) > 0) {
            foreach (Lock::find()
                ->where(['in', 'id', $missList])
                ->select(['id', 'rooms', 'hours', 'start_date', 'end_date', 'status', 'data'])
                ->asArray()->each(100) as $lock) {
                $lock['rooms'] = json_decode($lock['rooms'], true);
                $lock['hours'] = json_decode($lock['hours'], true);
                $lock = array_merge($lock, json_decode($lock['data'], true));
                unset($lock['data']);
                $result[(string)$lock['id']] = $lock;

                $cacheKey = 'Lock'.'_'.$lock['id'];
                $cache->set($cacheKey, $data, 0, new TagDependency(['tags' => $cacheKey]));
                Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
            }
        }

        return $result;
    }


    /**
     * 添加一个房间锁
     *
     * @param Lock $lock 房间锁
     * @return boolean
     */
    public static function addLock($lock) {
        $result = $lock->save();
        if (!$result) {
            Yii::error($lock->getErrors(), __METHOD__);
            throw new HdzxException('房间锁保存失败', Error::SAVE_LOCK);
        }
        TagDependency::invalidate(Yii::$app->cache, 'LockTable');
    }

    /**
     * 修改一个房间锁
     *
     * @param Lock $lock 房间锁
     * @return boolean
     */
    public static function editLock($lock) {
        $result = $lock->save();
        if (!$result) {
            Yii::error($lock->getErrors(), __METHOD__);
            throw new HdzxException('房间锁保存失败', Error::SAVE_LOCK);
        }
        TagDependency::invalidate(Yii::$app->cache, 'Lock'.'_'.$lock->id); 
        //读取解析lock的room和date，使其缓存失效
    }

    /**
     * 删除一个房间锁
     *
     * @param Lock $lock 房间锁
     * @return boolean
     */
    public static function deleteLock($lock) {
        $result = $lock->delete();
        if (!$result) {
            Yii::error($lock->getErrors(), __METHOD__);
            throw new HdzxException('房间锁删除失败', Error::SAVE_LOCK);
        }
        TagDependency::invalidate(Yii::$app->cache, 'Lock'.'_'.$lock->id);
        TagDependency::invalidate(Yii::$app->cache, 'LockTable');
        //读取解析lock的room和date，使其缓存失效
    }
}