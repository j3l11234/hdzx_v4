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

    //申请表
    protected $_ordered = [];
    //占用表
    protected $_used = [];
    //锁定表
    protected $_locked = [];



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
                'createdAtAttribute' => false,
                'updatedAtAttribute' => 'updated_at',
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
     * 
     * json转换
     */
    public function afterFind(){
        $this->_ordered = json_decode($this->ordered, true);
        $this->_used = json_decode($this->used, true);
        $this->_locked = json_decode($this->locked, true);
    }

    /**
     * @inheritdoc
     * 
     * json转换
     */
    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $this->ordered = json_encode($this->_ordered);
            $this->used = json_encode($this->_used);
            $this->locked = json_encode($this->_locked);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function fields() {
        return [
            'ordered' => function () {
                return $this->_ordered;
            },
            'used' => function () {
                return $this->_used;
            },
            'locked' => function () {
                return $this->_locked;
            },
        ];
    }

    /**
     * @inheritdoc
     */
    public function optimisticLock() {
        return 'ver';
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
     * @param string $name _ordered|_used|_locled
     * @param integer $id 插入的id
     * @param array $hours 插入的小时数组
     * @return null
     */
    protected function addTable($name, $id, $hours){
        if ($hours != null) {
            foreach ($hours as $hour) {
                if (isset($this->{$name}[$hour])) {
                    if (!in_array($id, $this->{$name}[$hour])) {
                        $this->{$name}[$hour][] = $id;
                    }
                } else {
                    $this->{$name}[$hour] = [$id];
                }
            }
        }     
    }

    /**
     * 从表移除一个id
     *
     * @param string $name _ordered|_used|_locled
     * @param integer $id 插入的id
     * @return null
     */
    protected function removeTable($name, $id) {
        foreach ($this->{$name} as $hour=>$hourTable) {
            $index = array_search($id,$hourTable);
            if ($index !== false) {
                array_splice($this->{$name}[$hour], $index, 1);
            }
        }
    }

    /**
     * 获取一个时段内的id
     *
     * @param string $name _ordered|_used|_locled
     * @param array $hours 查找的小时数组 为null则为全部
     * @return array idList
     */
    protected function getTable($name, $hours = null) {
        $idList = [];
        foreach ($this->{$name} as $hour=>$hourTable) {
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
        return $this->addTable('_ordered', $id, $hours);
    }

    /**
     * 从ordered表移除一个id
     *
     * @param integer $id 插入的id
     * @return null
     */
    public function removeOrdered($id) {
        return $this->removeTable('_ordered', $id);
    }

    /**
     * 从ordered读取一个时段数据
     * @param array $hours 查找的小时数组 为null则为全部
     * [1,2,3]
     * 
     * @return array
     */
    public function getOrdered($hours = null) {
        return $this->getTable('_ordered', $hours);
    }

    /**
     * 增加一个id到used表
     *
     * @param integer $id 插入的id
     * @param array $hours 插入的小时数组
     * @return null
     */
    public function addUsed($id, $hours) {
        return $this->addTable('_used', $id, $hours);
    }

    /**
     * 从used表移除一个id
     *
     * @param integer $id 插入的id
     * @return null
     */
    public function removeUsed($id) {
        return $this->removeTable('_used', $id);
    }
    
    /**
     * 从used读取一个时段数据
     * @param array $hours 查找的小时数组 为null则为全部
     * [1,2,3]
     * 
     * @return array
     */
    public function getUsed($hours = null) {
        return $this->getTable('_used', $hours);
    }

    /**
     * 增加一个id到ordered表
     *
     * @param integer $id 插入的id
     * @param array $hours 插入的小时数组
     * @return null
     */
    public function addLocked($id, $hours) {
        return $this->addTable('_locked', $id, $hours);
    }

    /**
     * 从ordered表移除一个id
     *
     * @param integer $id 插入的id
     * @return null
     */
    public function removeLocked($id) {
        return $this->removeTable('_locked', $id);
    }

    /**
     * 从ordered读取一个时段数据
     * @param array $hours 查找的小时数组 为null则为全部
     * [1,2,3]
     * 
     * @return array
     */
    public function getLocked($hours = null) {
        return $this->getTable('_locked', $hours);
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
            if(isset($this->_locked[$hour]) && sizeof($this->_locked[$hour]) > 0){
                $hourTable[$hour] = self::STATUS_LOCKED;
            }else if(isset($this->_used[$hour]) && sizeof($this->_used[$hour]) > 0){
                $hourTable[$hour] = self::STATUS_USED;
            }else if(isset($this->_ordered[$hour]) && sizeof($this->_ordered[$hour]) > 0){
                $hourTable[$hour] = self::STATUS_ORDERED;
            }else{
                $hourTable[$hour] = self::STATUS_FREE;
            }
        }
        return $hourTable;
    }
}
