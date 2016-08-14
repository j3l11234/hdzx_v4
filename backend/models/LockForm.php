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
 * Signup form
 */
class ApproveForm extends Model {
    public $order_id;
    public $type;
    public $comment;

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
        $scenarios['approveOrder'] = ['order_id', 'type', 'comment'];
        $scenarios['rejectOrder'] = ['order_id', 'type', 'comment'];
        $scenarios['revokeOrder'] = ['order_id', 'type', 'comment'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['order_id', 'type'], 'required'],
            [['type'], 'in', 'range' => ['auto', 'manager', 'school', ]],
        ];
    }


    /**
     * 通过预约
     *
     * @return Order|false 是否审批成功
     */
    public function approveOrder() {
        $user = Yii::$app->user->getIdentity()->getUser();
        $order = Order::findOne($this->order_id);

        if($order === null){
            $this->setErrorMessage('预约不存在');
            return false;
        }

        $numType = $this->getType($this->type);

        try {
            ApproveService::approveOrder($order, $user, $numType, $this->comment);
            return $order;
        } catch (Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        } 
    }

    /**
     * 驳回预约
     *
     * @return Order|false 是否审批成功
     */
    public function rejectOrder() {
        $user = Yii::$app->user->getIdentity()->getUser();
        $order = Order::findOne($this->order_id);

        if($order === null){
            $this->setErrorMessage('预约不存在');
            return false;
        }

        $numType = $this->getType($this->type);
        
        try {
            ApproveService::rejectOrder($order, $user, $numType, $this->comment);
            return $order;
        } catch (Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        } 
    }

    /**
     * 撤回审批预约
     *
     * @return Order|false 是否审批成功
     */
    public function revokeOrder() {
        $user = Yii::$app->user->getIdentity()->getUser();
        $order = Order::findOne($this->order_id);

        if($order === null){
            $this->setErrorMessage('预约不存在');
            return false;
        }

        $numType = $this->getType($this->type);
        
        try {
            ApproveService::revokeOrder($order, $user, $numType, $this->comment);
            return $order;
        } catch (Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        } 
    }
}
