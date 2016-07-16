<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\entities;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\behaviors\JsonBehavior;

/**
 * RoomTable
 * 房间状态表
 *
 * @property integer $id
 * @property date $date
 * @property integer $room_id
 * @property string $ordered
 * @property string $used
 * @property string $locked
 * @property integer $updated_at
 * @property integer $ver
 */
class RoomTable extends ActiveRecord {
    /**
     * 时间状态 可用
     */
    const STATUS_FREE       = 0x01;
    /**
     * 时间状态 被预约
     */
    const STATUS_ORDERED    = 0x02;
    /**
     * 时间状态 被占用
     */
    const STATUS_USED       = 0x03;
    /**
     * 时间状态 锁定
     */
    const STATUS_LOCKED     = 0x04;

    public $useOptimisticLock = true;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%room_table}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],[
                'class' => JsonBehavior::className(),
                'attributes' => ['ordered', 'used', 'locked'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date', 'room_id'], 'required'],
            [['ordered', 'used', 'locked'], 'safe'],
            [['room_id'], 'integer'], 
        ];
    }

    /**
     * @inheritdoc
     */
    public function optimisticLock() {
        return $this->useOptimisticLock ? 'ver' : null;
    }

    public static function getCacheKey($date, $room_id){
        return 'RoomTable'.'_'.$date.'_'.$room_id;
    }

    /**
     * 通过日期和房间查找时间表
     *
     * @param string $date 日期
     * @param integer $room_id 房间id
     * @return static|null
     */
    public static function findByDateRoom($date, $room_id)  
    {
        return static::findOne(['date' => $date, 'room_id' => $room_id]);
    }

    /**
     * 增加一个id到表
     *
     * @param minxed $table 数据表
     * @param integer $id 插入的id
     * @param array $hours 插入的小时数组
     * @return null
     */
    public static function addTable($table, $id, $hours){
        if ($hours != null) {
            foreach ($hours as $hour) {
                $hour = (string)$hour;
                if (isset($table[$hour])) {
                    if (!in_array($id, $table[$hour])) {
                        $table[$hour][] = $id;
                    }
                } else {
                    $table[$hour] = [$id];
                }
            }
        }
        return $table;
    }

    /**
     * 从表移除一个id
     *
     * @param minxed $table 数据表
     * @param integer $id 插入的id
     * @return null
     */
    public static function removeTable($table, $id) {
        foreach ($table as &$idList) {
            $index = array_search($id, $idList);
            if ($index !== false) {
                array_splice($idList, $index, 1);
            }
        }
        return $table;
    }

    /**
     * 获取一个时段内的id(静态)
     *
     * @param minxed $table 数据表
     * @param array $hours 查找的小时数组 为null则为全部
     * @return array idList
     */
    public static function getTable($table, $hours = null) {
        $idList = [];
        foreach ($table as $hour=>$hourTable) {
            if ($hours == null || in_array($hour, $hours)){
                foreach ($hourTable as $id) {
                    $idList[$id] = true;
                }
            }
        }
        $idList = array_keys($idList);
        return $idList;
    }


    /**
     * 增加一个id到ordered表
     *
     * @param integer $id 插入的id
     * @param array $hours 插入的小时数组
     * @return null
     */
    public function addOrdered($id, $hours) {
        $this->ordered = self::addTable($this->ordered, $id, $hours);
    }

    /**
     * 从ordered表移除一个id
     *
     * @param integer $id 插入的id
     * @return null
     */
    public function removeOrdered($id) {
        $this->ordered = self::removeTable($this->ordered, $id);
    }

    /**
     * 从ordered读取一个时段数据
     * @param array $hours 查找的小时数组 为null则为全部
     * [1,2,3]
     * 
     * @return array
     */
    public function getOrdered($hours = null) {
        return self::getTable($this->ordered, $hours);
    }

    /**
     * 增加一个id到used表
     *
     * @param integer $id 插入的id
     * @param array $hours 插入的小时数组
     * @return null
     */
    public function addUsed($id, $hours) {
        $this->used = self::addTable($this->used, $id, $hours);
    }

    /**
     * 从used表移除一个id
     *
     * @param integer $id 插入的id
     * @return null
     */
    public function removeUsed($id) {
        $this->used = self::removeTable($this->used, $id);
    }
    
    /**
     * 从used读取一个时段数据
     * @param array $hours 查找的小时数组 为null则为全部
     * [1,2,3]
     * 
     * @return array
     */
    public function getUsed($hours = null) {
        return self::getTable($this->used, $hours);
    }

    /**
     * 增加一个id到ordered表
     *
     * @param integer $id 插入的id
     * @param array $hours 插入的小时数组
     * @return null
     */
    public function addLocked($id, $hours) {
        $this->locked = self::addTable($this->locked, $id, $hours);
    }

    /**
     * 从ordered表移除一个id
     *
     * @param integer $id 插入的id
     * @return null
     */
    public function removeLocked($id) {
        $this->locked = self::removeTable($this->locked, $id);
    }

    /**
     * 从ordered读取一个时段数据
     * @param array $hours 查找的小时数组 为null则为全部
     * [1,2,3]
     * 
     * @return array
     */
    public function getLocked($hours = null) {
        return self::getTable($this->locked, $hours);
    }

    /**
     * 生成小时表
     * @param array $hours 查找的小时数组
     * [1,2,3]
     * 
     * @return array
     */
    public function getHourTable($hours) {
        $hourTable = [];

        foreach ($hours as $hour) {
            if(isset($this->locked[$hour]) && sizeof($this->locked[$hour]) > 0){
                $hourTable[$hour] = self::STATUS_LOCKED;
            }else if(isset($this->used[$hour]) && sizeof($this->used[$hour]) > 0){
                $hourTable[$hour] = self::STATUS_USED;
            }else if(isset($this->ordered[$hour]) && sizeof($this->ordered[$hour]) > 0){
                $hourTable[$hour] = self::STATUS_ORDERED;
            }else{
                $hourTable[$hour] = self::STATUS_FREE;
            }
        }
        return $hourTable;
    }
}
