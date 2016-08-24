<?php
namespace frontend\models;

use Yii;
use yii\base\Model;
use common\behaviors\ErrorBehavior;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\Room;
use common\services\OrderService;

/**
 * OrderSubmit form
 */
class OrderSubmitForm extends Model {
    public $date;
    public $room_id;
    public $hours;
    public $name;
    public $phone;
    public $title;
    public $content;
    public $dept;
    public $number;
    public $secure;

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
        $scenarios['submitOrder'] = ['date', 'room_id', 'hours', 'name', 'phone', 'phone', 'title', 'content', 'number', 'secure'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['date', 'room_id', 'hours', 'name', 'phone', 'phone', 'title', 'content', 'number', 'secure'], 'required'],
            [['date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['hours'], 'jsonValidator'],
        ];
    }

    function jsonValidator($attribute, $params) {
        if (!is_string($this->$attribute) || !is_array(json_decode($this->$attribute, true))) {
            $this->addError($attribute, $attribute.'格式错误');
        }
    }


    /**
     * 提交设宁.
     *
     * @return Order|false 是否成功
     */
    public function submitOrder() {
        $user = Yii::$app->user->getIdentity()->getUser();

        $room = Room::findOne($this->room_id);
        $orderType;
        switch ($room->type) {
            case Room::TYPE_SIMPLE:
                $orderType = Order::TYPE_SIMPLE;
                break;
            case Room::TYPE_ACTIVITY:
                $orderType = Order::TYPE_TWICE;
                break;
            default:
                $orderType = Order::TYPE_TWICE;
                break;
        }
        //验证日期
        $roomData = $room->data;
        if(!$room->checkOpen($this->date, $roomData['max_before'], $roomData['min_before'], $roomData['by_week'], $roomData['open_time'])){
            $this->setErrorMessage('该日期下的房间不可用');
            return false;
        }
        $hours = json_decode($this->hours,true);
        
        $room = Room::findOne($this->room_id);
        if ($room === null) {
            $this->setErrorMessage('房间不存在');
        }
        if($user->isStudent()) {
            $deptName = '';
        } else {
            $deptName = $user->alias;
        }
        $order = new Order();
        $order->date = $this->date;
        $order->room_id = $this->room_id;
        $order->user_id = $user->id;
        $order->type = $orderType;
        $order->status = Order::STATUS_INIT;
        $order->hours = $hours;
        $order->data = [
            'name' => $this->name,
            'student_no' => $user->isStudent() ? $user->id : '',
            'phone' => $this->phone,
            'title' => $this->title,
            'content' => $this->content,
            'number' => $this->number,
            'secure' => $this->secure,
            'dept_name' => $deptName,
            'room_name' => $room->name.'('.$room->number.')',
        ];

        OrderService::submitOrder($order, $user);
        return $order;
    }
}
