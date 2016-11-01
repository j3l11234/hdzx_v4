<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use yii\base\UserException;

use common\behaviors\ErrorBehavior;
use common\models\entities\Lock;
use common\services\LockService;
use common\services\RoomService;

/**
 * Lock Form
 */
class LockForm extends Model {
    public $lock_id;
    public $hours;
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
        $scenarios[static::SCENARIO_ADD_LOCK] = ['hours', 'rooms', 'loop_type', 'loop_day', 'start_date', 'end_date', 'status', 'title', 'comment'];
        $scenarios[static::SCENARIO_EDIT_LOCK] = ['lock_id', 'hours', 'rooms', 'loop_type', 'loop_day', 'start_date', 'end_date', 'status', 'title', 'comment'];
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
            [['lock_id', 'rooms', 'hours', 'loop_type', 'status', 'title'], 'required'],
            [['start_date', 'end_date'], 'required', 'on'=>[static::SCENARIO_ADD_LOCK, static::SCENARIO_EDIT_LOCK]],
            ['loop_day', 'number', 'min'=>0, 'max'=>31,],
            ['loop_type', 'in', 'range' => [Lock::LOOP_DAY, Lock::LOOP_WEEK, Lock::LOOP_MONTH, Lock::LOOP_INTERVAL,], 'message' => '房间锁类型异常'],
            [['start_date', 'end_date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['rooms','hours'], 'jsonValidator'],
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
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        if ($this->scenario == static::SCENARIO_ADD_LOCK) {
            $lock = new Lock();
        } else {
            $lock = Lock::findOne($this->lock_id);
            if(empty($lock)){
                throw new UserException('房间锁不存在');
            }
        }
        if (strtotime($this->end_date) - strtotime($this->start_date) > 3*366*86400) {
            throw new UserException('开始时间和结束时间不能超过三年');
        }
        $hours = array_intersect(json_decode($this->hours, TRUE), Yii::$app->params['order.hours']);
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

        if ($this->scenario == static::SCENARIO_ADD_LOCK) {
            LockService::addLock($lock);
            return '添加房间锁成功';
        } else if ($this->scenario == static::SCENARIO_EDIT_LOCK){
            LockService::editLock($lock);
            return '修改房间锁成功';
        }
    }

    /**
     * 删除房间锁
     *
     * @return Order|false 是否删除成功
     */
    public function deleteLock() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $lock = Lock::findOne($this->lock_id);
        if(empty($lock)){
            throw new UserException('房间锁不存在');
        }

        LockService::deleteLock($lock);
        return '删除房间锁成功';
    }

    /**
     * 应用房间锁
     *
     * @return Order|false 是否删除成功
     */
    public function applyLock() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        if (strtotime($this->end_date) - strtotime($this->start_date) > 366*86400) {
            throw new UserException('开始时间和结束时间不能超过一年');
        }

        $dateRange = RoomService::getSumDateRange(FALSE);
        $startDate = !empty($this->start_date) ? $this->start_date : date('Y-m-d', $dateRange['start']);
        $endDate = !empty($this->end_date) ? $this->end_date : date('Y-m-d', $dateRange['end']);
        
        RoomService::applyLock($startDate, $endDate);
        return '应用房间锁成功';
    }
}
