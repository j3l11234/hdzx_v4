<?php
namespace frontend\models;

use Yii;
use yii\base\Model;
use common\helpers\HdzxException;
use common\behaviors\ErrorBehavior;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\Room;
use common\services\OrderService;

/**
 * OrderSubmit form
 */
class OrderSubmitForm extends Model {
    public $order_id;
    public $date;
    public $room_id;
    public $hours;
    public $name;
    public $student_no;
    public $phone;
    public $title;
    public $content;
    public $dept;
    public $number;
    public $secure;

    const SCENARIO_SUBMIT_ORDER      = 'submitOrder';
    const SCENARIO_CANCEL_ORDER      = 'cancelOrder';

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
        $scenarios[static::SCENARIO_SUBMIT_ORDER] = ['date', 'room_id', 'hours', 'name', 'student_no', 'phone', 'title', 'content', 'number', 'secure'];
        $scenarios[static::SCENARIO_CANCEL_ORDER] = ['order_id'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['order_id', 'date', 'room_id', 'hours', 'name', 'student_no', 'phone', 'title', 'content', 'number', 'secure'], 'required'],
            [['student_no'], 'match', 'pattern' => '/^\d{8}$/'],
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
     * 提交申请.
     *
     * @return Order|false 是否成功
     */
    public function submitOrder() {
        try {
            $user = Yii::$app->user->getIdentity()->getUser();
            $hours = json_decode($this->hours, true);

            $room = Room::findOne($this->room_id);
            if ($room === null) {
                $this->setErrorMessage('房间不存在');
            }
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
            if(!Room::checkOpen($this->date, $roomData['max_before'], $roomData['min_before'], $roomData['by_week'], $roomData['open_time'])){
                $this->setErrorMessage('该日期下的房间不可用');
                return false;
            }

            //验证额度
            $usage = OrderService::queryUsage($user, strtotime($this->date), false);
            if($usage['month'][$this->room_id]['avl'] < count($hours)){
                $this->setErrorMessage('本月房间使用时长额度不足');
                return false;
            }
            if($usage['week'][$this->room_id]['avl'] < count($hours)){
                $this->setErrorMessage('本周房间使用时长额度不足');
                return false;
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
                'student_no' => $user->isStudent() ? substr($user->id,1) : $this->student_no,
                'phone' => $this->phone,
                'title' => $this->title,
                'content' => $this->content,
                'number' => $this->number,
                'secure' => $this->secure,
                'dept_name' => $deptName,
                'room_name' => $room->name.'('.$room->number.')',
            ];

        
            OrderService::submitOrder($order, $user);
            $this->setMessage('提交申请成功');
            return true;
        } catch (HdzxException $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }


    /**
     * 取消申请.
     *
     * @return Order|false 是否成功
     */
    public function cancelOrder() {
        try {
            $order = Order::findOne($this->order_id);
            if(empty($order)){
                $this->setErrorMessage('申请不存在');
                return false;
            }

            $user = Yii::$app->user->getIdentity()->getUser();

        
            OrderService::cancelOrder($order, $user);
            $this->setMessage('取消申请成功');
            return true;
        } catch (HdzxException $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }
}
