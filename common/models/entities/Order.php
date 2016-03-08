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
 * Order
 * 预约
 *
 * 预约状态是有重复的，比如校级审批通过和自动审批通过都是审批通过。
 *
 * @property integer $id
 * @property date $date 申请日期
 * @property integer $room_id 房间id
 * @property string $hours 申请的小时
 * @property integer $user_id 申请id(如果是普通账号)
 * @property integer $dept_id 部门id
 * @property integer $type 申请类型(普通申请 后台申请)
 * @property integer $status 申请状态
 * @property integer $submit_time 提交时间
 * @property string $data 申请具体数据
 * @property integer $issue_time 开门条发放时间
 * @property integer $updated_at
 * @property integer $ver
 */
class Order extends ActiveRecord {
    /**
     * 预约状态 初始化
     */
    const STATUS_INIT               = 0x01;
    /**
     * 预约状态 已通过
     */
    const STATUS_PASSED             = 0x02;
    /**
     * 预约状态 取消
     */
    const STATUS_CANCELED           = 0x03;
    /**
     * 预约状态 负责人待审批
     */
    const STATUS_MANAGER_PENDING    = 0x10;
    /**
     * 预约状态 负责人通过
     */
    const STATUS_MANAGER_APPROVED   = 0x11;
    /**
     * 预约状态 负责人驳回
     */
    const STATUS_MANAGER_REJECTED   = 0x12;
    /**
     * 预约状态 校团委待审批
     */
    const STATUS_SCHOOL_PENDING     = 0x11;
    /**
     * 预约状态 校团委通过
     */
    const STATUS_SCHOOL_APPROVED    = 0x02;
    /**
     * 预约状态 校团委驳回
     */
    const STATUS_SCHOOL_REJECTED    = 0x22;
    /**
     * 预约状态 自动待审批
     */
    const STATUS_AUTO_PENDING       = 0x30;
    /**
     * 预约状态 自动通过
     */
    const STATUS_AUTO_APPROVED      = 0x02;
    /**
     * 预约状态 自动驳回
     */
    const STATUS_AUTO_REJECTED      = 0x32;

    /**
     * 预约类型 自动审批预约
     */
    const TYPE_AUTO     = 1;
    /**
     * 预约状态 二级审批预约
     */
    const TYPE_TWICE    = 2;

    protected $_hours = [];
    protected $_data = [];

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
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date', 'room_id', 'dept_id', 'type', 'status'], 'required'],
            [['hours', 'data'], 'safe'],
            [['room_id', 'user_id', 'dept_id', 'submit_time', 'issue_time'], 'integer'],
            [['status'], 'in', 'range' => [
                self::STATUS_INIT, self::STATUS_PASSED, self::STATUS_CANCELED,
                self::STATUS_MANAGER_PENDING, self::STATUS_MANAGER_APPROVED, self::STATUS_MANAGER_REJECTED,
                self::STATUS_SCHOOL_PENDING, self::STATUS_SCHOOL_APPROVED, self::STATUS_SCHOOL_REJECTED,
                self::STATUS_AUTO_PENDING, self::STATUS_AUTO_APPROVED, self::STATUS_AUTO_REJECTED]],
            [['type'], 'in', 'range' => [self::TYPE_AUTO, self::TYPE_TWICE]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function optimisticLock() {
        return 'ver';
    }

    /**
     * @inheritdoc
     * 
     * json转换
     */
    public function afterFind(){
        $this->_hours = json_decode($this->hours, true);
        $this->_data = json_decode($this->data, true);
    }

    /**
     * @inheritdoc
     * 
     * json转换
     */
    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $this->hours = json_encode($this->_hours);
            $this->data = json_encode($this->_data);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function fields() {
        $fields = parent::fields();

        $fields['hours'] = function () {
            return $this->_hours;
        };
        $fields['data'] = function () {
            return $this->_data;
        };

        return $fields;
    }

    public static function getCacheKey($order_id){
        return 'Order'.'_'.$order_id;
    }

    /**
     * 得到预约的小时数组
     *
     * @return array 小时
     */
    public function getHours(){
        return $this->_hours;
    }

    /**
     * 写入预约的小时数组
     *
     * @param array 小时
     */
    public function setHours($hours){
        $this->_hours = $hours;
    }

    /**
     * 得到预约信息
     *
     * @return array 预约信息
     */
    public function getOrderData(){
        return $this->_data;
    }

    /**
     * 写入预约信息
     *
     * @param array 预约信息
     */
    public function setOrderData($data){
        $this->_data = $data;
    }

    /**
     * 通过日期和房间查找预约
     *
     * @param string $date 预约日期
     * @param integer $room_id 房间id
     * @param integer $asArray 结果是否为数组形式
     * @return static|null
     */
    public static function findByDateRoom($date,$room_id,$asArray = false)
    {
        $find = static::find(['date' => $date, 'room_id' => $room_id]);
        if ($asArray){
            $find = $find->asArray();
        }

        return $find->all();
    }
}
