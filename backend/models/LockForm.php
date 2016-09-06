<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use common\helpers\HdzxException;

use common\behaviors\ErrorBehavior;
use common\models\entities\Lock;
use common\services\LockService;
use common\services\RoomService;

/**
 * Lock Form
 */
class LockForm extends Model {
    public $lock_id;
    public $start_hour;
    public $end_hour;
    public $rooms;
    public $loop_type;
    public $loop_day;
    public $start_date;
    public $end_date;
    public $status;
    public $title;
    public $comment;
    
    const SCENARIO_ADD_LOCK       = 'addLock';
    const SCENARIO_EDIT_LOCK      = 'editLock';
    const SCENARIO_DELETE_LOCK    = 'delLock';
    const SCENARIO_APPLY_LOCK     = 'applyLock';

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
        $scenarios[static::SCENARIO_ADD_LOCK] = ['start_hour', 'end_hour', 'rooms', 'loop_type', 'loop_day', 'start_date', 'end_date', 'status', 'title', 'comment'];
        $scenarios[static::SCENARIO_EDIT_LOCK] = ['lock_id', 'start_hour', 'end_hour', 'rooms', 'loop_type', 'loop_day', 'start_date', 'end_date', 'status', 'title', 'comment'];
        $scenarios[static::SCENARIO_DELETE_LOCK] = ['lock_id',];
        $scenarios[static::SCENARIO_APPLY_LOCK] = ['start_date', 'end_date'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        $startHour = Yii::$app->params['order.startHour'];
        $endHour = Yii::$app->params['order.endHour'];

        return [
            [['lock_id', 'rooms', 'loop_type', 'status', 'title', 'comment'], 'required'],
            [['start_date', 'end_date'], 'required', 'on'=>[static::SCENARIO_ADD_LOCK, static::SCENARIO_EDIT_LOCK]],
            ['loop_day', 'number', 'min'=>1, 'max'=>31,],
            ['start_hour', 'number', 'min'=>$startHour, 'max'=>$endHour-1,],
            ['start_hour', 'number', 'min'=>$startHour+1, 'max'=>$endHour,],
            [['start_date', 'end_date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['rooms'], 'jsonValidator'],
        ];
    }

    function jsonValidator($attribute, $params) {
        if (!is_string($this->$attribute) || !is_array(json_decode($this->$attribute, true))) {
            $this->addError($attribute, $attribute.'格式错误');
        }
    }

    /**
     * 提交房间锁
     *
     * @return Order|false 是否提交成功
     */
    public function submitLock() {
        if ($this->scenario == static::SCENARIO_ADD_LOCK) {
            $lock = new Lock();
        } else {
            $lock = Lock::findOne($this->lock_id);
            if(empty($lock)){
                $this->setErrorMessage('房间锁不存在');
                return false;
            }
        }
        if (strtotime($this->end_date) - strtotime($this->start_date) > 366*86400) {
            $this->setErrorMessage('开始时间和结束时间不能超过一年');
            return false;
        }

        $hours = [];
        for ($hour = $this->start_hour; $hour < $this->end_hour ; $hour++) { 
            $hours[] = (int)$hour;
        }
        $lock->hours = $hours;
        $lock->rooms = json_decode($this->rooms);
        $lock->start_date = $this->start_date;
        $lock->end_date = $this->end_date;
        $lock->status = $this->status;
        $lock->data = [
            'loop_type' => $this->loop_type,
            'loop_day' => $this->loop_day,
            'title' => $this->title,
            'comment' => $this->comment,
        ];

        try {
            if ($this->scenario == static::SCENARIO_ADD_LOCK) {
                LockService::addLock($lock);
                $this->setMessage('添加房间锁成功');
            } else if ($this->scenario == static::SCENARIO_EDIT_LOCK){
                LockService::editLock($lock);
                $this->setMessage('修改房间锁成功');
            }
            return true;
        } catch (HdzxException $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }

    /**
     * 删除房间锁
     *
     * @return Order|false 是否删除成功
     */
    public function deleteLock() {
        $lock = Lock::findOne($this->lock_id);
        if(empty($lock)){
            $this->setErrorMessage('房间锁不存在');
            return false;
        }

        try {
            LockService::deleteLock($lock);
            $this->setMessage('删除房间锁成功');
            return true;
        } catch (HdzxException $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }

    /**
     * 应用房间锁
     *
     * @return Order|false 是否删除成功
     */
    public function applyLock() {
        if (strtotime($this->end_date) - strtotime($this->start_date) > 366*86400) {
            $this->setErrorMessage('开始时间和结束时间不能超过一年');
            return false;
        }

        $dateRange = RoomService::queryDateRange();
        $startDate = !empty($this->start_date) ? $this->start_date : date('Y-m-d', $dateRange['start']);
        $endDate = !empty($this->end_date) ? $this->end_date : date('Y-m-d', $dateRange['end']);
        
        try {
            LockService::applyLock($startDate, $endDate);
            $this->setMessage('应用房间锁成功');
            return true;
        } catch (HdzxException $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }
}
