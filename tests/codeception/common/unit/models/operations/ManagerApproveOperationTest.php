<?php

namespace tests\codeception\common\unit\models;

use Yii;
use common\exceptions\OrderOperationException;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\models\entities\User;
use common\models\operations\BaseOrderOperation;
use common\models\operations\ManagerApproveOperation;
use common\models\services\RoomService;
use common\models\services\UserService;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\UserFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * OrderOperation test
 */
class ManagerApproveOperationTest extends DbTestCase {

    public function testCheckAuth() {
        //认证异常
        $order = Order::findOne(20);
        $user = UserService::findIdentity(1)->getUser();
        $order->managers = [];
        $user->removePrivilege(User::PRIV_APPROVE_MANAGER_ALL);

        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();
 
        $submitOp = new ManagerApproveOperation($order, $user, $roomTable);
        try {
            $submitOp->doOperation();
            expect('should throw exception', false)->true();
            $transaction->commit();
        } catch (OrderOperationException $e) {
            expect('exception should be ERROR_AUTH_FAILED', $e->getCode())->equals(BaseOrderOperation::ERROR_AUTH_FAILED);
            $transaction->rollBack();
        }

        $order = Order::findOne(20);
        $user = UserService::findIdentity(1)->getUser();
        $user->removePrivilege(User::PRIV_APPROVE_MANAGER_ALL);

        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();
 
        $submitOp = new ManagerApproveOperation($order, $user, $roomTable);
        try {
            $submitOp->doOperation();
            $transaction->commit();
        } catch (OrderOperationException $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function testCheckStatus() {
        //状态异常
        $order = Order::findOne(20);
        $order->status = Order::STATUS_PASSED;
        $user = UserService::findIdentity(1)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();  

        $submitOp = new ManagerApproveOperation($order, $user, $roomTable);
        try {
            $submitOp->doOperation();
            expect('should throw exception', false)->true();
            $transaction->commit();
        } catch (OrderOperationException $e) {
            expect('exception should be ERROR_INVALID_ORDER_STATUS', $e->getCode())->equals(BaseOrderOperation::ERROR_INVALID_ORDER_STATUS);
            $transaction->rollBack();
        }

         $newOrder = Order::findOne($order->id);
         expect('order->status should be STATUS_INIT', $newOrder->status)->equals(Order::STATUS_MANAGER_PENDING);
    }

    public function testOperation() {
        //状态正常
        $order = Order::findOne(20);
        $user = UserService::findIdentity(1)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();

        $submitOp = new ManagerApproveOperation($order, $user, $roomTable);
        try {
            $submitOp->doOperation();
            $transaction->commit();
        } catch (OrderOperationException $e) {
            $transaction->rollBack();
            throw $e;    
        }

        $newOrder = Order::findOne($order->id);
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_MANAGER_APPROVED);

        $orderOp = OrderOperation::findOne([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'type' => OrderOperation::TYPE_MANAGER_APPROVE
            ]);
        expect('can find $orderOp', $orderOp)->notNull();

        $newRoomTable = RoomService::getRoomTable($order->date, $order->room_id);
        $ordered = $newRoomTable->getOrdered($order->hours);
        $used = $newRoomTable->getUsed($order->hours);
        expect('roomTable->ordered have order', in_array($order->id, $ordered))->true();
        expect('roomTable->used have not order', in_array($order->id, $used))->false();
    }

    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'room_table' => [
                'class' => RoomTableFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/roomtable.php'
            ],
            'order_op' => [
                'class' => OrderOperationFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/order_op.php'
            ],
            'order' => [
                'class' => OrderFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/order.php'
            ],
            'user' => [
                'class' => UserFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/user.php'
            ],
        ];
    }
}
