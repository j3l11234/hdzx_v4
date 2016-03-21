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
 * 房间锁
 *
 * @property integer $id
 * @property json $rooms 房间
 * @property json $hours 锁定申请的小时
 * @property date $start_date 开始时间
 * @property date $end_date 结束时间
 * @property int $status 房间锁状态
 * @property json $data
 * ```php
 * $data = [
 *     'loop_type' => 1, //房间锁类型
 *     'loop_day' => 1, //循环日，每周第几天 每月第几天
 *     'title' => 中午休息, //房间锁标题
 *     'comment' => 中午休息, //房间锁备注
 * ]
 * ```  
 * @property integer $created_at
 * @property integer $updated_at
 */
class Lock extends ActiveRecord {
    /**
     * 循环类型 按日循环
     */
    const LOOK_DAY      = 0x01;
    /**
     * 循环类型 按周循环
     */
    const LOOK_WEEK     = 0x02;
    /**
     * 循环类型 按月循环
     */
    const LOOK_MONTH    = 0x03;

    /**
     * 房间锁状态 启用
     */
    const STATUS_ENABLE  = 0x01;
    /**
     * 房间锁状态 禁用
     */
    const STATUS_DISABLE   = 0x02;


    protected $_data = [];
    protected $_rooms = [];
    protected $_hours = [];

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%lock}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     * 
     * json转换
     */
    public function afterFind() {
        $this->_data = json_decode($this->data, true);
        $this->_rooms = json_decode($this->rooms, true);
        $this->_hours = json_decode($this->hours, true);
    }

    /**
     * @inheritdoc
     * 
     * json转换
     */
    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $this->data = json_encode($this->_data);
            $this->rooms = json_encode($this->_rooms);
            $this->hours = json_encode($this->_hours);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['number', 'name', 'type','status'], 'required'],
            [['number', 'align'], 'integer'],
            [['data', 'align'], 'safe'],
            [['type'], 'in', 'range' => [self::TYPE_AUTO, self::TYPE_TWICE]],
            [['status'], 'in', 'range' => [self::STATUS_CLOSE, self::STATUS_OPEN]],
        ];
    }

    /**
     * 得到房间锁额外数据
     *
     * @return array 操作信息
     */
    public function getLockData(){
        return $this->_data;
    }

    /**
     * 写入房间锁额外数据
     *
     * @param array 操作信息
     */
    public function setLockData($data){
        $this->_data = $data;
    }

    /**
     * 得到房间锁定的的日期(静态)
     *
     * @param int $loop_type 房间锁类型
     * @param int $loop_day 循环日
     * @param date $start_date 开始日期
     * @param date $end_date 结束日期
     * @return Array 日期List
     */
    public static function getDateList($loop_type, $loop_day, $start_date = null, $end_date = null) {
        $startDateTs = strtotime($start_date);
        $endDateTs = strtotime($end_date);

        //根据循环确定开始和结束
        if ($loop_type == static::LOOK_DAY) {
            $startTs = $startDateTs;
            $endTs = $endDateTs;
            $step = '1 days';
        } else if ($loop_type == static::LOOK_WEEK) {
            $startTs = strtotime((($loop_day - date('w', $startDateTs) + 7) % 7).' days', $startDateTs);
            $endTs = $endDateTs;
            $step = '1 weeks';
        } else if ($loop_type == static::LOOK_MONTH) {
            $startTs = strtotime(($loop_day - $day).' days'.($loop_day < $day ? ' 1 months' : ''), $startDateTs);
            $endTs = $endDateTs;
            $step = '1 months';
        }

        $dateList = [];
        for ($iTs=$startTs; $iTs <= $endTs; $iTs = strtotime($step, $iTs)) {
            $iDate = date('Y-m-d', $iTs);
            $dateList[] = $iDate;
        }
        return $dateList;
    }

    /**
     * 得到房间锁定的的日期
     *
     * @param date $start_date 开始日期
     * @param date $end_date 结束日期
     * @return Array 日期List
     */
    public function getDateListSelf($start_date = null, $end_date = null) {
        if(!empty($start_date)) {
            $start_date = strtotime($start_date) > strtotime($this->start_date) ? $start_date : $this->start_date;
        }else{
            $start_date = $this->start_date;
        }

        if(!empty($end_date)) {
            $end_date = strtotime($end_date) < strtotime($this->end_date) ? $end_date : $this->end_date;
        }else{
            $end_date = $this->end_date;
        }

        return self::getDateList($this->_data['loop_type'], $this->_data['loop_day'], $start_date, $end_date);
    }

    public static function getCacheKey($lock_id){
        return 'Lock'.'_'.$lock_id;
    }

    /**
     * @inheritdoc
     */
    public function fields() {
        $fields = parent::fields();
        $fields['data'] = function () {
            return $this->_data;
        };
        $fields['rooms'] = function () {
            return $this->_rooms;
        };
        $fields['hours'] = function () {
            return $this->_hours;
        };

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'number' => '房间号',
            'name' => '房间名',
            'type' => '房间类型',
            'info' => 'Info',
            'align' => 'Align',
            'open' => 'Open',
            'updated_at' => 'Update Time',
        ];
    }
}
