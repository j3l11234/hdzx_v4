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
 * 预约操作类
 *
 * @property integer $id
 * @property integer $order_id
 * @property integer $user_id
 * @property integer $time
 * @property integer $type
 * @property integer $data
 * ```php
 * //data格式比较自由
 * $data = [
 *     'operator' => 张三, //操作人名字
 *     'studentn_no' => 12301120, //操作人学号(如果是学生)
 *     'commemt' => '提交预约', //操作备注
 * ]
 * ``` 
 */
class OrderOperation extends ActiveRecord {
    /**
     * 操作类型 提交
     */
    const TYPE_SUBMIT           = 00;
    /**
     * 操作类型 修改预约时间
     */
    const TYPE_CHANGE_HOUR      = 01;
    /**
     * 操作类型 取消
     */
    const TYPE_CANCEL           = 02;
    /**
     * 操作类型 负责人审批通过
     */
    const TYPE_MANAGER_APPROVE = 10;
    /**
     * 操作类型 负责人审批驳回
     */
    const TYPE_MANAGER_REJECT = 11;
    /**
     * 操作类型 负责人撤回审批
     */
    const TYPE_MANAGER_REVOKE   = 12;
    /**
     * 操作类型 校团委审批通过
     */
    const TYPE_SCHOOL_APPROVE  = 20;
    /**
     * 操作类型 校团委审批驳回
     */
    const TYPE_SCHOOL_REJECT  = 21;
    /**
     * 操作类型 校团委撤回审批
     */
    const TYPE_SCHOOL_REVOKE    = 22;
      /**
     * 操作类型 自动审批通过
     */
    const TYPE_AUTO_APPROVE    = 30;
    /**
     * 操作类型 自动审批驳回
     */
    const TYPE_AUTO_REJECT    = 31;
    /**
     * 操作类型 自动审批撤回
     */
    const TYPE_AUTO_REVOKE      = 32;

    protected $_data = [];

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%order_op}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['order_id', 'user_id', 'type'], 'required'],
            [['data'], 'safe'],
            [['order_id', 'user_id'], 'integer'],
            [['type'], 'in', 'range' => [
                self::TYPE_SUBMIT, self::TYPE_CHANGE_HOUR, self::TYPE_CANCEL, 
                self::TYPE_MANAGER_APPROVE, self::TYPE_MANAGER_REJECT, self::TYPE_MANAGER_REVOKE, 
                self::TYPE_SCHOOL_APPROVE, self::TYPE_SCHOOL_REJECT, self::TYPE_SCHOOL_REVOKE, 
                self::TYPE_AUTO_APPROVE, self::TYPE_AUTO_REJECT, self::TYPE_AUTO_REVOKE]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'time',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     * 
     * json转换
     */
    public function afterFind(){
        $this->_data = json_decode($this->data, true);
    }

    /**
     * @inheritdoc
     * 
     * json转换
     */
    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $this->data = json_encode($this->_data);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 得到操作信息
     *
     * @return array 操作信息
     */
    public function getOpData(){
        return $this->_data;
    }

    /**
     * 写入操作信息
     *
     * @param array 操作信息
     */
    public function setOpData($data){
        $this->_data = $data;
    }
    
    /**
     * @inheritdoc
     */
    public function fields() {
        $fields = parent::fields();
        $fields['data'] = function () {
            return $this->_data;
        };

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'user_id' => 'User ID',
            'student_id' => 'Student ID',
            'time' => 'Time',
            'type' => 'Type',
            'data' => 'Data',
        ];
    }
}
