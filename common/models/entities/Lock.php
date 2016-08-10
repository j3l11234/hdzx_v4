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
    const LOOP_DAY      = 1;
    /**
     * 循环类型 按周循环
     */
    const LOOP_WEEK     = 2;
    /**
     * 循环类型 按月循环
     */
    const LOOP_MONTH    = 3;

    /**
     * 房间锁状态 启用
     */
    const STATUS_ENABLE  = 1;
    /**
     * 房间锁状态 禁用
     */
    const STATUS_DISABLE   = 2;


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
            ],[
                'class' => JsonBehavior::className(),
                'attributes' => ['rooms', 'hours', 'data'],
            ],
        ];
    }


    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'rooms', 'hours', 'start_date', 'end_date', 'status', 'data'], 'safe'],
        ];
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
    public static function getDateList($loop_type, $loop_day, $start_date, $end_date) {
        $startDateTs = strtotime($start_date);
        $endDateTs = strtotime($end_date);

        $dateList = [];
        if ($loop_type == static::LOOP_DAY) {
            for ($iTs=$startDateTs; $iTs <= $endDateTs; $iTs = strtotime('1 days', $iTs)) {
                $iDate = date('Y-m-d', $iTs);
                $dateList[] = $iDate;
            }
        } else if ($loop_type == static::LOOP_WEEK) {
            for ($iTs=$startDateTs; $iTs <= $endDateTs; $iTs = strtotime('1 days', $iTs)) {
                if(date('w', $iTs) == $loop_day){
                    $iDate = date('Y-m-d', $iTs);
                    $dateList[] = $iDate;
                }
            }
        } else if ($loop_type == static::LOOP_MONTH) {
            for ($iTs=$startDateTs; $iTs <= $endDateTs; $iTs = strtotime('1 days', $iTs)) {
                if(date('d', $iTs) == $loop_day){
                    $iDate = date('Y-m-d', $iTs);
                    $dateList[] = $iDate;
                }
            }
        }
        return $dateList;
    }
}
