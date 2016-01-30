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
 * 房间
 *
 * @property integer $id
 * @property integer $number 房间号
 * @property string $name 房间名
 * @property integer $type 房间类型
 * @property string $data
 * ```php
 * $data = [
 *     'secure' => 1, //是否需要填写安全信息
 *     'by_week' => 1, //按周开放
 *     'max_before' => 30, //最大提前
 *     'min_before' => 5, //最小提前申请
 *     'max_hour' => 2, //单次申请最长时间
 * ]
 * ```  
 * @property integer $align 排序依据
 * @property integer $status 房间状态
 * @property integer $created_at
 * @property integer $updated_at
 */
class Room extends ActiveRecord {
    /**
     * 操作类型 自动审批类房间
     */
    const TYPE_AUTO     = 01;
    /**
     * 操作类型 手动审批类房间
     */
    const TYPE_TWICE        = 02;
    /**
     * 操作类型 负责人手动审批 校级自动审批
     */
    const TYPE_SCHOOL_AUTO  = 03;

    /**
     * 房间状态 关闭
     */
    const STATUS_CLOSE  = 00;
    /**
     * 房间状态 开放申请
     */
    const STATUS_OPEN   = 01;

    protected $_data = [];

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%room}}';
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
     * 得到操作信息
     *
     * @return array 操作信息
     */
    public function getRoomData(){
        return $this->_data;
    }

    /**
     * 写入操作信息
     *
     * @param array 操作信息
     */
    public function setRoomData($data){
        $this->_data = $data;
    }

    /**
     * 验证日期是否在可申请范围内
     *
     * @param string $date 测试日期 形如'2015-12-15'
     * @param int $now 参考时间，默认为当前时间
     * @return boolean 是否可以申请
     */
    public function checkOpen($date, $now = null) {
        $date = strtotime($date);
        $range = $this->getDateRange($now);
        
        return ($date >= $range['start'] && $date <= $range['end']);
    }

    /**
     * 得到允许申请的日期
     *
     * @param int $now 参考时间，默认为当前时间
     * @return array 返回的格式
     * [
     *      'start' => $limitStart,
     *      'end' => $limitEnd
     * ]
     */
    public function getDateRange($now = null) {
        $now = $now === null ? time() : $now;

        $max_before = $this->_data['max_before'];
        $min_before = $this->_data['min_before'];

        $weekDay = date('w', $now);
        $month = date("m", $now);
        $year = date("Y", $now);
        $day = date("d", $now);

        $limitStart = mktime(0, 0, 0, $month, $day + $min_before, $year);

        if($this->_data['by_week'] == 1) {
            $max_before -= $max_before % 7;
            $limitEnd = mktime(23, 59, 59, $month, $day - $weekDay + 7 + $max_before, $year);
        } else {
            $limitEnd = mktime(23, 59, 59, $month, $day + $max_before, $year);
        }

        return [
            'start' => $limitStart,
            'end' => $limitEnd
        ];
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
