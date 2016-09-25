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
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\services\RoomService;

/**
 * 房间锁相关服务类
 */
class LockService extends Component {

    /**
     * 查询锁列表
     * 批量查询锁(带缓存)
     *
     * @param boolean $onlyId 仅获取id
     * @param $useCache 是否使用缓存(默认为是)
     * @return Array 如果onlyId未真，返回lock_id的列表，否则返回Lock的Map
     */
    public static function getLockList($onlyId = FALSE, $useCache = TRUE) {
        $lockList;

        //读取缓存
        $cacheMiss;
        if ($useCache) {
            $cacheKey = 'LockList';
            $cacheData = Yii::$app->cache->get($cacheKey);
            if ($cacheData == null) {
                Yii::trace($cacheKey.':缓存失效', '数据缓存');
                $cacheMiss = TRUE;
            } else {
                Yii::trace($cacheKey.':缓存命中', '数据缓存');
                $lockList = $cacheData;
                $cacheMiss = FALSE;
            }
        } else {
            $cacheMiss = TRUE;
        }
        if($cacheMiss) {
            $locks = Lock::find()->select(['id'])->asArray()->all();
            $lock_ids = array_column($locks, 'id');
            $locks = static::getLocks($lock_ids, $useCache);

            $lockList = [
                'lockList' => array_keys($locks),
                'locks' => $locks,
            ];

            //写入缓存
            $cacheKey = 'LockList';
            Yii::$app->cache->set($cacheKey, $lockList, 
                Yii::$app->params['cache.duration'],
                new TagDependency(['tags' => ['Lock']]));
            Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
        }

        if ($onlyId) {
            return $lockList['roomList'];
        }
        return $lockList;
    }

    /**
     * 批量查询锁(带缓存)
     *
     * @param Array $lock_ids lock_id的列表
     * @param $useCache 是否使用缓存(默认为是)
     * @return Array locks的Map
     */
    public static function getLocks($lock_ids, $useCache = TRUE) {
        $locks = [];

        //读取缓存
        $cacheMisses;
        if ($useCache) {
            $cacheMisses = [];
            foreach ($lock_ids as $lock_id) {
                $cacheKey = 'Lock_'.$lock_id;
                $cacheData = Yii::$app->cache->get($cacheKey);
                if ($cacheData == null) {
                    Yii::trace($cacheKey.':缓存失效', '数据缓存');
                    $cacheMisses[] = $lock_id;
                } else {
                    Yii::trace($cacheKey.':缓存命中', '数据缓存');
                    $locks[$lock_id] = $cacheData;
                }
            }
        } else {
            $cacheMisses = $lock_ids;
        }

        //获取剩下数据(缓存miss的)
        if(count($cacheMisses) > 0) {
            $cacheNews = [];
            foreach (Lock::find()
                ->where(['in', 'id', $cacheMisses])
                ->select(['id', 'rooms', 'hours', 'start_date', 'end_date', 'status', 'data'])
                ->asArray()->each(100) as $lock) {
                $lock['rooms'] = json_decode($lock['rooms'], TRUE);
                $lock['hours'] = json_decode($lock['hours'], TRUE);
                $lock = array_merge($lock, json_decode($lock['data'], TRUE));
                unset($lock['data']);
                $locks[$lock['id']] = $lock;
                $cacheNews[] = $lock['id'];
            }

            if ($useCache) {
                //写入缓存
                Yii::beginProfile('Lock写入缓存', '数据缓存');
                foreach ($cacheNews as $lock_id) {
                    $lock = $locks[$lock_id];
                    $cacheKey = 'Lock_'.$lock['id'];
                    Yii::$app->cache->set($cacheKey, $lock,
                        Yii::$app->params['cache.duration'],
                        new TagDependency(['tags' => [$cacheKey, 'Lock']]));
                    Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
                }
                Yii::endProfile('Lock写入缓存', '数据缓存');
            }
        }

        return $locks;
    }


    /**
     * 批量查询锁定表
     *
     * @param Array $dateRooms [日期房间]的数组
     * @return Array lockTable的Map
     */
    public static function getLockTables($dateRooms) {
        $lockTables = [];

        //解析得出时间、房间范围
        $dates = [];
        $room_ids = [];
        foreach ($dateRooms as $dateRoom) {
            $dateRoomSplit = explode('_', $dateRoom);
            $dates[$dateRoomSplit[0]] = TRUE;
            $room_ids[$dateRoomSplit[1]] = TRUE;

            $lockTables[$dateRoom] = [];
        }
        $room_ids = array_keys($room_ids);
        $dates = array_keys($dates);

        $locks = Lock::find()->select(['id'])->where(['status' => Lock::STATUS_ENABLE])->asArray()->all();
        $lock_ids = array_column($locks, 'id');
        $locks = static::getLocks($lock_ids, FALSE);
        foreach ($locks as $lock_id => $lock) {
            $dateList = Lock::getDateList($lock['loop_type'], $lock['loop_day'], $lock['start_date'], $lock['end_date']);
            //计算交集提升效率
            $_dates = array_intersect($dates, $dateList);
            $_room_ids = array_intersect($room_ids, $lock['rooms']);

            foreach ($_dates as $date) {
                foreach ($_room_ids as $room_id) {
                    $dateRoom = $date.'_'.$room_id;
                    if(!in_array($dateRoom, $dateRooms)){
                        continue;
                    }
                    $lockTables[$dateRoom] = RoomTable::addTable($lockTables[$dateRoom], $lock['id'], $lock['hours']);
                }
            }
        }

        return $lockTables;
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
        TagDependency::invalidate(Yii::$app->cache, 'Lock_'.$lock->id); 
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
        TagDependency::invalidate(Yii::$app->cache, 'Lock_'.$lock->id);
    }
}