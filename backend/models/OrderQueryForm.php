<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use common\behaviors\ErrorBehavior;
use common\models\entities\User;
use common\models\entities\Order;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\services\RoomService;
use common\services\OrderService;
use common\services\LockService;

/**
 * OrderQuery form
 */
class OrderQueryForm extends Model {
    public $start_date;
    public $end_date;
    public $student_no;

    /**
     * 场景 查询开门条
     */
    const SCENARIO_GET_ISSUE       = 'getIssueOrders';

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            ErrorBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios[static::SCENARIO_GET_ISSUE] = ['start_date', 'end_date', 'student_no'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['student_no'], 'required'],
            [['student_no'], 'match', 'pattern' => '/^\d{8}$/'],
            [['start_date', 'end_date', 'date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['start_date', 'end_date'], 'dateRangeValidator'],
        ];
    }


    function dateRangeValidator($attribute, $params) {
        $range = static::getDefaultDateRange();
        
        $date = strtotime($this->$attribute);   
        if($date < $range['start']  || $date > $range['end']){
            $this->addError($attribute, $attribute.'超出范围，只能查询当日起至后一个月内的记录');
        }
    }

    public static function getDefaultDateRange() {
        $today = strtotime(date('Y-m-d', time()));
        $start = $today;
        $end = strtotime("+1 month",$today);

        return [
            'start' => $start,
            'end' => $end
        ];
    }
    
    
    /**
     * 根据学号查询开门条记录
     *
     * @return Mixed|null 返回数据
     */
    public function getIssueOrders() {
        $dateRange = static::getDefaultDateRange();
        $startDate = !empty($this->start_date) ? $this->start_date : date('Y-m-d',$dateRange['start']);
        $endDate = !empty($this->end_date) ? $this->end_date : date('Y-m-d', $dateRange['end']);


        $user = Yii::$app->user->getIdentity()->getUser();

        $data = OrderService::queryIssueOrders($user, $this->student_no, $startDate, $endDate);

        return array_merge($data, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'start_date' => '开始日期',
            'end_date' => '结束日期',
            'student_no' => '学号'
        ];
    }
}
