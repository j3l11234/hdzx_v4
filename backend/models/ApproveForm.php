<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use yii\base\UserException;

use common\behaviors\ErrorBehavior;
use common\models\entities\User;
use common\models\entities\Order;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\services\RoomService;
use common\services\ApproveService;

/**
 * Approve form
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

    private static function getType($type) {
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
     * 通过预约
     *
     * @return Order|false 是否审批成功
     */
    public function approveOrder() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $order = Order::findOne($this->order_id);

        if ($order === null) {
            throw new UserException('申请不存在');
        }

        $type = static::getType($this->type);

        ApproveService::approveOrder($order, $user, $type, $this->comment);
        if ($type == ApproveService::TYPE_SIMPLE) { //琴房审批，驳回琴房的冲突预约
            ApproveService::rejectConflictOrder($order, $user, $type);
        } else if ( $type == ApproveService::TYPE_SCHOOL) { //校级审批，驳回负责人和校级的冲突预约
            ApproveService::rejectConflictOrder($order, $user, ApproveService::TYPE_SCHOOL);
            ApproveService::rejectConflictOrder($order, $user, ApproveService::TYPE_MANAGER);
        }
        return '审批通过成功';
    }

    /**
     * 驳回预约
     *
     * @return Order|false 是否审批成功
     */
    public function rejectOrder() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $order = Order::findOne($this->order_id);

        if ($order === null) {
            throw new UserException('申请不存在');
        }

        $type = static::getType($this->type);
        
        ApproveService::rejectOrder($order, $user, $type, $this->comment);
        return '审批驳回成功';
    }

    /**
     * 撤回审批预约
     *
     * @return Order|false 是否审批成功
     */
    public function revokeOrder() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $user = Yii::$app->user->getIdentity()->getUser();
        $order = Order::findOne($this->order_id);

        if ($order === null) {
            throw new UserException('申请不存在');
        }

        $type = static::getType($this->type);
        
        ApproveService::revokeOrder($order, $user, $type, $this->comment);
        return '审批撤回成功';
    }
}
