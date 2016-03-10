<?php
namespace frontend\models;

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
    public function scenarios(){
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
            [['type'], 'in', 'range' => [ApproveService::TYPE_AUTO, ApproveService::TYPE_MANAGER, ApproveService::TYPE_SCHOOL]],
        ];
    }

    function dateRangeValidator($attribute, $params) {
        $today = strtotime(date('Y-m-d', time()));
        $start = strtotime("-31 day",$today);
        $end = strtotime("+31 day",$today);
        
        $date = strtotime($this->$attribute);
        
        if($date < $start  || $date > $end){
            $this->addError($attribute, $attribute.'超出范围，只能查询前后一个月内');
        }
    }


    /**
     * 查询房间使用表
     *
     * @return User|null the saved model or null if saving fails
     */
    public function getApproveOrder() {
        $user = Yii::$app->user->getIdentity()->getUser();

        $data = ApproveService::queryApproveOrder($user, $this->type, $this->start_date, $this->end_date);
        return $data;
    }
}
