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
    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios['getRoomTables'] = ['start_date', 'end_date', 'rooms', 'rt_detail'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['start_date', 'end_date'], 'required'],
            [['start_date', 'end_date'], 'date', 'format'=>'yyyy-MM-dd'],
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
        $minEndDate = $startDate;

        $rooms = RoomService::queryRoomList()['rooms'];
        //计算hourTables
        $hourTables = [];
        foreach ($roonList as $room_id) {
            if(!isset($rooms[$room_id])){
                continue;
            }
            $hourTables[$room_id] = [];
            $room = $rooms[$room_id];
            //计算日期范围 合并起始区间
            $dateRange = Room::getDateRange($room['max_before'], $room['min_before'], $room['by_week']);
            $start = $startDate;
            $end = min($endDate, $dateRange['end']);
            $minEndDate = max($minEndDate, $end);

            for ($time=$start; $time <= $end; $time = strtotime("+1 day", $time)) {
                $date = date('Y-m-d', $time);
                $roomTable = RoomService::queryRoomTable($date, $room_id);
                if(!$this->rt_detail){
                    unset($roomTable['ordered']);
                    unset($roomTable['used']);
                    unset($roomTable['locked']);
                }
                $hourTables[$room_id][$date] = $roomTable;
            }
        }

        //计算dateList
        $dateList = [];
        for ($time=$startDate; $time <= $minEndDate; $time = strtotime("+1 day", $time)) {
            $dateList[] = date('Y-m-d', $time);
        }

        return [
            'dateList' => $dateList,
            'hourTables' => $hourTables,
        ];
    }
}
