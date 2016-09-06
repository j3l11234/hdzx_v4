<?php
namespace backend\models;

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
class OrderForm extends Model {
    public $order_id;
    public $comment;

    const SCENARIO_ISSUE      = 'issueOrder';

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
        $scenarios[static::SCENARIO_ISSUE] = ['order_id','comment'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['order_id',], 'required'],
        ];
    }


    /**
     * 取消申请.
     *
     * @return Order|false 是否成功
     */
    public function issueOrder() {
        try {
            $order = Order::findOne($this->order_id);
            if(empty($order)){
                $this->setErrorMessage('申请不存在');
                return false;
            }

            $user = Yii::$app->user->getIdentity()->getUser();

        
            OrderService::issueOrder($order, $user, $this->comment);
            $this->setMessage('发放开门条成功');
            return true;
        } catch (HdzxException $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }
}
