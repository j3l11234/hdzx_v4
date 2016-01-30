<?php
namespace frontend\models;

use common\behaviors\ErrorBehavior;
use common\models\User;
use common\models\entities\Room;
use common\models\services\RoomService;
use yii\base\Model;
use Yii;

/**
 * Signup form
 */
class OrderQueryForm extends Model {
    public $start_date;
    public $end_date;
    public $rooms;
    public $rt_detail = false;

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
    public function rules() {
        return [
            [['start_date', 'end_date'], 'required'],
            [['rooms'], 'jsonValidator']
        ];
    }

    function jsonValidator($attribute, $params) {
        if (!is_array(json_decode($this->$attribute, true))) {
            $this->addError($attribute, $attribute.'格式错误');
        }
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function getRoomTables() {
        $roonList = json_decode($this->rooms,true);
        $startDate = strtotime($this->start_date);
        $endDate = strtotime($this->end_date);

        $roomTables = [];
        foreach ($roonList as $room_id) {
            $roomTables[$room_id] = [];
            $room = Room::findOne($room_id);
            //计算日期范围 合并起始区间
            $dateRange = $room->getDateRange();
            $start = $startDate;
            $end = min($endDate, $dateRange['end']);
            for ($time=$start; $time <= $end; $time = strtotime("+1 day", $time)) {
                $date = date('Y-m-d', $time);
                $roomTable = RoomService::queryRoomTable($date, $room_id);
                if(!$this->rt_detail){
                    unset($roomTable['ordered']);
                    unset($roomTable['used']);
                    unset($roomTable['locked']);
                }
                $roomTables[$room_id][$date] = $roomTable;
            }
            
        }
        return $roomTables;
    }
}
