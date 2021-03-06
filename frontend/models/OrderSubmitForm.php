<?php
namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\base\UserException;

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
    public $dept_id;
    public $name;
    public $student_no;
    public $phone;
    public $title;
    public $content;
    public $dept;
    public $number;
    public $secure;

    public $prin_student;
    public $prin_student_phone;
    public $prin_teacher;
    public $prin_teacher_phone;
    public $activity_type;
    public $need_media;

    public $captchaTime;

    const SCENARIO_SUBMIT_ORDER      = 'submitOrder';
    const SCENARIO_SUBMIT_ORDER_PAPER      = 'submitOrderPaper';
    const SCENARIO_CANCEL_ORDER      = 'cancelOrder';
    const SCENARIO_PAPER_ORDER       = 'paperOrder';
    const SCENARIO_UPDATE_ORDER_EXT       = 'updateOrderExt'; //旧数据兼容方案

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
        return array_merge(parent::scenarios(), [
            static::SCENARIO_SUBMIT_ORDER => ['date', 'room_id', 'hours', 'dept_id', 'name', 'student_no', 'phone', 'title', 'content', 'number', 'secure','prin_student', 'prin_student_phone', 'prin_teacher', 'prin_teacher_phone', 'activity_type', 'need_media'],
            static::SCENARIO_CANCEL_ORDER => ['order_id'],
            static::SCENARIO_PAPER_ORDER => ['order_id'],

            static::SCENARIO_UPDATE_ORDER_EXT => ['order_id','prin_student', 'prin_student_phone', 'prin_teacher', 'prin_teacher_phone', 'activity_type', 'need_media'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['order_id', 'date', 'room_id', 'hours', 'dept_id', 'name', 'student_no', 'phone', 'title', 'content', 'number', 'secure'], 'required'],
            [['prin_student', 'prin_student_phone', 'prin_teacher', 'prin_teacher_phone', 'activity_type'], 'required', 'on' => static::SCENARIO_SUBMIT_ORDER_PAPER],
            [['student_no'], 'match', 'pattern' => '/^\d{8}$/'],
            [['date'], 'date', 'format'=>'yyyy-MM-dd'],
            [['hours'], 'jsonValidator'],
        ];
    }

    function jsonValidator($attribute, $params) {
        if (!is_string($this->$attribute) || !is_array(json_decode($this->$attribute, TRUE))) {
            $this->addError($attribute, $attribute.'格式错误');
        }
    }


    /**
     * 提交申请.
     *
     * @return Order|false 是否成功
     */
    public function submitOrder() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $hours = array_intersect(json_decode($this->hours, TRUE), Yii::$app->params['order.hours']);

        $dept = Department::findOne($this->dept_id);
        if ($dept === null) {
            throw new UserException('社团单位不存在');
        }
        $room = Room::findOne($this->room_id);
        if ($room === null) {
            throw new UserException('房间不存在');
        }
        if (isset($room->data['need_paper']) && $room->data['need_paper'] == 1) {
            $this->scenario = static::SCENARIO_SUBMIT_ORDER_PAPER;
            if (!$this->validate(['prin_student', 'prin_student_phone', 'prin_teacher', 'prin_teacher_phone', 'activity_type', 'need_media'])) {
                throw new UserException($this->getErrorMessage());
            }   
        }


        //验证日期
        $roomData = $room->data;
        if(!Room::checkOpen($roomData, $this->date, time())){
            throw new UserException('该日期下的房间不可用');
        }

        if ($this->captchaTime < Room::getOpenPeriod($roomData, $this->date, time())['start']) {
            throw new UserException('验证码过期');
        }

        //验证时长
        if ($room->data['max_hour'] < count($this->hours)){
            throw new UserException('申请时长超过限制');
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
        
        //验证额度
        $usage = OrderService::queryUsage($user, strtotime($this->date), false);
        if($usage['month'][$this->room_id]['avl'] < count($hours)){
            throw new UserException('本月房间使用时长额度不足');
        }
        if($usage['week'][$this->room_id]['avl'] < count($hours)){
            throw new UserException('本周房间使用时长额度不足');
        }
        
        $order = new Order();
        $order->date = $this->date;
        $order->room_id = $this->room_id;
        $order->user_id = $user->id;
        $order->dept_id = $this->dept_id;
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
            'dept_name' => $dept->name,
            'room_name' => $room->name.'('.$room->number.')',
            'need_paper' => 0,
        ];
        if ($this->scenario == static::SCENARIO_SUBMIT_ORDER_PAPER) {
            $order->data = array_merge($order->data, [
                'need_paper' => 1,
                'prin_student' => $this->prin_student,
                'prin_student_phone' => $this->prin_student_phone,
                'prin_teacher' => $this->prin_teacher,
                'prin_teacher_phone' => $this->prin_teacher_phone,
                'activity_type' => $this->activity_type,
                'need_media' => $this->need_media ? 1 : 0,
            ]);
        }

        OrderService::submitOrder($order, $user);
        return '提交申请成功';
    }


    /**
     * 取消申请.
     *
     * @return Order|false 是否成功
     */
    public function cancelOrder() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $order = Order::find()->where(['id' => $this->order_id, 'user_id' => $user->id])->one();
        if(empty($order)){
            throw new UserException('申请不存在');
        }

        OrderService::cancelOrder($order, $user);
        return '取消申请成功';
    }

    /**
     * 获取纸质申请表.
     *
     * @return Order|false 是否成功
     */
    public function paperOrder() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $order = Order::find()->where(['id' => $this->order_id, 'user_id' => $user->id])->one();
        if(empty($order)){
            throw new UserException('申请不存在');
        }

        //TBD need_paper检测
        if ($order->data['need_paper'] != 1) {
            throw new UserException('该申请无需打印申请表');
        }

        $data = OrderService::paperOrder($order, $user);
        return $data;
    }

    /**
     * 更新申请额外信息
     * (旧数据兼容方案)
     *
     * @return Order|false 是否成功
     */
    public function updateOrderExt() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $order = Order::find()->where(['id' => $this->order_id, 'user_id' => $user->id])->one();
        if(empty($order)){
            throw new UserException('申请不存在');
        }

        OrderService::updateOrderExt($order, $user, [
            'prin_student' => $this->prin_student,
            'prin_student_phone' => $this->prin_student_phone,
            'prin_teacher' => $this->prin_teacher,
            'prin_teacher_phone' => $this->prin_teacher_phone,
            'activity_type' => $this->activity_type,
            'need_media' => $this->need_media ? 1 : 0,
        ]);
        
        return '更新申请信息成功';
    }
}
