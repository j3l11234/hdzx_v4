<?php
namespace backend\models;

use common\behaviors\ErrorBehavior;
use common\models\entities\User;
use common\models\entities\Order;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\models\services\RoomService;
use common\models\services\ApproveService;

use yii\base\Model;
use Yii;

/**
 * Signup form
 */
class ApproveQueryForm extends Model {
    public $start_date;
    public $end_date;
    public $status;
    public $type;

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
    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios['getApproveOrder'] = ['start_date', 'end_date', 'status', 'type'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['type'], 'required'],
            [['start_date', 'end_date', 'date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['start_date', 'end_date'], 'dateRangeValidator'],
            [['type'], 'in', 'range' => ['auto', 'manager', 'school',]],
        ];
    }

    function dateRangeValidator($attribute, $params) {
        $range = static::getDateRange();
        
        $date = strtotime($this->$attribute);   
        if($date < $range['start']  || $date > $range['end']){
            $this->addError($attribute, $attribute.'超出范围，只能查询前后一个月内的记录');
        }
    }

    public static function getDateRange(){
        $today = strtotime(date('Y-m-d', time()));
        $start = strtotime("-31 day",$today);
        $end = strtotime("+31 day",$today);

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    function getType($type) {
        switch ($type) {
            case 'auto':
                return ApproveService::TYPE_AUTO;
            case 'manager':
                return ApproveService::TYPE_MANAGER;
            case 'school':
                return ApproveService::TYPE_SCHOOL;
            default:
                break;
        }
    }

    /**
     * 查询房间使用表
     *
     * @return User|null the saved model or null if saving fails
     */
    public function getApproveOrder() {
        $user = Yii::$app->user->getIdentity()->getUser();
        $numType = $this->getType($this->type);
        $range = static::getDateRange();
        if(empty($this->start_date)){
            $this->start_date = date('Y-m-d', $range['start']);
        }
        if(empty($this->end_date)){
            $this->end_date = date('Y-m-d', $range['end']);
        }
        
        $data = ApproveService::queryApproveOrder($user, $numType, $this->start_date, $this->end_date);

        //解析roomTable，用于分析冲突
        $roomTables = [];
        foreach ($data['orders'] as $id => $order) {
            $room_id = $order['room_id'];
            $date = $order['date'];
            if(!isset($roomTables[$room_id])){
                $roomTables[$room_id] = [];
            }
            if (isset($roomTables[$room_id][$date])){
                continue;
            }

            $roomTable = RoomService::queryRoomTable($date, $room_id);
            unset($roomTable['locked']);
            unset($roomTable['hourTable']);
            unset($roomTable['chksum']);
            $roomTables[$room_id][$date] = $roomTable;
        }
        $data['roomTables'] = $roomTables;

        return $data;
    }
}
