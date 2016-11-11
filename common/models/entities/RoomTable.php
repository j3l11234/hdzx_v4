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
    const STATUS_FREE       = 1;
    /**
     * 时间状态 被预约
     */
    const STATUS_ORDERED    = 2;
    /**
     * 时间状态 被占用
     */
    const STATUS_USED       = 3;
    /**
     * 时间状态 锁定
     */
    const STATUS_LOCKED     = 4;

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
                'attributes' => ['ordered', 'used', 'rejected', 'locked'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'date', 'room_id', 'ordered', 'used', 'rejected', 'locked'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function optimisticLock() {
        return $this->useOptimisticLock ? 'ver' : null;
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
        return static::findOne($date.'_'.$room_id);
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
        if (!is_array($table)) {
            $table = [];
        }

        if ($hours != NULL) {
            $hours = array_intersect($hours, Yii::$app->params['order.hours']);
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
        if (!is_array($table)) {
            $table = [];
        }
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
     * @param array $exclude 排除列表
     * @return array idList
     */
    public static function getTable($table, $hours = null, $exclude = null) {
        if (!is_array($table)) {
            $table = [];
        }
        $idList = [];
        foreach ($table as $hour=>$hourTable) {
            if ($hours != null && !in_array($hour, $hours)) {
                continue;
            }
            foreach ($hourTable as $id) {
                if ($exclude != null && in_array($id, $exclude)) {
                    continue;
                }
                $idList[$id] = true;
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
     * 增加一个id到rejected表
     *
     * @param integer $id 插入的id
     * @param array $hours 插入的小时数组
     * @return null
     */
    public function addRejected($id, $hours) {
        $this->rejected = self::addTable($this->rejected, $id, $hours);
    }

    /**
     * 从rejected表移除一个id
     *
     * @param integer $id 插入的id
     * @return null
     */
    public function removeRejected($id) {
        $this->rejected = self::removeTable($this->rejected, $id);
    }

    /**
     * 从rejected读取一个时段数据
     * @param array $hours 查找的小时数组 为null则为全部
     * [1,2,3]
     * 
     * @return array
     */
    public function getRejected($hours = null) {
        return self::getTable($this->rejected, $hours);
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
    public static function getHourTable($ordered, $used, $locked, $hours = NULL) {
        if ($hours === NULL) {
            $hours = Yii::$app->params['order.hours'];
        }  
        $hourTable = [];

        foreach ($hours as $hour) {
            if(isset($locked[$hour]) && sizeof($locked[$hour]) > 0){
                $hourTable[$hour] = self::STATUS_LOCKED;
            }else if(isset($used[$hour]) && sizeof($used[$hour]) > 0){
                $hourTable[$hour] = self::STATUS_USED;
            }else if(isset($ordered[$hour]) && sizeof($ordered[$hour]) > 0){
                $hourTable[$hour] = self::STATUS_ORDERED;
            }else{
                $hourTable[$hour] = self::STATUS_FREE;
            }
        }
        return $hourTable;
    }

    public function getInsertData($attributes = null) {
        if (!$this->beforeSave(true)) {
            return NULL;
        }
        $values = $this->toArray($attributes);
        $this->afterSave(true, $attributes);
        return $values;
    }
}
