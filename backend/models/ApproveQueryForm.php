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
use common\services\ApproveService;

/**
 * ApproveQuery form
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
            [['start_date', 'end_date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['type'], 'in', 'range' => ['auto', 'manager', 'school',]],
        ];
    }

    public static function getDefaultDateRange(){
        $today = strtotime(date('Y-m-d', time()));
        $start = $today;
        $end = strtotime("+1 month",$today);

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    function getType($type) {
        switch ($type) {
            case 'auto':
                return ApproveService::TYPE_SIMPLE;
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
        $dateRange = static::getDefaultDateRange();
        $startDate = !empty($this->start_date) ? $this->start_date : date('Y-m-d',$dateRange['start']);
        $endDate = !empty($this->end_date) ? $this->end_date : date('Y-m-d', $dateRange['end']);

        $user = Yii::$app->user->getIdentity()->getUser();
        $numType = $this->getType($this->type);

        if( strtotime($endDate) -strtotime($startDate) > 93 * 86400) {
            $this->setErrorMessage('查询日期间隔不能大于3个月');
            return false;
        }

        $data = ApproveService::queryApproveOrder($user, $numType, $startDate, $endDate);

        //解析roomTable，用于分析冲突
        $roomTables = [];
        foreach ($data['orders'] as $id => $order) {
            $room_id = $order['room_id'];
            $date = $order['date'];
            if (isset($roomTables[$room_id.'_'.$date])){
                continue;
            }

            $roomTable = RoomService::queryRoomTable($date, $room_id);
            unset($roomTable['locked']);
            unset($roomTable['hourTable']);
            unset($roomTable['chksum']);
            $roomTables[$room_id.'_'.$date] = $roomTable;
        }
        $data['roomTables'] = $roomTables;
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;

        return $data;
    }
}
