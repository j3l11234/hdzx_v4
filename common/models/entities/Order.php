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
 * Order
 * 预约
 *
 * @property integer $id
 * @property date $date 申请日期
 * @property integer $room_id 房间id
 * @property json $hours 申请的小时
 * @property integer $user_id 申请id(如果是普通账号)
 * @property integer $dept_id 部门单位
 * @property integer $type 申请类型(普通申请 后台申请)
 * @property integer $status 申请状态
 * @property integer $submit_time 提交时间
 * @property json $data 申请具体数据
 * @property integer $issue_time 开门条发放时间
 * @property integer $updated_at
 * @property integer $ver
 */
class Order extends ActiveRecord {
    /**
     * 预约状态 初始化
     */
    const STATUS_INIT               = 1;
    /**
     * 预约状态 已通过
     */
    const STATUS_PASSED             = 2;
    /**
     * 预约状态 取消
     */
    const STATUS_CANCELED           = 3;
    /**
     * 预约状态 负责人待审批
     */
    const STATUS_MANAGER_PENDING    = 10;
    /**
     * 预约状态 负责人通过
     */
    const STATUS_MANAGER_APPROVED   = 11;
    /**
     * 预约状态 负责人驳回
     */
    const STATUS_MANAGER_REJECTED   = 12;
    /**
     * 预约状态 校团委待审批
     */
    const STATUS_SCHOOL_PENDING     = 11;
    /**
     * 预约状态 校团委通过
     */
    const STATUS_SCHOOL_APPROVED    = 2;
    /**
     * 预约状态 校团委驳回
     */
    const STATUS_SCHOOL_REJECTED    = 22;
    /**
     * 预约状态 琴房待审批
     */
    const STATUS_SIMPLE_PENDING       = 30;
    /**
     * 预约状态 琴房通过
     */
    const STATUS_SIMPLE_APPROVED      = 02;
    /**
     * 预约状态 琴房驳回
     */
    const STATUS_SIMPLE_REJECTED      = 32;

    /**
     * 预约类型 琴房审批预约
     */
    const TYPE_SIMPLE     = 1;
    /**
     * 预约状态 二级审批预约
     */
    const TYPE_TWICE    = 2;

    /**
     * 房间锁状态 预约
     */
    const ROOMTABLE_ORDERED = 01;
    /**
     * 房间锁状态 占用
     */
    const ROOMTABLE_USED = 02;
    /**
     * 房间锁状态 拒绝
     */
    const ROOMTABLE_REJECTED = 03;
    /**
     * 房间锁状态 失效
     */
    const ROOMTABLE_NONE = 00;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%order}}';
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
            ],[
                'class' => JsonBehavior::className(),
                'attributes' => ['hours', 'data'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'date', 'room_id', 'hours', 'user_id', 'type', 'dept_id', 'status', 'submit_time', 'data', 'issue_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function optimisticLock() {
        return 'ver';
    }

    /**
     * 通过日期和房间查找预约
     *
     * @param string $date 预约日期
     * @param integer $room_id 房间id
     * @param integer $asArray 结果是否为数组形式
     * @return static|null
     */
    public static function findByDateRoom($date, $room_id, $asArray = false) {
        $find = static::find()->where(['date' => $date, 'room_id' => $room_id]);
        if ($asArray){
            $find = $find->asArray();
        }

        return $find->all();
    }

    /**
     * 根据hours取得时间范围
     *
     * @param array $hours housrs
     * @return array {start_hour:8, end_hour: 21}
     */
    public static function hours2Range($hours) {
        $start_hour = -1;
        $end_hour = -1;
        foreach ($hours as $hour) {
            if ($start_hour === -1 || $start_hour > $hour) {
                $start_hour = $hour;
            }
            if ($end_hour === -1 || $end_hour < $hour) {
                $end_hour = $hour;
            }
        }
        return [
            'start_hour'    => $start_hour,
            'end_hour'      => $end_hour+1,
        ];
    }
    /**
     * 得到房间表状态
     *
     * @param integer $status 预约状态
     * @return static|null
     */
    public static function getRoomTableStatus($status) {
        $roomTableStatus = self::ROOMTABLE_NONE;
        if ($status == self::STATUS_MANAGER_PENDING ||
            $status == self::STATUS_MANAGER_APPROVED ||
            $status == self::STATUS_SCHOOL_PENDING ||
            $status == self::STATUS_SIMPLE_PENDING ) {

            $roomTableStatus = self::ROOMTABLE_ORDERED;
        } else if ($status == self::STATUS_PASSED ||
            $status == self::STATUS_SCHOOL_APPROVED ||
            $status == self::STATUS_SIMPLE_APPROVED) {

            $roomTableStatus = self::ROOMTABLE_USED;
        } else if ($status == self::STATUS_MANAGER_REJECTED ||
            $status == self::STATUS_SCHOOL_REJECTED ||
            $status == self::STATUS_SIMPLE_REJECTED) {
            $roomTableStatus = self::ROOMTABLE_REJECTED;
        }
        
        return $roomTableStatus;
    }
}
